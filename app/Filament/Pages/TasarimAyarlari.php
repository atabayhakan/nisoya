<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Http\Controllers\TemaOzellestiriciController;
use App\Support\Settings;
use App\Support\Tema;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Sitenin görünümü: tema seçimi, hazır paketler ve ince ayarlar.
 *
 * ---------------------------------------------------------------------------
 * FABRİKA VARSAYILANI TEK YERDE ({@see VARSAYILANLAR})
 *
 * "Varsayılana Sıfırla" eskiden `secPreset('eski')` çağırıyordu; o paket
 * `border_radius='soft'` ve `glassmorphism='0'` yazıyor, oysa projenin her
 * yerindeki fabrika varsayılanı `modern` + cam AÇIK. Yani kurtarma refleksi
 * olması gereken düğme, durumu sessizce daha da kaydırıyordu.
 *
 * ---------------------------------------------------------------------------
 * RENK TEK KONTROLDÜR
 *
 * Klasik iskelet hem `brand-theme` (hazır aile: `gorunum.marka_rengi`) hem
 * `tasarim-theme` (serbest hex: `gorunum.primary_color`) basıyor ve ikincisi
 * yalnız varsayılandan FARKLIYSA devreye giriyor. İkisini bağımsız bırakmak
 * ölü kontrol üretir: sahip sitedeki panelden "mor" seçip buradan yeşili
 * tıklarsa site mor kalır, panel yeşil gösterir.
 *
 * {@see TemaOzellestiriciController} bu kuralı zaten
 * uyguluyordu (biri yazılırken diğeri varsayılana çekilir); bu sayfa
 * uygulamıyordu. Artık uyguluyor.
 */
class TasarimAyarlari extends Page
{
    use RestrictsToAdmins;

