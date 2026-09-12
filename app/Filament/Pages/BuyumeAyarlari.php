<?php

namespace App\Filament\Pages;

use App\Services\Growth\BuyumeMetrikleriServisi;
use App\Services\Growth\Discovery\GooglePlacesDiscoverySource;
use App\Services\Growth\Discovery\OverpassDiscoverySource;
use App\Services\Growth\DiscoveryRunner;
use App\Support\Growth\GrowthCatalog;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Büyüme Ajanı (Growth Engine) Kontrol Merkezi.
 *
 * Yurt dışındaki Türk esnafının otonom keşfi, yapay zekâ destekli kültürel
 * tespiti, Tersine Katılım (Reverse Onboarding - sahiplenilebilir vitrinler)
 * ve WhatsApp davet süreçlerini tek merkezden yönetir.
 */
class BuyumeAyarlari extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Pazarlama & Büyüme';

    protected static ?string $navigationLabel = 'Büyüme Ajanı';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $source = Settings::get('growth.source') ?: config('growth.source', 'auto');

        return match ($source) {
            'overpass' => 'OSM Overpass',
            'google' => 'Google Places',
            'fixture' => 'Demo',
            default => 'Hibrit',
        };
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiStratejiRaporu')
                ->label('AI Büyüme Strateji Raporu')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Eylül 2026 AI Büyüme & Tersine Katılım Stratejisi')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat')
                ->modalContent(fn () => view('filament.growth.strateji-modal', [
                    'metrikler' => $this->getMetriklerProperty(),
                ])),
        ];
    }

    protected string $view = 'filament.pages.buyume-ayarlari';

    public ?array $data = [];

    // Hızlı Canlı Keşif Simülatörü alanları
    public string $kesif_ulke = 'DE';

    public string $kesif_sehir = 'Berlin';

    public string $kesif_meslek = 'lokanta';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return 'Büyüme Ajanı & Tersine Katılım Merkezi';
    }

    public function mount(): void
    {
        $this->form->fill([
            'source' => Settings::get('growth.source') ?: config('growth.source', 'auto'),
            'google_places_api_key' => Settings::get('growth.google_places_api_key') ?: (config('growth.google_places.api_key') ?: ''),
            'auto_create_listings' => (Settings::get('growth.auto_create_listings') ?? (config('growth.auto_create_listings') ? '1' : '0')) === '1',
            'use_llm' => (Settings::get('growth.use_llm') ?? (config('growth.use_llm') ? '1' : '0')) === '1',
            'min_confidence' => (string) (Settings::get('growth.min_confidence') ?: config('growth.min_confidence', 70)),
            'daily_limit' => (string) (Settings::get('growth.daily_limit') ?: config('growth.daily_limit', 100)),
            'min_rating' => (string) (Settings::get('growth.min_rating') ?: config('growth.min_rating', 3.5)),
            'min_reviews' => (string) (Settings::get('growth.min_reviews') ?: config('growth.min_reviews', 3)),
            'whatsapp_signature' => Settings::get('growth.whatsapp_signature') ?: config('growth.whatsapp_signature', 'Hakan · nisoya.com'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('🛰️ Keşif Motoru & Harita Ağı Kaynağı')
                    ->description('Ajanın Türk işletmelerini hangi harita ve dizin servisinden toplayacağı. OpenStreetMap tamamen ücretsiz ve anahtarsızdır; Google Places ise en zengin fotoğraf ve puan verisini sağlar.')
                    ->schema([
                        Select::make('source')
                            ->label('Aktif Keşif Kaynağı')
                            ->options([
                                'overpass' => '🌍 OpenStreetMap Overpass — ÜCRETSİZ (Önerilen / Kart & Fatura Yok)',
                                'google' => '📍 Google Places API (New) — Zengin Veri (Fotoğraflar, Puanlar, Telefonlar)',
                                'auto' => '⚡ Otomatik Hibrit (Google anahtarı varsa Google, yoksa OpenStreetMap)',
                                'fixture' => '🧪 Demo / Test Modu (Dış ağ çağrısı yok, örnek fixture verisi)',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('OpenStreetMap harita verisi tamamen bedavadır ve Nisoya haritalarıyla %100 uyumludur. Google Places daha fazla iletişim bilgisi (telefon, web sitesi, çalışma saatleri) içerir.'),

                        TextInput::make('google_places_api_key')
                            ->label('Google Places API Anahtarı')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->placeholder('AIzaSy...')
                            ->helperText(new HtmlString('Google Cloud Console üzerinden <strong>"Places API (New)"</strong> servisini etkinleştirerek alabilirsiniz: <a href="https://console.cloud.google.com/google/maps-apis/credentials" target="_blank" rel="noopener" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">Google Cloud Konsolu ↗</a>. Veritabanında şifreli saklanır.')),
                    ]),

                Section::make('🤖 Yapay Zekâ (LLM) Kültürel Tespit & Tersine Katılım (Reverse Onboarding)')
                    ->description('Keşfedilen adayların Türk diasporasına ait olup olmadığını çok-katmanlı kültürel sinyallerle doğrulama ve otomatik dükkan vitrini açma mekanizması.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('use_llm')
                            ->label('Yapay Zekâ (LLM) ile Şüpheli/Sınırda Adayları Doğrula')
                            ->helperText('Açık: Kural tabanlı sözlükte sınırda kalan (? Sınırda) adaylar sitenin merkezi YZ motoru (OpenRouter/NVIDIA/OpenAI) ile analiz edilir. Yabancı esnafın Türk sanılması önlenir.'),

                        Select::make('min_confidence')
                            ->label('Otomatik Onay Güven Eşiği')
                            ->options([
                                '60' => '%60 — Geniş Havuz (Hafif toleranslı)',
                                '70' => '%70 — Dengeli Önerilen (Standart filtre)',
                                '80' => '%80 — Yüksek Güven (Kesin Türk işletmeleri)',
                                '90' => '%90 — Ultra Katı (Yalnızca bariz tabelalar)',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Bu puanın altındaki adaylar Keşif Havuzu\'nda "İnceleme Bekliyor" damgası alır; onaylanmadan davet kuyruğuna düşmez.'),

                        Toggle::make('auto_create_listings')
                            ->label('Yüksek Güvenli Adaylara Otomatik Vitrin Hazırla (Tersine Katılım)')
                            ->columnSpanFull()
                            ->helperText('Açık: Türk olduğu kesinleşen (✓ Türk) adaylar için arka planda anında fotoğraflı, haritalı ve puanlı bir vitrin ilanı (`/sahiplen/{token}`) üretilir ve WhatsApp daveti hazır hale getirilir.'),
                    ]),

                Section::make('🛡️ Kalite Filtreleri & Kota Güvenlik Freni')
                    ->description('Google Places API bütçesini kontrol altında tutmak ve kapanmış ya da düşük puanlı işletmeleri otomatik elemek için koruma kuralları.')
                    ->columns(3)
                    ->schema([
                        Select::make('daily_limit')
                            ->label('Günlük Keşif Kotası')
                            ->options([
                                '50' => '50 İşletme / Gün (Ekonomik)',
                                '100' => '100 İşletme / Gün (Önerilen)',
                                '250' => '250 İşletme / Gün (Hızlı Büyüme)',
                                '0' => 'Sınırsız (Fren Yok)',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Günde bu sayıya ulaşıldığında otonom tarama bir sonraki güne kadar durur.'),

                        Select::make('min_rating')
                            ->label('Minimum Google Puanı')
                            ->options([
                                '0' => 'Filtre Yok (Tümü)',
                                '3.0' => '⭐ 3.0 ve üzeri',
                                '3.5' => '⭐ 3.5 ve üzeri (Önerilen)',
                                '4.0' => '⭐ 4.0 ve üzeri (Seçkin)',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Kötü hizmet veren işletmeler havuza alınmaz.'),

                        Select::make('min_reviews')
                            ->label('Minimum Yorum Sayısı')
                            ->options([
                                '0' => 'Filtre Yok',
                                '1' => 'En az 1 yorum',
                                '3' => 'En az 3 yorum (Önerilen)',
                                '10' => 'En az 10 yorum (Popüler)',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Tabela veya kapanmış hayalet dükkanları eler.'),
                    ]),

                Section::make('💬 WhatsApp Davet & İletişim Şablonu')
                    ->description('Esnafa dükkanını tek tıkla sahiplenmesi için gönderilen WhatsApp mesajının imza ve ton özelleştirmesi.')
                    ->schema([
                        TextInput::make('whatsapp_signature')
                            ->label('Davet Gönderen İmza / Yetkili Adı')
                            ->placeholder('Hakan · nisoya.com')
                            ->helperText('WhatsApp davet metninin ve e-posta mesajlarının en altında gönderici imzası olarak basılır.')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Settings::setMany([
            'growth.source' => $state['source'] ?? 'auto',
            'growth.google_places_api_key' => $state['google_places_api_key'] ?? '',
            'growth.auto_create_listings' => ! empty($state['auto_create_listings']) ? '1' : '0',
            'growth.use_llm' => ! empty($state['use_llm']) ? '1' : '0',
            'growth.min_confidence' => (string) ($state['min_confidence'] ?? '70'),
            'growth.daily_limit' => (string) ($state['daily_limit'] ?? '100'),
            'growth.min_rating' => (string) ($state['min_rating'] ?? '3.5'),
            'growth.min_reviews' => (string) ($state['min_reviews'] ?? '3'),
            'growth.whatsapp_signature' => (string) ($state['whatsapp_signature'] ?? 'Hakan · nisoya.com'),
        ]);

        // Çalışma zamanı yapılandırmasını anında güncelle
        config([
            'growth.source' => $state['source'] ?? 'auto',
            'growth.google_places.api_key' => $state['google_places_api_key'] ?? '',
            'growth.auto_create_listings' => ! empty($state['auto_create_listings']),
            'growth.use_llm' => ! empty($state['use_llm']),
            'growth.min_confidence' => (int) ($state['min_confidence'] ?? 70),
            'growth.daily_limit' => (int) ($state['daily_limit'] ?? 100),
            'growth.min_rating' => (float) ($state['min_rating'] ?? 3.5),
            'growth.min_reviews' => (int) ($state['min_reviews'] ?? 3),
            'growth.whatsapp_signature' => (string) ($state['whatsapp_signature'] ?? 'Hakan · nisoya.com'),
        ]);

        $sourceLabel = match ($state['source'] ?? 'auto') {
            'overpass' => 'OpenStreetMap (Ücretsiz)',
            'google' => 'Google Places API (New)',
            'fixture' => 'Demo verisi',
            default => 'Otomatik Hibrit',
        };

        Notification::make()
            ->title('Büyüme Ajanı Ayarları Kaydedildi ✓')
            ->body("Keşif kaynağı: {$sourceLabel}. Kalite filtreleri, Tersine Katılım ve kota kuralları canlı sitede anında aktifleşti.")
            ->success()
            ->send();
    }

    /** Geriye dönük uyumluluk: eski testEt çağrısını Google Places testine bağlar. */
    public function testEt(): void
    {
        $this->testPlaces();
    }

    /** Google Places API bağlantısını canlı olarak test eder. */
    public function testPlaces(): void
    {
        $state = $this->form->getState();
        $key = (string) ($state['google_places_api_key'] ?? (Settings::get('growth.google_places_api_key') ?: ''));

        if (blank($key)) {
            Notification::make()
                ->title('API Anahtarı Eksik')
                ->body('Google Places bağlantısını test etmeden önce lütfen geçerli bir API anahtarı girin.')
                ->warning()
                ->send();

            return;
        }

        $result = (new GooglePlacesDiscoverySource($key))->probe();

        if ($result['ok']) {
            Notification::make()
                ->title('Google Places Bağlantısı Başarılı ✓')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Google Places Bağlantısı Kurulamadı')
                ->body($result['message']."\n\n💡 İpucu: Google Cloud Console'da 'Places API (New)' servisinin etkinleştirildiğinden ve faturalandırmanın açık olduğundan emin olun.")
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /** OpenStreetMap Overpass sunucusunun canlılığını ve gecikmesini test eder. */
    public function testOverpass(): void
    {
        $result = app(OverpassDiscoverySource::class)->probe();

        if ($result['ok']) {
            Notification::make()
                ->title('OpenStreetMap Overpass Aktif ✓')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Overpass Sunucusuna Ulaşılamadı')
                ->body($result['message']."\n\n💡 İpucu: Overpass genel sunucusu anlık yoğunluk yaşıyor olabilir. Birkaç saniye sonra tekrar deneyin.")
                ->warning()
                ->send();
        }
    }

    /** Panelden tek tıkla seçilen şehir ve meslekte anlık 5 işletmelik canlı keşif yapar. */
    public function hizliKesifBaslat(): void
    {
        $country = strtoupper(trim($this->kesif_ulke ?: 'DE'));
        $city = trim($this->kesif_sehir ?: 'Berlin');
        $tradeKey = trim($this->kesif_meslek ?: 'lokanta');

        $trades = GrowthCatalog::tradesForCountry($country);
        $selectedTrade = null;
        foreach ($trades as $t) {
            if ($t['key'] === $tradeKey) {
                $selectedTrade = $t;
                break;
            }
        }

        if ($selectedTrade === null) {
            $selectedTrade = $trades[0] ?? ['key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant', 'osm' => 'amenity=restaurant'];
        }

        $runner = app(DiscoveryRunner::class);
        $useLlm = (bool) (config('growth.use_llm') || (Settings::get('growth.use_llm') === '1'));

        try {
            $stats = $runner->runForCityTrade($country, $city, $selectedTrade, perQuery: 5, useLlm: $useLlm);

            $discovered = $stats['discovered'];
            $turkish = $stats['turkish'];
            $saved = $stats['saved'];
            $created = $stats['created'] ?? 0;

            $mesaj = "• Taranan İşletme: {$discovered}\n• Doğrulanan Türk Esnafı: {$turkish}\n• Havuza Eklenen/Güncellenen: {$saved}";
            if ($created > 0) {
                $mesaj .= "\n• Otomatik Üretilen Vitrin: {$created} (Tersine Katılım)";
            }

            Notification::make()
                ->title("Hızlı Keşif Tamamlandı ({$city} / {$selectedTrade['tr']}) ✓")
                ->body($mesaj)
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Hızlı Keşif Başarısız')
                ->body('Hata: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function updatedKesifUlke(string $value): void
    {
        $country = strtoupper(trim($value));
        $cities = GrowthCatalog::CITIES[$country] ?? [];
        $this->kesif_sehir = $cities[0] ?? 'Berlin';
    }

    /**
     * @return array<string, string>
     */
    public function getUlkelerProperty(): array
    {
        return [
            'DE' => '🇩🇪 Almanya',
            'US' => '🇺🇸 ABD (New Jersey, New York...)',
            'KZ' => '🇰🇿 Kazakistan',
            'KG' => '🇰🇬 Kırgızistan',
            'UZ' => '🇺🇿 Özbekistan',
            'TH' => '🇹🇭 Tayland',
            'KH' => '🇰🇭 Kamboçya',
        ];
    }

    /**
     * @return list<string>
     */
    public function getSehirlerProperty(): array
    {
        $country = strtoupper(trim($this->kesif_ulke ?: 'DE'));

        return GrowthCatalog::CITIES[$country] ?? ['Berlin'];
    }

    /**
     * @return list<array{key: string, tr: string, en: string, osm: string, local?: string}>
     */
    public function getMesleklerProperty(): array
    {
        $country = strtoupper(trim($this->kesif_ulke ?: 'DE'));

        return GrowthCatalog::tradesForCountry($country);
    }

    /** Sayfa başlığında canlı telemetri ve dönüşüm hunisi metriklerini sunar. */
    public function getMetriklerProperty(): array
    {
        $servis = app(BuyumeMetrikleriServisi::class);
        $ozet = $servis->ozet();

        $toplam = $ozet['toplam_kesif'];
        $turk = $ozet['turk_isletmeler'];
        $turkOrani = $toplam > 0 ? round(($turk / $toplam) * 100, 1) : 0.0;

        $source = (string) (Settings::get('growth.source') ?: config('growth.source', 'auto'));
        $hasGoogleKey = filled(Settings::get('growth.google_places_api_key') ?: config('growth.google_places.api_key'));

        return [
            'toplam_kesif' => $toplam,
            'turk_isletmeler' => $turk,
            'turk_orani' => $turkOrani,
            'hazirlanan_vitrinler' => $ozet['hazirlanan_vitrinler'],
            'sahiplenilen_vitrinler' => $ozet['sahiplenilen_vitrinler'],
            'donusum_orani' => $ozet['donusum_orani'],
            'onay_bekleyen_hamleler' => $ozet['onay_bekleyen_hamleler'],
            'source' => $source,
            'has_google_key' => $hasGoogleKey,
        ];
    }
}
