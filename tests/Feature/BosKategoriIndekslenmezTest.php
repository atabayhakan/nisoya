<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Boş kategori sayfalarının arama motoruna bildirilmemesi (2026-07-29).
 *
 * BULUNAN DURUM: sitemap.xml 116 URL bildiriyordu; bunların 97'si kategori
 * sayfasıydı ve 93'ünde SIFIR ilan vardı. Hepsi indekslenebilir, hepsi aynı
 * meta description'ı taşıyordu — klasik "thin content" deseni.
 *
 * NEDEN ÖNEMLİ: arama motoru içeriksiz sayfaları değersiz sayar ve bu
 * değerlendirme SİTENİN TAMAMINA yansır; yani dolu olan birkaç sayfanın da
 * sıralaması düşer. Büyüme çalışması trafik artışı demek olduğu için, bu
 * temizlik yapılmadan yapılacak her SEO işi durumu KÖTÜLEŞTİRİR.
 *
 * İki kapı birden gerekiyor ve bu dosya ikisini de mühürler:
 *   1. sitemap'e girmemek (öneri listesinden çıkar),
 *   2. sayfanın kendi `robots` etiketi (iç bağlantıdan gelen taramayı keser).
 * Yalnız birincisi yapılırsa sayfa yine indekslenir — sitemap bir öneridir,
 * yasak değil.
 *
 * Sayfa SİLİNMEZ: kullanıcı gezinirken ulaşabilmeli. İlan girildiği anda
 * kendiliğinden hem sitemap'e döner hem indekslenebilir olur.
 */
class BosKategoriIndekslenmezTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function ilanliKategori(): Category
    {
        $kategori = Category::query()->whereNotNull('parent_id')->firstOrFail();

        Listing::factory()->create([
            'user_id' => User::factory()->create(['email_verified_at' => now()])->id,
            'category_id' => $kategori->id,
            'status' => ListingStatus::Aktif,
        ]);

        return $kategori;
    }

    public function test_bos_kategori_sitemapte_yer_almaz(): void
    {
        $dolu = $this->ilanliKategori();
        $bos = Category::query()->whereNotNull('parent_id')->where('id', '!=', $dolu->id)->firstOrFail();

        $icerik = $this->get('/sitemap.xml')->assertSuccessful()->getContent();

        $this->assertStringContainsString(
            route('listings.category', $dolu->slug),
            $icerik,
            'İlanı olan kategori sitemap\'te olmalı.'
        );

        $this->assertStringNotContainsString(
            route('listings.category', $bos->slug),
            $icerik,
            'Sıfır ilanlı kategori sitemap\'e girmemeli — içeriksiz sayfa bildirmek site geneli SEO değerlendirmesini düşürür.'
        );
    }

    public function test_bos_kategori_sayfasi_noindex_basar(): void
    {
        $bos = Category::query()->whereNotNull('parent_id')->firstOrFail();

        $this->get(route('listings.category', $bos->slug))
            ->assertSuccessful()
            ->assertSee('name="robots" content="noindex,follow"', false);
    }

    /**
     * `follow` bilinçli: sayfayı indeksleme ama bağlantıları izlemeye devam et.
     * `nofollow` yazılırsa gezinme akışı ve dolu sayfaların keşfi bozulur.
     */
    public function test_bos_kategori_baglantilari_izlenmeye_devam_eder(): void
    {
        $bos = Category::query()->whereNotNull('parent_id')->firstOrFail();

        $this->get(route('listings.category', $bos->slug))
            ->assertDontSee('content="noindex,nofollow"', false);
    }

    public function test_ilanli_kategori_indekslenebilir_kalir(): void
    {
        $dolu = $this->ilanliKategori();

        $this->get(route('listings.category', $dolu->slug))
            ->assertSuccessful()
            ->assertDontSee('name="robots"', false);
    }

    /**
     * Filtreli arama boş dönebilir; bu normaldir ve kalıcı bir sayfa değildir.
     * Kural yalnız KATEGORİ sayfalarına uygulanmalı, yoksa /ilanlar da
     * yanlışlıkla noindex olur.
     */
    public function test_kategorisiz_liste_sayfasi_noindex_almaz(): void
    {
        $this->get('/ilanlar?q=hicbirseyeeslesmeyenkelime')
            ->assertSuccessful()
            ->assertDontSee('name="robots"', false);
    }
}
