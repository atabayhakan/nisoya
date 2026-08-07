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
     * Panelde ELDE YAZILMIŞ rozet/etiket yeterince koyu zemin kullanır.
     *
     * Filament'in kendi bileşenleri (`<x-filament::badge>` vb.) kontrastı
     * kendi halleder. Tehlike, aynı işi ham Tailwind ile yapan işaretlemede:
     * `bg-primary-600` + `text-white` çifti, varsayılan palette (emerald)
     * beyaz/emerald-600 = 3.65:1 demek — AA'nın normal metin için istediği
     * 4.5'in altında. `marka_rengi` panelden seçilebiliyor ve amber/orange
     * gibi seçenekler daha da düşük kalıyor.
     *
     * NEDEN "AYNI SATIRDA METİN" KOŞULU VAR — yanlış alarm üretmemek için.
     * Depoda bu sınıf çifti iki yerde daha geçiyor ve İKİSİ DE İHLAL DEĞİL:
     * biri yalnız ikon taşıyan gönder düğmesi (`sr-only` metinle), diğeri
     * metinsiz bir grafik çubuğu. Metin olmayan içerikte WCAG eşiği 3:1 ve
     * 3.65 onu geçiyor. Sürekli bağıran bir bekçi, bakılmayan bir bekçidir.
     *
     * Bu yüzden kural dar: sınıflarla AYNI SATIRDA görünür metin de olmalı
     * (`>1<` gibi). Kendi kendine yeten rozetleri yakalar; çok satıra yayılan
     * metinli öğeleri kaçırır — bilinen ve kabul edilen sınır.
     */
    public function test_panelde_elde_yazilmis_rozet_yeterince_koyu_zemin_kullanir(): void
    {
        $ihlaller = [];

        foreach ($this->bladeDosyalari() as $yol => $icerik) {
            if (! str_contains(str_replace('\\', '/', $yol), '/filament/')) {
                continue;
            }

            foreach (explode("\n", $icerik) as $no => $satir) {
                if (preg_match('/(?<!dark:)\bbg-primary-600\b/', $satir)
                    && preg_match('/(?<!dark:)\btext-white\b/', $satir)
                    // `>...metin...<` — öğe görünür metin taşıyor mu?
                    && preg_match('/>[^<>]*[^\s<>][^<>]*</', $satir)) {
                    $ihlaller[] = $this->kisaYol($yol).':'.($no + 1);
                }
            }
        }

        $this->assertSame([], $ihlaller, sprintf(
            "Beyaz metin primary-600 üzerinde 3.65:1 kalıyor (AA 4.5 ister). bg-primary-700 kullanın.\n%s",
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

            /*
             * KÖR NOKTA KAPATILDI (2026-08-08).
             *
             * Burada `/filament/` altındaki her dosya atlanıyordu; gerekçe
             * "yönetim paneli Filament'in kendi paletini kullanır" idi.
             *
             * Gerekçe Filament'in KENDİ BİLEŞENLERİ için doğru — onların
             * kontrastını Filament garanti eder. Ama bu klasördeki dosyalar
             * bizim elimizle yazılmış panel sayfaları ve içlerinde ham
             * Tailwind sınıfları var. Onlar ne Filament'in garantisine ne bu
             * testin kapsamına giriyordu: iki bekçinin arasındaki boşluk.
             *
             * PR #121'in incelemesinde ortaya çıktı — sayfaya `bg-primary-600
             * text-white` rozetler eklendi ve bu test, aynı orana (3.65:1)
             * site tarafında build kırarken panelde hiç ses çıkarmadı.
             *
             * ÖLÇÜLDÜ (kaldırmadan önce): atlama kalkınca mevcut kodda yalnız
             * TEK ihlal çıkıyor (kesif-ilerlemesi.blade.php). Yani kör nokta
             * daraltılmaya değil kapatılmaya uygundu; maliyeti bir satır.
             *
             * NOT: buradaki yasaklar zemin olarak beyazı varsayıyor. Panelin
             * açık kip zemini de beyaz/gray-50 olduğu için oranlar taşınabilir;
             * palet değişirse yeniden ölçülmeli (dosya başındaki kural).
             */

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
