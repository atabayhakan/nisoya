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
