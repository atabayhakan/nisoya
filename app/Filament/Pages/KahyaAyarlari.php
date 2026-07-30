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
use UnitEnum;

/**
 * Kâhya ayarları — rapor alıcısı ve saati.
 *
 * DB'ye yazılır, `AppServiceProvider::mergeKahyaConfig()` ile `config('kahya.*')`
 * runtime'da override edilir. Değişiklik ANINDA geçerli olur; config:cache ya
 * da SSH gerekmez (YapayZekaAyarlari ile aynı desen).
 *
 * SAAT LİSTESİ SINIRLI: serbest metin yerine seçim, çünkü `dailyAt()` geçersiz
 * bir değerde sessizce çalışmaz — zamanlayıcı hatası da sessiz bir hatadır ve
 * bu sistemin bütün amacı sessiz hataları görünür kılmak.
 *
 * 04:00 ÖNCESİ SEÇENEK YOK: 03:30 medya temizliği ve 04:00 yedek bu saatlerde
 * koşuyor; rapor onlardan önce çalışırsa "son yedek" durumunu yanlış gösterir.
 */
class KahyaAyarlari extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Kâhya & Yapay Zekâ';

    protected static ?string $navigationLabel = 'Kâhya Ayarları';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.kahya-ayarlari';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return 'Kâhya Ayarları';
    }

    public function mount(): void
    {
        $this->form->fill([
            'isim' => Settings::get('kahya.isim') ?: config('kahya.isim', 'Kâhya'),
            'alici' => Settings::get('kahya.alici') ?: '',
            'rapor_saati' => Settings::get('kahya.rapor_saati') ?: config('kahya.rapor_saati', '07:30'),
            'sohbet_modeli' => Settings::get('kahya.sohbet_modeli') ?: '',
            'arama_saglayici' => Settings::get('kahya.arama_saglayici') ?: 'openrouter',
            'arama_anahtari' => Settings::get('kahya.arama_anahtari') ?: '',
            'places_anahtari' => Settings::get('kahya.places_anahtari') ?: '',
            'aylik_arama_limiti' => (int) (Settings::get('kahya.aylik_arama_limiti') ?: 300),
            'aylik_kesif_limiti' => (int) (Settings::get('kahya.aylik_kesif_limiti') ?: 200),
            'gonderim_host' => Settings::get('kahya.gonderim_host') ?: '',
            'gonderim_port' => (int) (Settings::get('kahya.gonderim_port') ?: 465),
            'gonderim_kullanici' => Settings::get('kahya.gonderim_kullanici') ?: '',
            'gonderim_parola' => Settings::get('kahya.gonderim_parola') ?: '',
            'gonderim_adresi' => Settings::get('kahya.gonderim_adresi') ?: '',
            'gonderim_ad' => Settings::get('kahya.gonderim_ad') ?: '',
            'gunluk_gonderim_limiti' => (int) (Settings::get('kahya.gunluk_gonderim_limiti') ?: 10),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kimlik')
                    ->description('Ajanın sohbet başlığında, karşılama kartında ve kendi tanıtımında kullandığı ad.')
                    ->schema([
                        TextInput::make('isim')
                            ->label('Ad')
                            ->placeholder('Kâhya')
                            ->maxLength(40)
                            ->helperText('Boş bırakılırsa "Kâhya" kullanılır.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Günlük rapor')
                    ->description('Kâhya her sabah sitenin durumunu özetleyip e-posta gönderir: ne oldu, seni bekleyen işler, ne bozuk, ne eksik.')
                    ->schema([
                        TextInput::make('alici')
                            ->label('Raporun gideceği e-posta')
                            ->email()
                            ->placeholder(Settings::get('iletisim.eposta') ?: 'iletisim adresine gönderilir')
                            ->helperText('Boş bırakırsan İletişim sayfasındaki adrese gider.')
                            ->columnSpanFull(),

                        Select::make('rapor_saati')
                            ->label('Gönderim saati (sunucu saati, UTC)')
                            ->options([
                                '05:00' => '05:00',
                                '06:00' => '06:00',
                                '07:00' => '07:00',
                                '07:30' => '07:30 (önerilen)',
                                '08:00' => '08:00',
                                '09:00' => '09:00',
                                '12:00' => '12:00',
                                '20:00' => '20:00',
                            ])
                            ->required()
                            ->helperText('04:00\'ten önce seçenek yok: yedekleme 04:00\'te koşuyor ve rapor "son yedek" durumunu doğru göstermeli.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Sohbet')
                    ->description('Kâhya ile Konuş sayfasında eylem seçimini yapan model. '
                        .'Boş bırakılırsa Yapay Zekâ Ayarları\'ndaki varsayılan model kullanılır.')
                    ->schema([
                        TextInput::make('sohbet_modeli')
                            ->label('Sohbet modeli (isteğe bağlı)')
                            ->placeholder('örn. anthropic/claude-sonnet-4.5')
                            // Doğru işi seçmek yanlış işi seçmekten çok daha
                            // ucuz: hata bedeli canlıda ödenir. Bu yüzden buraya
                            // varsayılandan güçlü bir model yazmak mantıklı.
                            ->helperText('Sağlayıcı Yapay Zekâ Ayarları\'ndaki sağlayıcıdır; burası yalnız model adını değiştirir. '
                                .'Eylem seçimi için varsayılandan güçlü bir model önerilir.')
                            ->maxLength(120)
                            ->columnSpanFull(),
                    ]),

                Section::make('Dış Gözler (F3)')
                    ->description('Kâhya\'nın web araması ve işletme keşfi. Anahtar girilmeden ilgili araç '
                        .'çalışmaz; Kâhya sohbette neyin eksik olduğunu söyler. Her çağrı Kâhya '
                        .'Harcamaları\'nda sayılır ve aylık limite tabidir.')
                    ->schema([
                        Select::make('arama_saglayici')
                            ->label('Web arama sağlayıcısı')
                            ->options([
                                'openrouter' => 'OpenRouter web (mevcut AI kredin — ek hesap gerekmez)',
                                'tavily' => 'Tavily (ücretsiz kota — tavily.com)',
                                'brave' => 'Brave Search (geniş dizin — brave.com/search/api)',
                            ])
                            ->helperText('OpenRouter ~$4/1000 arama (krediden); Tavily/Brave ücretsiz kotalı ama ayrı hesap ister.'),

                        TextInput::make('arama_anahtari')
                            ->label('Arama API anahtarı')
                            ->password()
                            ->revealable()
                            ->maxLength(200)
                            ->helperText('OpenRouter\'da boş bırakılabilir — Yapay Zekâ Ayarları\'ndaki anahtar kullanılır. Tavily/Brave için zorunlu.'),

                        TextInput::make('places_anahtari')
                            ->label('Google Places API anahtarı')
                            ->password()
                            ->revealable()
                            ->maxLength(200)
                            ->helperText('Google Cloud Console → Places API (New) etkinleştir + anahtar oluştur. Boşsa isletme-kesfet kapalı kalır.'),

                        TextInput::make('aylik_arama_limiti')
                            ->label('Aylık arama limiti')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100000)
                            ->helperText('Web araması, ay içinde bu sayıya ulaşınca durur (0 = tamamen kapalı).'),

                        TextInput::make('aylik_kesif_limiti')
                            ->label('Aylık keşif limiti')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100000)
                            ->helperText('İşletme keşfi (Places ücretli) için aylık tavan.'),
                    ])
                    ->columns(2),

                Section::make('Dış Eller (F4) — hamle gönderim kimliği')
                    ->description('Onayladığın e-posta hamle kartları bu kimlikle GÖNDERİLİR. UYARI: Buraya ana '
                        .'alan adının (nisoya.com) SMTP\'sini DEĞİL, AYRI bir gönderim alanının kimliğini gir '
                        .'(ör. mail.nisoya.com altında Amazon SES) — erişim postası şikâyet yerse spam damgasını '
                        .'yalnız o alan yer, üyelerin şifre sıfırlama postaları etkilenmez. Boş bırakılırsa onay '
                        .'yine kaydedilir ama gönderim yapılmaz (elle uygularsın).')
                    ->schema([
                        TextInput::make('gonderim_host')
                            ->label('SMTP sunucusu')
                            ->placeholder('örn. email-smtp.eu-central-1.amazonaws.com')
                            ->maxLength(190),
                        TextInput::make('gonderim_port')
                            ->label('Port')
                            ->numeric()
                            ->helperText('465 = SSL, 587 = TLS.'),
                        TextInput::make('gonderim_kullanici')
                            ->label('SMTP kullanıcı adı')
                            ->maxLength(190),
                        TextInput::make('gonderim_parola')
                            ->label('SMTP parolası')
                            ->password()
                            ->revealable()
                            ->maxLength(190),
                        TextInput::make('gonderim_adresi')
                            ->label('Gönderen adres')
                            ->email()
                            ->placeholder('örn. merhaba@mail.nisoya.com')
                            ->helperText('SPF/DKIM/DMARC kayıtları bu alan için DNS\'te tanımlı olmalı.'),
                        TextInput::make('gonderim_ad')
                            ->label('Gönderen adı')
                            ->placeholder('örn. Hakan — Nisoya')
                            ->maxLength(100),
                        TextInput::make('gunluk_gonderim_limiti')
                            ->label('Günlük gönderim tavanı (ısıtma)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(500)
                            ->helperText('Yeni gönderim kimliği ilk haftalarda günde 5-10 postayla ısınmalı; aceleyle artırma.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Settings::setMany([
            'kahya.isim' => trim((string) ($state['isim'] ?? '')),
            'kahya.alici' => trim((string) ($state['alici'] ?? '')),
            'kahya.rapor_saati' => $state['rapor_saati'] ?? '07:30',
            'kahya.sohbet_modeli' => trim((string) ($state['sohbet_modeli'] ?? '')),
            'kahya.arama_saglayici' => (string) ($state['arama_saglayici'] ?? 'tavily'),
            'kahya.arama_anahtari' => trim((string) ($state['arama_anahtari'] ?? '')),
            'kahya.places_anahtari' => trim((string) ($state['places_anahtari'] ?? '')),
            'kahya.aylik_arama_limiti' => (string) max(0, (int) ($state['aylik_arama_limiti'] ?? 300)),
            'kahya.aylik_kesif_limiti' => (string) max(0, (int) ($state['aylik_kesif_limiti'] ?? 200)),
            'kahya.gonderim_host' => trim((string) ($state['gonderim_host'] ?? '')),
            'kahya.gonderim_port' => (string) max(1, (int) ($state['gonderim_port'] ?? 465)),
            'kahya.gonderim_kullanici' => trim((string) ($state['gonderim_kullanici'] ?? '')),
            'kahya.gonderim_parola' => (string) ($state['gonderim_parola'] ?? ''),
            'kahya.gonderim_adresi' => trim((string) ($state['gonderim_adresi'] ?? '')),
            'kahya.gonderim_ad' => trim((string) ($state['gonderim_ad'] ?? '')),
            'kahya.gunluk_gonderim_limiti' => (string) max(0, (int) ($state['gunluk_gonderim_limiti'] ?? 10)),
        ]);

        Notification::make()
            ->title('Kâhya ayarları kaydedildi')
            ->body('Değişiklik anında geçerli. Denemek için Kâhya sayfasındaki "Şimdi rapor gönder" düğmesini kullanabilirsin.')
            ->success()
            ->send();
    }
}
