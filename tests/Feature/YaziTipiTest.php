<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Yazı tipi yükleme bekçisi (2026-07-28).
 *
 * BULUNAN HATA: site, tasarım sisteminin ilan ettiği yazı tipiyle hiç
 * çizilmiyordu. vite.config.js üç aileyi self-host ediyor, `npm run build`
 * woff2 dosyalarını ve `fonts-*.css`i üretiyor, deploy hepsini sunucuya
 * kopyalıyordu — ve hiçbir iskelet Laravel'in `Vite::fonts()` çağrısını
 * yapmadığı için tarayıcıya tek bir @font-face bile ulaşmıyordu. Ziyaretçi
 * siteyi kendi işletim sistemi fontuyla görüyordu.
 *
 * Sessizdi: CSS'te `--font-sans: 'Instrument Sans', ui-sans-serif, ...`
 * yazıyordu ve tarayıcı sessizce yedeğe düşüyordu. Hiçbir hata, hiçbir
 * konsol uyarısı. Tarayıcı ölçümüyle yakalandı: "Instrument Sans" ile
 * `ui-sans-serif` aynı metni piksel piksel aynı genişlikte çiziyordu.
 *
 * İKİNCİ HATA (aynı sınıf): `font-bold` (700) sitede 155 yerde kullanılıyordu
 * ama vite.config.js yalnız 400/500/600 yüklüyordu. Tarayıcı eksik ağırlığı
 * SENTEZLER — 600'ü yatay şişirir. Sonuç "sahte kalın": bulanık kenarlar,
 * bozuk harf aralıkları. Bu da sessizdir.
 *
 * Bu dosya iki hatayı da makineyle imkânsız kılar.
 */
class YaziTipiTest extends TestCase
{
    /** Tailwind ağırlık yardımcısı => CSS font-weight sayısı. */
    private const AGIRLIKLAR = [
        'font-thin' => 100,
        'font-extralight' => 200,
        'font-light' => 300,
        'font-normal' => 400,
        'font-medium' => 500,
        'font-semibold' => 600,
        'font-bold' => 700,
        'font-extrabold' => 800,
        'font-black' => 900,
    ];

    /**
     * Her temanın iskeleti ve misafir iskeleti font bileşenini basmalı.
     *
     * `@vite([...])` font stil sayfasına <link> BASMAZ — o dosya bir Vite
     * "entry"si değil, yan çıktıdır. Onu sayfaya sokan tek şey budur.
     */
    public function test_tum_iskeletler_font_bilesenini_basar(): void
    {
        $iskeletler = [
            'resources/views/components/layouts/app.blade.php',
            'resources/views/components/layouts/guest.blade.php',
            'resources/views/vitrin/components/layouts/app.blade.php',
            'resources/views/vitrin/components/layouts/guest.blade.php',
        ];

        foreach ($iskeletler as $iskelet) {
            $this->assertStringContainsString(
                'x-layout-fonts',
                File::get(base_path($iskelet)),
                "{$iskelet} font bileşenini basmıyor — bu iskeletten dönen sayfalar "
                .'sistem fontuyla çizilir ve hiçbir hata vermez.'
            );
        }
    }

    /**
     * Bileşen gerçekten Vite::fonts() çağırmalı.
     *
     * Bileşenin var olması yetmez; içi boşalırsa ya da başka bir şey basarsa
     * hata sessizce geri gelir.
     */
    public function test_font_bileseni_vite_fonts_cagirir(): void
    {
        $this->assertStringContainsString(
            'Vite::fonts(',
            File::get(base_path('resources/views/components/layout-fonts.blade.php')),
            'Font bileşeni Vite::fonts() çağırmıyor — @font-face kuralları sayfaya hiç ulaşmaz.'
        );
    }

    /**
     * ASIL BEKÇİ: markup'ta kullanılan her ağırlık gerçekten yüklenmeli.
     *
     * Yüklenmeyen bir ağırlık hata vermez; tarayıcı onu sentezler ve sonuç
     * sahte kalındır. Bu test "kullanılan" ile "yüklenen" kümelerini
     * karşılaştırarak o sessizliği bozar.
     */
    public function test_markupta_kullanilan_her_agirlik_yuklenir(): void
    {
        $vite = File::get(base_path('vite.config.js'));

        foreach ([
            'Instrument Sans' => 'klasik',
            'Plus Jakarta Sans' => 'vitrin',
        ] as $aile => $tema) {
            $yuklenen = $this->yuklenenAgirliklar($vite, $aile);
            $kullanilan = $this->kullanilanAgirliklar($tema);

            $eksik = array_diff($kullanilan, $yuklenen);

            $this->assertSame([], array_values($eksik), sprintf(
                '%s temasında %s ağırlığı markup\'ta kullanılıyor ama %s için yüklenmiyor. '
                .'Tarayıcı eksik ağırlığı sentezler (sahte kalın). Ya vite.config.js\'e ağırlığı ekleyin '
                .'ya da markup\'ta yüklü bir ağırlığa geçin. Yüklenen: %s.',
                $tema,
                implode('/', $eksik),
                $aile,
                implode('/', $yuklenen)
            ));
        }
    }

    /**
     * vite.config.js'te bir ailenin `weights` dizisi.
     *
     * @return list<int>
     */
    private function yuklenenAgirliklar(string $vite, string $aile): array
    {
        $desen = '/bunny\(\s*[\'"]'.preg_quote($aile, '/').'[\'"].*?weights:\s*\[([0-9,\s]+)\]/s';

        $this->assertMatchesRegularExpression($desen, $vite, "vite.config.js '{$aile}' ailesini self-host etmiyor.");

        preg_match($desen, $vite, $m);

        return array_map('intval', array_map('trim', explode(',', trim($m[1]))));
    }

    /**
     * Bir temanın Blade dosyalarında geçen ağırlıklar.
     *
     * Vitrin dosyaları klasik taramadan DIŞLANIR: iki tema farklı yazı tipi
     * kullanır, ağırlık kümeleri birbirini bağlamaz.
     *
     * @return list<int>
     */
    private function kullanilanAgirliklar(string $tema): array
    {
        $vitrinMi = fn (string $yol) => str_contains(str_replace('\\', '/', $yol), '/vitrin/');

        $bulunan = [];

        foreach (File::allFiles(resource_path('views')) as $dosya) {
            if (! str_ends_with($dosya->getFilename(), '.blade.php')) {
                continue;
            }

            // Yönetim paneli Filament'in kendi yazı tipini kullanır, site
            // ailesine bağlı değil — kapsam dışı.
            if (str_contains(str_replace('\\', '/', $dosya->getPathname()), '/filament/')) {
                continue;
            }

            if ($vitrinMi($dosya->getPathname()) !== ($tema === 'vitrin')) {
                continue;
            }

            // Yorumlar AYIKLANIR. Bir sınıf adından yorum içinde söz etmek
            // ("font-extrabold değil, çünkü...") onu kullanmak değildir; ham
            // metni taramak bu testin kendi açıklama notunu ihlal saymasına
            // yol açıyordu.
            $icerik = preg_replace('/\{\{--.*?--\}\}|<!--.*?-->/s', '', $dosya->getContents()) ?? '';

            foreach (self::AGIRLIKLAR as $sinif => $sayi) {
                if (preg_match('/\b'.preg_quote($sinif, '/').'\b/', $icerik)) {
                    $bulunan[$sayi] = true;
                }
            }
        }

        $agirliklar = array_keys($bulunan);
        sort($agirliklar);

        return $agirliklar;
    }
}
