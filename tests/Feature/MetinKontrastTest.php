<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Metin kontrastı bekçisi (2026-07-28).
 *
 * Bu dosyadaki her yasak, tarayıcıda ÖLÇÜLMÜŞ bir orandır — tahmin değil.
 * Ölçüm yöntemi: canvas ile gerçek sRGB'ye çevir (Tailwind v4 oklch/oklab
 * basar, elle ayrıştırmak yanlış sonuç verir), WCAG 2.1 bağıl parlaklık
 * formülünü uygula, metnin arkasındaki İLK opak zemini yukarı doğru çözerek
 * bul.
 *
 * WCAG AA eşiği: normal metin 4.5, büyük metin (>=24px, ya da >=18.66px ve
 * kalın) 3.0.
 *
 * Neden statik bir test: kontrast ancak tarayıcıda ölçülebilir, ama HATA
 * kaynakta yapılır. Ölçümü bir kez yapıp sonucu yasak sınıf kombinasyonuna
 * çevirmek, CI'ın tarayıcı çalıştırmadan aynı hatayı yakalamasını sağlar.
 * Yasak listesi ölçümün donmuş halidir; palet değişirse yeniden ölçülmeli.
 */
class MetinKontrastTest extends TestCase
{
    /**
     * [regex, ölçülen oran, neden, önerilen].
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private const YASAKLAR = [
        [
            '/(?<!dark:)\btext-stone-400\b/',
            '2.59',
            'açık zeminde okunmuyor',
            'text-stone-600 (7.64)',
        ],
        [
            '/\bdark:text-stone-500\b/',
            '3.65',
            'koyu zeminde okunmuyor',
            'dark:text-stone-400 (6.76)',
        ],
        [
            '/\bdark:text-stone-600\b/',
            '2.29',
            'koyu zeminde okunmuyor',
            'dark:text-stone-400 (6.76)',
        ],
        [
            '/(?<!dark:)(?<!hover:)(?<!group-hover:)\btext-emerald-600\b/',
            '3.65',
            'beyaz zeminde bağlantı metni olarak okunmuyor',
            'text-emerald-700 (5.36)',
        ],
    ];

    public function test_olculen_kontrast_hatalari_geri_gelmez(): void
    {
        $ihlaller = [];

        foreach ($this->bladeDosyalari() as $yol => $icerik) {
            foreach (self::YASAKLAR as [$desen, $oran, $neden, $oneri]) {
                if (preg_match_all($desen, $icerik, $m)) {
                    $ihlaller[] = sprintf(
                        '%s — %s (%s:1, %s) → %s',
                        $this->kisaYol($yol),
                        $m[0][0],
                        $oran,
                        $neden,
                        $oneri
                    );
                }
            }
        }

        $this->assertSame([], $ihlaller, "Ölçülmüş kontrast hataları geri gelmiş:\n".implode("\n", $ihlaller));
    }

    /**
     * Beyaz metinli dolu buton emerald-600 zemin kullanamaz.
     *
     * Ölçüldü: beyaz / emerald-600 = 3.65 (kalır), beyaz / emerald-700 = 5.36.
     * Bu, sitenin her sayfasındaki birincil eylem düğmesiydi.
     */
    public function test_beyaz_metinli_buton_yeterince_koyu_zemin_kullanir(): void
    {
        $ihlaller = [];

        foreach ($this->bladeDosyalari() as $yol => $icerik) {
            foreach (explode("\n", $icerik) as $no => $satir) {
                if (preg_match('/(?<!dark:)\bbg-emerald-600\b/', $satir)
                    && preg_match('/(?<!dark:)\btext-white\b/', $satir)) {
                    $ihlaller[] = $this->kisaYol($yol).':'.($no + 1);
                }
            }
        }

        $this->assertSame([], $ihlaller, sprintf(
            "Beyaz metin emerald-600 üzerinde 3.65:1 kalıyor (AA 4.5 ister). bg-emerald-700 kullanın (5.36:1).\n%s",
            implode("\n", $ihlaller)
        ));
    }

    /**
     * @return array<string, string>
     */
    private function bladeDosyalari(): array
    {
        $sonuc = [];

        foreach (File::allFiles(resource_path('views')) as $dosya) {
            if (! str_ends_with($dosya->getFilename(), '.blade.php')) {
                continue;
            }

            // Yönetim paneli Filament'in kendi paletini kullanır — kapsam dışı.
            if (str_contains(str_replace('\\', '/', $dosya->getPathname()), '/filament/')) {
                continue;
            }

            // Yorumlar ayıklanır: bir sınıftan yorumda söz etmek kullanmak değildir.
            $sonuc[$dosya->getPathname()] = preg_replace(
                '/\{\{--.*?--\}\}|<!--.*?-->/s',
                '',
                $dosya->getContents()
            ) ?? '';
        }

        return $sonuc;
    }

    private function kisaYol(string $yol): string
    {
        return str_replace('\\', '/', str_replace(base_path().DIRECTORY_SEPARATOR, '', $yol));
    }
}
