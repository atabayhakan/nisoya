<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Support\GoogleGiris;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Giriş yöntemleri — şimdilik yalnız "Google ile giriş".
 *
 * Sahip anahtarları buradan girer; `AppServiceProvider::mergeRuntimeConfig()`
 * bunları `services.google.*` üzerine yazar. Client secret
 * `Settings::SIRLI_ANAHTARLAR` listesinde olduğu için veritabanında şifreli
 * durur.
 *
 * Ekranda yönlendirme adresi AÇIKÇA gösterilir: Google Cloud Console'daki
 * "Authorized redirect URI" ile harfi harfine aynı olmak zorunda ve kurulumda
 * en sık yapılan hata bu adresi yanlış yazmak.
 */
class GirisYontemleri extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Kullanıcılar & Güvenlik';

    protected static ?string $navigationLabel = 'Giriş Yöntemleri';

    protected string $view = 'filament.pages.giris-yontemleri';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Giriş Yöntemleri';
    }

    public function mount(): void
    {
        $this->form->fill([
            'google_aktif' => Settings::get('giris.google_aktif') === '1',
            'google_client_id' => Settings::get('giris.google_client_id') ?: '',
            'google_client_secret' => Settings::get('giris.google_client_secret') ?: '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Google ile giriş')
                    ->description('Üyeler Google hesabıyla kaydolabilir ve girebilir. Anahtarlar Google Cloud Console → "APIs & Services" → "Credentials" → "OAuth client ID" (tür: Web application) altından alınır.')
                    ->schema([
                        Toggle::make('google_aktif')
                            ->label('Google ile giriş açık')
                            ->helperText('Kapalıyken giriş ve kayıt sayfalarında Google düğmesi hiç görünmez. Anahtarlardan biri boşsa düğme yine görünmez — yarım kurulumla çalışmayan bir düğme göstermemek için.'),

                        TextInput::make('google_client_id')
                            ->label('Client ID')
                            ->helperText('…apps.googleusercontent.com ile biter.'),

                        TextInput::make('google_client_secret')
                            ->label('Client Secret')
                            ->password()
                            ->revealable()
                            ->helperText('Veritabanında şifreli saklanır.'),
                    ]),

                Section::make('Google Cloud Console\'a girilecek adres')
                    ->description('Aşağıdaki adresi "Authorized redirect URIs" alanına HARFİ HARFİNE ekle. Tek karakter farkı bile Google\'ın redirect_uri_mismatch hatası vermesine yeter.')
                    ->schema([
                        TextInput::make('yonlendirme')
                            ->label('Authorized redirect URI')
                            ->default(fn () => GoogleGiris::yonlendirmeAdresi())
                            ->readOnly()
                            ->dehydrated(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Settings::setMany([
            'giris.google_aktif' => ! empty($state['google_aktif']) ? '1' : '0',
            'giris.google_client_id' => $state['google_client_id'] ?? '',
            'giris.google_client_secret' => $state['google_client_secret'] ?? '',
        ]);

        Notification::make()
            ->title('Giriş ayarları kaydedildi')
            ->body('Değişiklik canlı sitede anında geçerli.')
            ->success()
            ->send();
    }
}
