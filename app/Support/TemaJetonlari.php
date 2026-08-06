<?php

namespace App\Support;

/**
 * Görünüm ayarlarının TEK GERÇEK KAYNAĞI: hangi ayar anahtarı hangi temada
 * yaşar ve onu hangi bileşen okur.
 *
 * NEDEN VAR: 2026-07-28 denetiminde şu bulundu — admin panelindeki görünüm
 * kontrollerinin tamamı (marka rengi, ana renk, font, köşe, cam, animasyon)
 * Vitrin teması aktifken HİÇBİR ŞEY YAPMIYORDU. Sebep sessiz ve masumdu:
 * klasik iskelet <x-brand-theme /> + <x-tasarim-theme /> basıyor, Vitrin
 * iskeleti ise yalnız <x-vitrin-theme /> basıyor ve o bileşen hiçbir ayar
 * okumuyordu. Panelde hiçbir uyarı yoktu; sahip düğmeleri çeviriyor, site
 * değişmiyordu.
 *
 * Bu sınıf o hata sınıfını YAPISAL olarak imkânsız kılar: her kontrol burada
 * "hangi temalarda geçerli" ve "hangi dosya okuyor" diye beyan edilir,
 * TemaJetonlariTest de beyanın doğruluğunu makineyle doğrular — okuyan dosya
 * anahtarı gerçekten okuyor mu ve o dosya ilgili temanın iskeletinde
 * gerçekten basılıyor mu.
 *
 * Yeni bir görünüm ayarı eklerken buraya da eklenmezse özelleştiricide
 * görünmez; yanlış beyan edilirse test kırılır.
 */
final class TemaJetonlari
{
    /**
     * @var array<string, array{etiket: string, temalar: array<int, string>, okuyan: string, bilesen: string}>
     */
    public const JETONLAR = [
        'gorunum.marka_rengi' => [
            'etiket' => 'Marka rengi (hazır aile)',
            'temalar' => ['klasik'],
            'okuyan' => 'resources/views/components/brand-theme.blade.php',
            'bilesen' => 'x-brand-theme',
            'bilesen_dosyasi' => 'resources/views/components/brand-theme.blade.php',
        ],
        'gorunum.primary_color' => [
            'etiket' => 'Özel birincil renk',
            'temalar' => ['klasik'],
            'okuyan' => 'resources/views/components/tasarim-theme.blade.php',
            'bilesen' => 'x-tasarim-theme',
            'bilesen_dosyasi' => 'resources/views/components/tasarim-theme.blade.php',
        ],
        'gorunum.font_family' => [
            'etiket' => 'Yazı tipi',
            'temalar' => ['klasik'],
            'okuyan' => 'resources/views/components/tasarim-theme.blade.php',
            'bilesen' => 'x-tasarim-theme',
            'bilesen_dosyasi' => 'resources/views/components/tasarim-theme.blade.php',
        ],
        'gorunum.border_radius' => [
            'etiket' => 'Köşe yuvarlatma',
            'temalar' => ['klasik'],
            'okuyan' => 'resources/views/components/tasarim-theme.blade.php',
            'bilesen' => 'x-tasarim-theme',
            'bilesen_dosyasi' => 'resources/views/components/tasarim-theme.blade.php',
        ],
        'gorunum.glassmorphism' => [
            'etiket' => 'Cam efekti',
            'temalar' => ['klasik'],
            'okuyan' => 'resources/views/components/tasarim-theme.blade.php',
            'bilesen' => 'x-tasarim-theme',
            'bilesen_dosyasi' => 'resources/views/components/tasarim-theme.blade.php',
        ],
        'gorunum.smooth_animations' => [
            'etiket' => 'Akıcı animasyonlar',
            'temalar' => ['klasik'],
            'okuyan' => 'resources/views/components/tasarim-theme.blade.php',
            'bilesen' => 'x-tasarim-theme',
            'bilesen_dosyasi' => 'resources/views/components/tasarim-theme.blade.php',
        ],
        'gorunum.vitrin_aksan' => [
            'etiket' => 'Vurgu rengi',
            'temalar' => ['vitrin'],
            // Anahtar bu sınıfın kendi içinde okunur (vitrinAksani); bileşen
            // ona dolaylı olarak bağlanır. Bekçi testi zincirin üç halkasını
            // da ayrı ayrı doğrular.
            'okuyan' => 'app/Support/TemaJetonlari.php',
            'bilesen' => 'x-vitrin-theme',
            'bilesen_dosyasi' => 'resources/views/components/vitrin-theme.blade.php',
        ],
    ];

    /**
     * Seçilebilir yazı tiplerinin TEK KAYNAĞI.
     *
     * NEDEN BURADA: panelde uzun süre 'inter' ve 'outfit' seçenekleri durdu;
     * ikisi de hiçbir yerden YÜKLENMİYORDU (vite.config.js yalnız Instrument
     * Sans, Instrument Serif ve Plus Jakarta Sans'ı self-host ediyor). Sahip
     * seçiyor, kaydediliyor, site sessizce sistem sans'ına düşüyordu. Üstelik
     * "obsidian" ve "nordic" hazır ayarları o ölü değerleri kendiliğinden
     * yazıyordu. Liste tek yerde tutulur ve TemaJetonlariTest her seçeneğin
     * gerçekten self-host edildiğini vite.config.js'ten doğrular.
     *
     * @var array<string, array{etiket: string, aile: string, css: string}>
     */
    public const FONTLAR = [
        'sans' => [
            'etiket' => 'Instrument Sans (varsayılan)',
            'aile' => 'Instrument Sans',
            'css' => "'Instrument Sans', ui-sans-serif, system-ui, sans-serif",
        ],
        'serif' => [
            'etiket' => 'Instrument Serif',
            'aile' => 'Instrument Serif',
            'css' => "'Instrument Serif', Georgia, 'Times New Roman', serif",
        ],
    ];

