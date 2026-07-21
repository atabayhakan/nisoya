<?php

namespace App\Filament\Pages;

use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

/**
 * E-posta (SMTP) ayarları — sahibin sunucuya (SSH/.env) dokunmadan e-posta
 * sağlayıcısını panelden yönetebilmesi (Faz 1 · G3).
 *
 * Değerler DB'ye yazılır; AppServiceProvider::mergeMailConfig() ile
 * config('mail.*') runtime'da override edilir (DB > env > kod). "Test e-postası
 * gönder" formdaki (henüz kaydedilmemiş olabilen) değerlerle gerçek bir deneme
 * gönderir. SMTP parolası hassas olduğundan yalnızca Admin erişebilir.
 */
class MailAyarlari extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static ?string $navigationLabel = 'E-posta (SMTP)';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.mail-ayarlari';

    public ?array $data = [];

    /** SMTP parolası burada görünür/düzenlenir — yalnızca Admin. */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return 'E-posta (SMTP) Ayarları';
    }

    public function mount(): void
    {
        // DB'de değer varsa onu, yoksa mevcut (env) config'i göster — parola
        // hariç: env parolasını forma sızdırma, yalnızca DB'dekini göster.
        $this->form->fill([
            'host' => Settings::get('mail.host') ?: config('mail.mailers.smtp.host'),
            'port' => Settings::get('mail.port') ?: (string) config('mail.mailers.smtp.port'),
            'username' => Settings::get('mail.username') ?: config('mail.mailers.smtp.username'),
            'password' => Settings::get('mail.password') ?: '',
            'encryption' => Settings::get('mail.encryption') ?: $this->schemeToEncryption(config('mail.mailers.smtp.scheme')),
            'from_address' => Settings::get('mail.from_address') ?: config('mail.from.address'),
            'from_name' => Settings::get('mail.from_name') ?: config('mail.from.name'),
            'test_alici' => auth()->user()?->email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('SMTP sunucusu')
                    ->description('E-posta sağlayıcının (ör. Hostinger, Gmail, SendGrid) SMTP bilgileri. Kaydettiğinde değişiklik anında geçerli olur — sunucuya erişmene gerek yok.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('host')
                            ->label('SMTP sunucusu')
                            ->placeholder('smtp.hostinger.com')
                            ->helperText('Sağlayıcının verdiği sunucu adresi.'),

                        Select::make('encryption')
                            ->label('Şifreleme / Port')
                            ->options([
                                'ssl' => 'SSL — port 465',
                                'tls' => 'TLS / STARTTLS — port 587',
                            ])
                            ->native(false)
                            ->helperText('Çoğu sağlayıcı SSL (465) kullanır.'),

                        TextInput::make('port')
                            ->label('Port')
                            ->numeric()
                            ->placeholder('465')
                            ->helperText('SSL için 465, TLS için 587.'),

                        TextInput::make('username')
                            ->label('Kullanıcı adı')
                            ->placeholder('info@nisoya.com')
                            ->autocomplete('off'),

                        TextInput::make('password')
                            ->label('Parola')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->helperText('E-posta hesabının SMTP parolası. Boş bırakırsan kayıtlı parola korunur.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Gönderen bilgisi')
                    ->description('Giden e-postalarda görünecek ad ve adres.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('from_address')
                            ->label('Gönderen e-posta')
                            ->email()
                            ->placeholder('info@nisoya.com'),

                        TextInput::make('from_name')
                            ->label('Gönderen adı')
                            ->placeholder('Nisoya'),
                    ]),

                Section::make('Test')
                    ->description('Ayarların gerçekten çalıştığını doğrulamak için kendine bir test e-postası gönder.')
                    ->schema([
                        TextInput::make('test_alici')
                            ->label('Test e-postasının gideceği adres')
                            ->email()
                            ->helperText('Varsayılan olarak kendi adresin.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Parola boş bırakıldıysa mevcut (kayıtlı) parolayı koru — yanlışlıkla
        // silinip SMTP kimlik doğrulamasının bozulmasını önler.
        $password = ($state['password'] ?? '') !== ''
            ? $state['password']
            : (Settings::get('mail.password') ?? '');

        Settings::setMany([
            'mail.host' => $state['host'] ?? '',
            'mail.port' => $state['port'] ?? '',
            'mail.username' => $state['username'] ?? '',
            'mail.password' => $password,
            'mail.encryption' => $state['encryption'] ?? '',
            'mail.from_address' => $state['from_address'] ?? '',
            'mail.from_name' => $state['from_name'] ?? '',
        ]);

        Notification::make()
            ->title('E-posta ayarları kaydedildi')
            ->body('Değişiklik canlı sitede anında geçerli.')
            ->success()
            ->send();
    }

    /** Formdaki (kaydedilmemiş olabilir) değerlerle gerçek bir test e-postası gönderir. */
    public function testEt(): void
    {
        $state = $this->form->getState();

        if (($state['host'] ?? '') === '') {
            Notification::make()
                ->title('Önce SMTP sunucusu gir')
                ->warning()
                ->send();

            return;
        }

        $this->applyMailConfigFromState($state);

        $to = ($state['test_alici'] ?? '') ?: auth()->user()->email;

        try {
            Mail::raw(
                'Bu bir Nisoya test e-postasıdır. Bu mesajı aldıysanız e-posta (SMTP) ayarların çalışıyor. ✓',
                fn ($message) => $message->to($to)->subject('Nisoya · E-posta ayarları testi')
            );

            Notification::make()
                ->title('Test e-postası gönderildi ✓')
                ->body($to.' adresine gönderildi. Gelen kutunu (ve spam klasörünü) kontrol et.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('E-posta gönderilemedi')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /** Form değerlerini config('mail.*') üzerine geçici olarak uygular (test için). */
    protected function applyMailConfigFromState(array $state): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $state['host'] ?? '',
            'mail.mailers.smtp.port' => (int) (($state['port'] ?? '') ?: 587),
            'mail.mailers.smtp.username' => ($state['username'] ?? '') ?: null,
            'mail.mailers.smtp.password' => ($state['password'] ?? '') ?: (Settings::get('mail.password') ?: null),
            'mail.mailers.smtp.scheme' => match ($state['encryption'] ?? '') {
                'ssl' => 'smtps',
                'tls' => 'smtp',
                default => null,
            },
            // Yanlış sunucu/porta uzun süre asılı kalmasın diye kısa zaman aşımı.
            'mail.mailers.smtp.timeout' => 15,
            'mail.from.address' => ($state['from_address'] ?? '') ?: config('mail.from.address'),
            'mail.from.name' => ($state['from_name'] ?? '') ?: config('mail.from.name'),
        ]);

        // MailManager çözümlenmiş "smtp" taşıyıcısını önbelleğe alır; yeni
        // config'in geçerli olması için önbelleği temizle. (Testte Mail::fake
        // altındaki sahte sürücüde purge olmayabilir → method_exists ile koru.)
        if (method_exists(Mail::getFacadeRoot(), 'purge')) {
            Mail::purge('smtp');
        }
    }

    /** config scheme değerini form 'encryption' değerine çevirir (gösterim için). */
    protected function schemeToEncryption(?string $scheme): string
    {
        return match ($scheme) {
            'smtps' => 'ssl',
            'smtp' => 'tls',
            default => '',
        };
    }
}
