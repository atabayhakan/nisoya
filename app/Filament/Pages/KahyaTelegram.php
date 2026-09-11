<?php

namespace App\Filament\Pages;

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
 * Kâhya Telegram — grup içi otomatik cevaplama ayarları (tasarım kararı,
 * 2026-09-11: "üçüncü taraf gruplara girmek yerine kendi grubumuzu kur,
 * Kâhya normal bir üye gibi katılsın").
 *
 * DB'ye yazılır, `AppServiceProvider::mergeKahyaConfig()` ile runtime'da
 * override edilir — KahyaAyarlari ile aynı desen, deploy/SSH gerekmez.
 *
 * Gerçek işi yapan sınıf App\Services\Kahya\Dis\TelegramDinleyici; bu sayfa
 * yalnız onun okuduğu ayarları düzenler.
 */
class KahyaTelegram extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Kâhya & Yapay Zekâ';

    protected static ?string $navigationLabel = 'Kâhya Telegram';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.kahya-telegram';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return 'Kâhya Telegram';
    }

    public function mount(): void
    {
        $this->form->fill([
            'aktif' => filter_var(Settings::get('kahya.telegram.aktif', '1'), FILTER_VALIDATE_BOOLEAN),
            'bot_token' => Settings::get('kahya.telegram.bot_token') ?: '',
            'bot_kullanici_adi' => Settings::get('kahya.telegram.bot_kullanici_adi') ?: '',
            'webhook_sirri' => Settings::get('kahya.telegram.webhook_sirri') ?: '',
            'izinli_grup_id' => Settings::get('kahya.telegram.izinli_grup_id') ?: '',
            'ulke_kodu' => Settings::get('kahya.telegram.ulke_kodu') ?: config('kahya.telegram.ulke_kodu', 'RU'),
            'persona_adi' => Settings::get('kahya.telegram.persona_adi') ?: '',
            'aylik_mesaj_limiti' => (int) (Settings::get('kahya.telegram.aylik_mesaj_limiti') ?: config('kahya.telegram.aylik_mesaj_limiti', 600)),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $webhookUrl = url('/webhook/telegram');

        return $schema
            ->components([
                Section::make('Kurulum')
                    ->description(
                        "1) @BotFather'da bir bot oluştur, token'ı aşağıya yapıştır. ".
                        '2) "Webhook gizli anahtarı" alanına rastgele bir metin yaz. '.
                        "3) Tarayıcıda şu adresi aç (TOKEN ve SIR'ı kendi değerlerinle değiştir): ".
                        "https://api.telegram.org/bot<TOKEN>/setWebhook?url={$webhookUrl}&secret_token=<SIR>"
                    )
                    ->schema([
                        TextInput::make('bot_token')
                            ->label('Bot token')
                            ->password()
                            ->revealable()
                            ->maxLength(200),
                        TextInput::make('bot_kullanici_adi')
                            ->label('Bot kullanıcı adı')
                            ->placeholder('örn. NisoyaYardimBot (@ olmadan)')
                            ->helperText('Grupta bir davet linki gösterilecekse (t.me/bu-ad) kullanılır.')
                            ->maxLength(80),
                        TextInput::make('webhook_sirri')
                            ->label('Webhook gizli anahtarı')
                            ->password()
                            ->revealable()
                            ->maxLength(200)
                            ->helperText('setWebhook çağrısındaki secret_token ile birebir aynı olmalı.'),
                    ])
                    ->columns(1),

                Section::make('Davranış')
                    ->schema([
                        Toggle::make('aktif')
                            ->label('Aktif')
                            ->helperText('Kapatınca ayarlar silinmez, Kâhya yalnız cevap vermeyi durdurur.'),
                        TextInput::make('persona_adi')
                            ->label('Grupta görünecek ad')
                            ->placeholder('Nisoya Kâhyası')
                            ->helperText('Boş bırakılırsa "Nisoya Kâhyası" kullanılır. İç panelde kullanılan "Kâhya" adından bilerek ayrı.')
                            ->maxLength(40),
                        TextInput::make('izinli_grup_id')
                            ->label('İzinli grup ID')
                            ->helperText('Kâhya YALNIZ bu gruba cevap verir. Grup ID\'sini öğrenmek için bota grupta bir mesaj attır, Kâhya Telegram Sohbetleri ekranında görünür.')
                            ->maxLength(64),
                        TextInput::make('ulke_kodu')
                            ->label('Ülke kodu')
                            ->placeholder('RU')
                            ->helperText('Cevaplarda hangi ülkenin YAYINDAki Ülke/Yaşam Rehberi içeriği bağlam olarak kullanılsın (ör. RU, DE, US).')
                            ->maxLength(2),
                        TextInput::make('aylik_mesaj_limiti')
                            ->label('Aylık AI-cevaplı mesaj tavanı')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100000)
                            ->helperText('Bu sayıya ulaşınca ay bitene kadar cevap verilmez (0 = tamamen kapalı). Kullanım Kâhya Harcamaları\'nda görünür.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Settings::setMany([
            'kahya.telegram.aktif' => ($state['aktif'] ?? true) ? '1' : '0',
            'kahya.telegram.bot_token' => trim((string) ($state['bot_token'] ?? '')),
            'kahya.telegram.bot_kullanici_adi' => trim((string) ($state['bot_kullanici_adi'] ?? ''), '@ '),
            'kahya.telegram.webhook_sirri' => trim((string) ($state['webhook_sirri'] ?? '')),
            'kahya.telegram.izinli_grup_id' => trim((string) ($state['izinli_grup_id'] ?? '')),
            'kahya.telegram.ulke_kodu' => strtoupper(trim((string) ($state['ulke_kodu'] ?? 'RU'))),
            'kahya.telegram.persona_adi' => trim((string) ($state['persona_adi'] ?? '')),
            'kahya.telegram.aylik_mesaj_limiti' => (string) max(0, (int) ($state['aylik_mesaj_limiti'] ?? 600)),
        ]);

        Notification::make()
            ->title('Kâhya Telegram ayarları kaydedildi')
            ->body('Değişiklik anında geçerli.')
            ->success()
            ->send();
    }
}