    /** Geçerli font anahtarı mı? Değilse varsayılana düşülür. */
    public static function fontCss(?string $anahtar): string
    {
        return (self::FONTLAR[$anahtar] ?? self::FONTLAR['sans'])['css'];
    }

    /**
     * Panel açılır menüsü için anahtar => etiket.
     *
     * @return array<string, string>
     */
    public static function fontSecenekleri(): array
    {
        return array_map(fn (array $f) => $f['etiket'], self::FONTLAR);
    }

    /**
     * Köşe yuvarlatma ölçeği — TEK KAYNAK.
     *
     * -----------------------------------------------------------------------
     * NEDEN BURAYA TAŞINDI (2026-08-06)
     *
     * Ölçek iki yerde ayrı ayrı yazılıydı: gerçek CSS'i basan
     * `components/tasarim-theme.blade.php` ve paneldeki "canlı önizleme".
     * Panelin yanında tam da bunu yasaklayan bir yorum duruyordu — "önizleme,
     * sitenin gerçekte uygulayacağı CSS'in AYNISINI kullanır; ayrı bir eşleme
     * tutmak ikisinin sessizce ayrışmasına yol açardı" — ama eşleme yine de
     * kopyalanmıştı ve ÇOKTAN AYRIŞMIŞTI:
     *
     *   modern → önizleme 14px, site 12px
     *   pill   → önizleme 24px, site 18px
     *
     * Yani "anlık önizleme" rozetiyle sunulan kutu, yanlış köşe gösteriyordu.
     * Yorum doğruyu söylüyordu, kod uymuyordu; artık uyuyor.
     *
     * `modern` bilerek Tailwind varsayılanlarına eşittir (dokunulmamış siteler
     * için no-op); diğerleri belirgin şekilde sapar. `rounded-full` etkilenmez.
     *
     * @return array{lg: string, xl: string, '2xl': string, '3xl': string}
     */
    public static function koseOlcegi(?string $anahtar): array
    {
        return match ($anahtar) {
            'sharp' => ['lg' => '2px', 'xl' => '3px', '2xl' => '4px', '3xl' => '6px'],
            'soft' => ['lg' => '6px', 'xl' => '8px', '2xl' => '10px', '3xl' => '14px'],
            'pill' => ['lg' => '14px', 'xl' => '18px', '2xl' => '24px', '3xl' => '32px'],
            default => ['lg' => '.5rem', 'xl' => '.75rem', '2xl' => '1rem', '3xl' => '1.5rem'],
        };
    }

    /**
     * Panel açılır menüsü için köşe seçenekleri.
     *
     * Etiketlerde PİKSEL YAZILMAZ. Eskiden "Modern (14px)" gibi yazıyordu ve
     * üç ayrı sorun taşıyordu: (1) 14px hiçbir katmanla eşleşmiyordu, (2) her
     * etiket başka bir katmandan (lg/xl/2xl) sayı almıştı, (3) ölçek değişince
     * etiket sessizce yalancı olurdu. Sayı vermeyen ad, yanlış sayı veren
     * addan iyidir.
     *
     * @return array<string, string>
     */
    public static function koseSecenekleri(): array
    {
        return [
            'sharp' => 'Keskin',
            'soft' => 'Yumuşak',
            'modern' => 'Modern (varsayılan)',
            'pill' => 'Kapsül',
        ];
    }

    /** Temaların iskelet dosyaları — bekçi testi bileşenin gerçekten basıldığını buradan doğrular. */
    public const ISKELETLER = [
        'klasik' => 'resources/views/components/layouts/app.blade.php',
        'vitrin' => 'resources/views/vitrin/components/layouts/app.blade.php',
    ];

    /** Bu anahtar verilen temada gerçekten bir şey yapıyor mu? */
    public static function gecerliMi(string $anahtar, string $tema): bool
    {
        return in_array($tema, self::JETONLAR[$anahtar]['temalar'] ?? [], true);
    }

    /**
     * Verilen temada geçerli olan anahtarlar.
     *
     * @return array<int, string>
     */
    public static function temaninJetonlari(string $tema): array
    {
        return array_keys(array_filter(
            self::JETONLAR,
            fn (array $j) => in_array($tema, $j['temalar'], true),
        ));
    }

    /**
     * Seçili Vitrin vurgu rampası. Geçersiz/boş değer varsayılana düşer —
     * tema motoru siteyi asla düşürmez disiplininin aynısı.
     *
     * @return array{label: string, hex: string, acik: array<int, string>, koyu: array<int, string>}
     */
    public static function vitrinAksani(?string $ad = null): array
    {
        /** @var array<string, array{label: string, hex: string, acik: array<int, string>, koyu: array<int, string>}> $rampalar */
        $rampalar = config('vitrin_accents', []);
        $ad ??= Settings::get('gorunum.vitrin_aksan', 'deniz');

        return $rampalar[$ad] ?? $rampalar['deniz'];
    }

    /** Geçerli Vitrin aksan adları. */
    public static function vitrinAksanAdlari(): array
    {
        return array_keys(config('vitrin_accents', []));
    }
}