    /** Fabrika varsayılanı — sıfırlamanın tek doğruluk kaynağı. */
    public const VARSAYILANLAR = [
        'gorunum.tasarim_modu' => 'eski',
        'gorunum.primary_color' => '#059669',
        'gorunum.marka_rengi' => 'emerald',
        'gorunum.font_family' => 'sans',
        'gorunum.border_radius' => 'modern',
        'gorunum.glassmorphism' => '1',
        'gorunum.smooth_animations' => '1',
        // Klasik-only ayarların aksine bu üçü her iki temada da geçerli
        // (bkz. TemaJetonlari::JETONLAR) ama sıfırlama tek düğmeden hepsini
        // kapsar — sahibin "her şeyi fabrikaya döndür" beklentisiyle tutarlı.
        'gorunum.logo_animasyon' => '0',
        'gorunum.logo_rengi' => '#059669',
        'gorunum.logo_yazi_tipi' => 'indie-flower',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik & Tasarım (CMS)';

    /**
     * Menü adı = sayfa başlığı.
     *
     * Sayfanın ÜÇ ayrı adı vardı: menüde "Tasarım Modu", başlıkta "2027
     * Tasarım Komuta Merkezi", sitedeki panelden yönlendirmede "Tasarım
     * Ayarları". Sahip birinde okuduğu adı diğerinde bulamıyordu. Panelin
     * geri kalanında böyle bir sapma yok (Duyuru Bandı, Hero Yöneticisi…).
     */
    protected static ?string $navigationLabel = 'Görünüm ve Tema';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.tasarim-ayarlari';

    public string $aktifTema = 'klasik'; // klasik, vitrin — view ağacını seçer (bkz. App\Support\Tema)

    public string $aktifMod = 'eski'; // eski, yeni, obsidian, nordic — yalnız klasik temada hükümlü

    public string $primaryColor = '#059669';

    public string $fontFamily = 'sans'; // sans, serif, inter, outfit

    public string $borderRadius = 'modern'; // sharp, soft, modern, pill

    public bool $glassmorphism = true;

    public bool $smoothAnimations = true;

    public bool $logoAnimasyon = false;

    public string $logoRenk = '#059669';

    public string $logoFont = 'indie-flower'; // indie-flower, dancing-script — bkz. TemaJetonlari::EL_YAZISI_FONTLAR

    public function getTitle(): string
    {
        return 'Görünüm ve Tema';
    }

    public function mount(): void
    {
        $this->hydrateFromSettings();
    }

    /** Bileşen durumunu kalıcı ayarlardan (tek doğruluk kaynağı) yükler. */
    private function hydrateFromSettings(): void
    {
        $this->aktifTema = Settings::get('gorunum.tema', 'klasik');
        $this->aktifMod = Settings::get('gorunum.tasarim_modu', 'eski');
        $this->primaryColor = Settings::get('gorunum.primary_color', '#059669');
        $this->fontFamily = Settings::get('gorunum.font_family', 'sans');
        $this->borderRadius = Settings::get('gorunum.border_radius', 'modern');
        $this->glassmorphism = Settings::get('gorunum.glassmorphism', '1') === '1';
        $this->smoothAnimations = Settings::get('gorunum.smooth_animations', '1') === '1';
        $this->logoAnimasyon = Settings::get('gorunum.logo_animasyon', '0') === '1';
        $this->logoRenk = Settings::get('gorunum.logo_rengi', '#059669');
        $this->logoFont = Settings::get('gorunum.logo_yazi_tipi', 'indie-flower');
    }

    /**
     * Tema geçişi (Vitrin projesi): view ağacını değiştirir, deploy gerekmez.
     * Geri dönüş de tek tık — klasik dosyalara hiç dokunulmadığı için
     * `klasik` seçildiğinde site birebir eski haline döner. tasarim_modu
     * preset'leri yalnız klasik temada hükümlüdür (App\Support\Tema).
     */
    public function secTema(string $tema): void
    {
        if (! in_array($tema, Tema::TEMALAR, true)) {
            return;
        }

        Settings::setMany(['gorunum.tema' => $tema]);

        // Adminin kendi bayat ?tema_onizleme oturum bayrağı kalıcı ayarın
        // ÖNÜNE geçer (Tema::aktif sırası) — temizlenmezse bildirim "klasiğe
        // dönüldü" derken admin hâlâ önizlemedeki temayı görürdü (P0 inceleme
        // bulgusu #2). Panel ve site aynı oturumu paylaşır.
        session()->forget('tema_onizleme');

        $this->hydrateFromSettings();

        Notification::make()
            ->title($tema === 'vitrin' ? 'Vitrin teması etkinleştirildi' : 'Klasik temaya dönüldü')
            ->body($tema === 'vitrin'
                ? 'Vitrin karşılığı henüz hazırlanmamış sayfalar klasik görünümle sunulmaya devam eder.'
                : 'Site birebir klasik görünümüne döndü; Tasarım Modu preset\'leri yeniden hükümlü.')
            ->success()
            ->send();
    }

    /**
     * Hazır paketler — TEK KAYNAK.
     *
     * -----------------------------------------------------------------------
     * AÇIKLAMALAR ARTIK VAAT DEĞİL, ÖZET
     *
     * Eski açıklamalar kodun yapmadığı şeyleri anlatıyordu (2026-08-06
     * denetimi, beş ajan bağımsız olarak buldu):
     *   · "Mat gece siyahı zemin, neon zümrüt ışımaları" → obsidian zemine
     *     HİÇ dokunmuyor (bkz. tasarim-theme: $stone50'de obsidian dalı yok),
     *     mühür rengi kehribar.
     *   · "0.5px zarif hatlar, İskandinav tipografi" → öyle bir kenarlık yok,
     *     font Zümrüt ile birebir aynı.
     *   · "Instrument Serif italik" → hiçbir yerde font-style basılmıyor.
     *
     * Sahip bu vaatlere göre seçim yapıp karşılığını bulamıyordu. Açıklamalar
     * artık paketin gerçekten yazdığı beş değerin özeti.
     *
     * @var array<string, array{ad: string, ozet: string, ayarlar: array<string, string>}>
     */
    public const PRESETLER = [
        'eski' => [
            'ad' => 'Zümrüt Klasik',
            'ozet' => 'Zümrüt yeşili vurgu, Instrument Sans, yumuşak köşeler. Cam efekti kapalı.',
            'ayarlar' => [
                'gorunum.tasarim_modu' => 'eski',
                'gorunum.primary_color' => '#059669',
                'gorunum.font_family' => 'sans',
                'gorunum.border_radius' => 'soft',
                'gorunum.glassmorphism' => '0',
                'gorunum.smooth_animations' => '1',
            ],
        ],
        'yeni' => [
            'ad' => 'Neo-Craft',
            'ozet' => 'Koyu yeşil vurgu, Instrument Serif, sıcak krem zemin. Cam efekti açık.',
            'ayarlar' => [
                'gorunum.tasarim_modu' => 'yeni',
                'gorunum.primary_color' => '#0f5c42',
                'gorunum.font_family' => 'serif',
                'gorunum.border_radius' => 'modern',
                'gorunum.glassmorphism' => '1',
                'gorunum.smooth_animations' => '1',
            ],
        ],
        'obsidian' => [
            'ad' => 'Obsidyen',
            'ozet' => 'Parlak zümrüt vurgu, kehribar mührü, koyu moda kilitli. Cam efekti açık.',
            'ayarlar' => [
                'gorunum.tasarim_modu' => 'obsidian',
                'gorunum.primary_color' => '#10b981',
                // Eskiden 'inter' yazıyordu; o aile hiçbir yerden yüklenmediği
                // için bu hazır ayarı uygulayan sahip sessizce sistem sans'ına
                // düşüyordu. Artık gerçekten yüklü olan aile yazılır.
                'gorunum.font_family' => 'sans',
                'gorunum.border_radius' => 'modern',
                'gorunum.glassmorphism' => '1',
                'gorunum.smooth_animations' => '1',
            ],
        ],
        'nordic' => [
            'ad' => 'Nordik',
            'ozet' => 'Koyu lacivert vurgu, açık gri zemin, kapsül köşeler. Cam efekti kapalı.',
            'ayarlar' => [
                'gorunum.tasarim_modu' => 'nordic',
                'gorunum.primary_color' => '#0f172a',
                // Eskiden 'outfit' yazıyordu — bkz. obsidian'daki not.
                'gorunum.font_family' => 'sans',
                'gorunum.border_radius' => 'pill',
                'gorunum.glassmorphism' => '0',
                'gorunum.smooth_animations' => '1',
            ],
        ],
    ];

    public function secPreset(string $preset): void
    {
        if (! isset(self::PRESETLER[$preset])) {
            return;
        }

        $this->yaz(self::PRESETLER[$preset]['ayarlar']);

        Notification::make()
            ->title(self::PRESETLER[$preset]['ad'].' uygulandı')
            ->body('Değişiklik canlı sitede anında geçerli.')
            ->success()
            ->send();
    }

    public function kaydetCustom(): void
    {
        $this->yaz([
            'gorunum.tasarim_modu' => $this->aktifMod,
            'gorunum.primary_color' => $this->primaryColor,
            'gorunum.font_family' => $this->fontFamily,
            'gorunum.border_radius' => $this->borderRadius,
            'gorunum.glassmorphism' => $this->glassmorphism ? '1' : '0',
            'gorunum.smooth_animations' => $this->smoothAnimations ? '1' : '0',
            'gorunum.logo_animasyon' => $this->logoAnimasyon ? '1' : '0',
            'gorunum.logo_rengi' => $this->logoRenk,
            'gorunum.logo_yazi_tipi' => $this->logoFont,
        ]);

        Notification::make()
            ->title('Görünüm ayarları kaydedildi')
            ->body('Değişiklik canlı sitede anında geçerli.')
            ->success()
            ->send();
    }

    public function sifirla(): void
    {
        $this->yaz(self::VARSAYILANLAR);

        Notification::make()
            ->title('Varsayılana dönüldü')
            ->body('Renk, yazı tipi, köşe ve efektler fabrika ayarına döndü. Tema seçimi değişmedi.')
            ->success()
            ->send();
    }

    /**
     * Görünüm ayarlarını yazan TEK kapı.
     *
     * `marka_rengi` burada varsayılana çekilir: hazır aile ile serbest hex
     * aynı CSS değişkenlerini basıyor ve ikincisi birincisini eziyor, o yüzden
     * ikisinin aynı anda "seçili" olması ölü kontrol üretir. Aynı kural
     * {@see TemaOzellestiriciController} içinde de var —
     * bu sayfa onu atlıyordu ve panel siteninkinden farklı bir renk
     * gösterebiliyordu.
     *
     * @param  array<string, string>  $ayarlar
     */
    private function yaz(array $ayarlar): void
    {
        if (isset($ayarlar['gorunum.primary_color']) && ! isset($ayarlar['gorunum.marka_rengi'])) {
            $ayarlar['gorunum.marka_rengi'] = 'emerald';
        }

        Settings::setMany($ayarlar);

        $this->hydrateFromSettings();
    }
}
