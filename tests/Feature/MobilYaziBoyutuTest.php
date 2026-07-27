<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * iOS Safari odak-zoom'u (2026-07-27).
 *
 * iOS, yazı boyutu 16px'in ALTINDA olan bir metin/seçim alanına dokunulduğunda
 * sayfayı otomatik büyütür ve odak kalkınca geri küçültmez. Tarama sırasında
 * sitenin en çok kullanılan kontrolleri (ana arama kutusu, ülke/kategori/tip/
 * sıralama seçicileri, tüm auth formları) bu eşiğin altındaydı.
 *
 * Bu bekçi iki şeyi birden mühürler: (1) düzeltmenin kendisi silinmesin,
 * (2) yerine "kolay" ama YANLIŞ çözüm konmasın — user-scalable=no /
 * maximum-scale ile pinch-zoom'u kapatmak zoom'u susturur ama görme
 * güçlüğü olan kullanıcının sayfayı büyütmesini de engeller (ve iOS bunu
 * zaten yok sayar).
 */
class MobilYaziBoyutuTest extends TestCase
{
    private function appCss(): string
    {
        return File::get(resource_path('css/app.css'));
    }

    public function test_dokunmatik_cihazlarda_form_alanlari_16px_e_yukseltilir(): void
    {
        $css = $this->appCss();

        $this->assertStringContainsString(
            '@media (pointer: coarse)',
            $css,
            'iOS odak-zoom düzeltmesi kaldırılmış. Genişlik değil GİRDİ TÜRÜ hedeflenmeli: '
            .'max-width kullanmak 1024px genişliğindeki iPad\'i kaçırır.'
        );

        // Kuralın gövdesini yakala ve gerçekten 16px verdiğini doğrula.
        preg_match('/@media \(pointer: coarse\)\s*\{(.+?)\n\}/s', $css, $m);
        $this->assertNotEmpty($m, 'pointer: coarse bloğu okunamadı.');
        $blok = $m[1];

        $this->assertStringContainsString('font-size: 16px', $blok);

        // Zoom'u tetikleyen alan türlerinin hepsi kapsanmalı.
        foreach (['select', 'textarea', "input[type='text']", "input[type='search']", "input[type='email']", "input[type='password']", "input[type='number']", 'input:not([type])'] as $secici) {
            $this->assertStringContainsString($secici, $blok, "Zoom tetikleyen alan türü kapsam dışı: {$secici}");
        }

        // Buton/onay kutusu görünümü değişmemeli — bunlar zaten zoom tetiklemez.
        foreach (["input[type='checkbox']", "input[type='submit']", "input[type='button']", "input[type='file']"] as $disarida) {
            $this->assertStringNotContainsString($disarida, $blok, "Zoom tetiklemeyen tür gereksiz yere kapsama alınmış: {$disarida}");
        }
    }

    public function test_hicbir_sayfa_pinch_zoomu_kapatmaz(): void
    {
        $ihlaller = [];

        foreach (File::allFiles(resource_path('views')) as $dosya) {
            if (! str_ends_with($dosya->getFilename(), '.blade.php')) {
                continue;
            }
            $icerik = File::get($dosya->getPathname());
            if (! str_contains($icerik, 'name="viewport"')) {
                continue;
            }
            if (preg_match('/user-scalable\s*=\s*no|maximum-scale\s*=\s*1/i', $icerik)) {
                $ihlaller[] = str_replace('\\', '/', $dosya->getRelativePathname());
            }
        }

        $this->assertSame([], $ihlaller,
            'Pinch-zoom kapatılmış. iOS odak-zoom\'unun çözümü bu DEĞİL: alanın gerçekten '
            .'16px olması (bkz. app.css @media (pointer: coarse)). user-scalable=no görme '
            .'güçlüğü olan kullanıcıyı sayfayı büyütemez hâle getirir.'
        );
    }
}
