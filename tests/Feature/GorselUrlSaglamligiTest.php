<?php

namespace Tests\Feature;

use App\Models\ListingImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Görsel URL üretiminin bekçisi (2026-07-29).
 *
 * BULUNAN HATA (canlıda): bir ilanın kapak görseli dört ayrı yüzeyde 404
 * veriyordu — ana sayfa, /ilanlar, ilan detayı ve satıcı profili.
 *
 * İKİ AYRI KÖK NEDEN ÜST ÜSTE BİNMİŞTİ:
 *
 * 1. ÖLÜ KOLON. Blade'ler `$srcset['thumb'] ?? Storage::url($img->path)`
 *    yazıyordu. `listing_images.path` kolonu 2026_07_02_100700 migration'ında
 *    DÜŞÜRÜLDÜ. Eloquent düşürülmüş kolon için null döner, `Storage::url(null)`
 *    ise `/storage` üretir — geçerli görünen ama daima 404 veren bir URL.
 *    Geri dönüş zinciri 8 blade'de kopyalanmıştı.
 *
 * 2. KONTROL EDİLMEYEN YAZMA. `ImageService` dört yerde `Storage::put()`
 *    dönüşünü yok sayıyordu. `public` diski `'throw' => false` ile
 *    yapılandırılı (config/filesystems.php), yani disk dolu/izin hatasında
 *    put() sessizce `false` döner. DB kaydı oluşur, dosya oluşmaz.
 *    Bu hata sınıfı 2026-07-17'de bir kod yolu için bulunup düzeltilmiş
 *    (cropSquare), diğer dördü atlanmıştı.
 *
 * İkisi birlikte "veritabanı görseli var diyor ama sunucuda yok" durumunu
 * hem üretiyor hem de gizliyordu.
 */
class GorselUrlSaglamligiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Hiçbir blade DÜŞÜRÜLMÜŞ `path` kolonunu okumamalı.
     *
     * `EventMedia->path` HARİÇ — o gerçek ve yaşayan bir kolondur.
     */
    public function test_hicbir_blade_dusurulmus_path_kolonunu_okumaz(): void
    {
        $ihlaller = [];
        $kok = dirname(__DIR__, 2).'/resources/views';

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($kok)) as $dosya) {
            $yol = str_replace('\\', '/', (string) $dosya);

            if (! str_ends_with($yol, '.blade.php')) {
                continue;
            }

            foreach (file($yol) ?: [] as $no => $satir) {
                // EventMedia'nın kendi path'i meşru — onu dışla.
                if (str_contains($satir, 'EventMedia::DISK')) {
                    continue;
                }

                if (preg_match('/(coverImage|listingImage|\$image|\$hero|\$benzer->coverImage)\s*->\s*path\b/', $satir)) {
                    $ihlaller[] = substr($yol, strpos($yol, '/resources/') + 1).':'.($no + 1).' → '.trim($satir);
                }
            }
        }

        $this->assertSame([], $ihlaller, sprintf(
            'Düşürülmüş `listing_images.path` kolonu okunuyor. Eloquent null döner, '.
            "`Storage::url(null)` = `/storage` = 404.\nBunun yerine \$image->enIyiUrl('thumb') kullanın.\n\n%s",
            implode("\n", $ihlaller)
        ));
    }

    /**
     * ImageService'te kontrol edilmeyen disk yazımı kalmamalı.
     */
    public function test_imageservice_disk_yazimlarini_kontrol_eder(): void
    {
        $kaynak = file(dirname(__DIR__, 2).'/app/Services/ImageService.php') ?: [];
        $ihlaller = [];

        foreach ($kaynak as $no => $satir) {
            if (! preg_match('/Storage::disk\(\x27public\x27\)->put\(/', $satir)) {
                continue;
            }

            // Kabul edilen biçimler: `if (! ...->put(`, `$x = ...->put(`, `return ...->put(`
            if (preg_match('/(if\s*\(\s*!|=\s*Storage::disk|return\s*\(?\(?bool\)?\s*Storage::disk)/', $satir)) {
                continue;
            }

            $ihlaller[] = 'ImageService.php:'.($no + 1).' → '.trim($satir);
        }

        $this->assertSame([], $ihlaller, sprintf(
            "Storage::put() dönüşü kontrol edilmiyor. `public` diski 'throw' => false ile ".
            'yapılandırılı; disk dolu/izin hatasında sessizce false döner ve DB hiç yazılmamış '.
            "bir dosyaya işaret eder.\n\n%s",
            implode("\n", $ihlaller)
        ));
    }

    public function test_en_iyi_url_varyant_yoksa_null_doner(): void
    {
        $gorsel = new ListingImage;

        $this->assertNull(
            $gorsel->enIyiUrl('thumb'),
            'Hiç varyant yokken uydurma bir URL değil null dönmeli — çağıran "görsel yok" dalına düşsün.'
        );
    }

    public function test_en_iyi_url_eksik_varyantta_asagi_iner(): void
    {
        $gorsel = new ListingImage;
        $gorsel->path_large = 'listings/large/x.webp';

        $url = $gorsel->enIyiUrl('thumb');

        $this->assertNotNull($url, 'thumb yoksa large kullanılmalı.');
        $this->assertStringContainsString('listings/large/x.webp', $url);
    }

    public function test_en_iyi_url_tercih_edilen_varyanti_oncelikler(): void
    {
        $gorsel = new ListingImage;
        $gorsel->path_thumb = 'listings/thumb/t.webp';
        $gorsel->path_large = 'listings/large/l.webp';

        $this->assertStringContainsString('thumb/t.webp', (string) $gorsel->enIyiUrl('thumb'));
        $this->assertStringContainsString('large/l.webp', (string) $gorsel->enIyiUrl('large'));
    }
}
