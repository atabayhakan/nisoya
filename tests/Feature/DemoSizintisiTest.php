<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ÖRNEK (demo) ilanlar gerçek arz gibi görünmesin. (2026-08-08)
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Demo verisinin kendisi dürüsttü: başlıkta `[ÖRNEK]` öneki, kartta rozet,
 * mesaj gönderimi kapalı. Rozeti OKUMAYAN yüzeyler dürüst değildi:
 *
 *   sitemap.xml     : 29 ilanın 28'i örnekti, hiçbirinde robots etiketi yoktu
 *   /ilanlar?ulke=DE: "12 ilan bulundu" — 12'si de örnekti
 *   /harita         : pin rozet taşıyamaz; örnekler yayılma haritası çiziyordu
 *   Cmd+K           : 5 sonuçluk listede örnekler gerçekleri itiyordu
 *
 * Yani bir SAYI ve bir ARAMA MOTORU, kartın üstündeki uyarıyı görmüyordu.
 * Ana sayfa bu kararı 2026-08-01'de zaten vermişti (HomeController: gerçek
 * ilan öncelikli); liste/harita/sitemap tarafına hiç uygulanmamıştı.
 *
 * Kural: örnek ilan SAYILMAZ, İNDEKSLENMEZ, gerçeklerin ARASINA KARIŞMAZ —
 * yalnız kendi etiketli rafında görünür.
 *
 * Bu dosya kuralın her ucunu ayrı ayrı ölçer. Sessizce kopabilecek bir bağ:
 * `Listing::scopeGercek()` çağrısını unutmak hata vermez, yalnız sayıyı
 * yeniden şişirir.
 */
class DemoSizintisiTest extends TestCase
{
    use RefreshDatabase;

    private Listing $gercek;

    private Listing $ornek;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);

        $kategori = Category::query()->whereNotNull('parent_id')->firstOrFail();

        $this->gercek = Listing::factory()->create([
            'user_id' => User::factory()->create(['is_demo' => false])->id,
            'category_id' => $kategori->id,
            'title' => 'GERCEK-ILAN-BASLIGI',
            'slug' => 'gercek-ilan-basligi',
            'status' => 'aktif',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'type' => 'hizmet',
            'latitude' => 52.52,
            'longitude' => 13.40,
            'is_demo' => false,
        ]);

        $this->ornek = Listing::factory()->create([
            'user_id' => User::factory()->create(['is_demo' => true])->id,
            'category_id' => $kategori->id,
            'title' => '[ÖRNEK] ORNEK-ILAN-BASLIGI',
            'slug' => 'ornek-ilan-basligi',
            'status' => 'aktif',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'type' => 'hizmet',
            'latitude' => 48.13,
            'longitude' => 11.58,
            'is_demo' => true,
        ]);
    }

    public function test_sitemap_ornek_ilani_bildirmez(): void
    {
        $yanit = $this->get('/sitemap.xml')->assertOk();

        $yanit->assertSee(route('listings.show', [$this->gercek, $this->gercek->slug]), false);
        $yanit->assertDontSee(route('listings.show', [$this->ornek, $this->ornek->slug]), false);
    }

    public function test_ornek_ilan_detayi_acilir_ama_noindex_tir(): void
    {
        // Sayfanın KAPATILMASI istenmiyor — sahibin gezintisi için açık kalmalı.
        // İstenen tek şey arama motoruna gerçek arz diye sunulmaması.
        $this->get(route('listings.show', [$this->ornek, $this->ornek->slug]))
            ->assertOk()
            ->assertSee('noindex', false);

        $this->get(route('listings.show', [$this->gercek, $this->gercek->slug]))
            ->assertOk()
            ->assertDontSee('name="robots"', false);
    }

    public function test_ilan_listesi_ornegi_saymaz_ve_listelemez(): void
    {
        $this->get('/ilanlar')
            ->assertOk()
            ->assertSee('GERCEK-ILAN-BASLIGI')
            ->assertDontSee('ORNEK-ILAN-BASLIGI')
            ->assertSee('1 ilan bulundu');
    }

    public function test_ulke_suzgeci_ornegi_saymaz(): void
    {
        // Asıl şikâyetin bire bir hâli: "/ilanlar?ulke=DE 12 ilan diyor,
        // 12'si de örnek".
        $this->get('/ilanlar?ulke=DE')
            ->assertOk()
            ->assertSee('1 ilan bulundu');
    }

    public function test_gercek_sonuc_bosken_ornekler_etiketli_rafta_gorunur(): void
    {
        // Demo tamamen kaybolmuyor: gerçek arz yokken, SAYILMADAN ve kendi
        // uyarısıyla görünüyor. Ana sayfadaki kuralın (HomeController) liste
        // sayfasındaki karşılığı.
        $this->gercek->update(['status' => 'taslak']);

        $this->get('/ilanlar')
            ->assertOk()
            ->assertSee('0 ilan bulundu')
            ->assertSee('Aşağıdakiler ÖRNEK ilanlardır')
            ->assertSee('ORNEK-ILAN-BASLIGI');
    }

    public function test_gercek_sonuc_varken_ornek_rafi_hic_basilmaz(): void
    {
        $this->get('/ilanlar')
            ->assertOk()
            ->assertDontSee('Aşağıdakiler ÖRNEK ilanlardır');
    }

    public function test_haritada_ornek_pin_yok(): void
    {
        // Pin rozet taşıyamaz — kartın çözdüğü sorun burada çözülemez.
        $this->get('/harita')
            ->assertOk()
            ->assertSee('GERCEK-ILAN-BASLIGI')
            ->assertDontSee('ORNEK-ILAN-BASLIGI');
    }

    public function test_hizli_aramada_ornek_cikmaz(): void
    {
        $sonuc = $this->getJson('/arama/hizli?q=ILAN-BASLIGI')->assertOk()->json('results');

        $basliklar = collect($sonuc)->pluck('title')->all();

        $this->assertContains('GERCEK-ILAN-BASLIGI', $basliklar);
        $this->assertNotContains('[ÖRNEK] ORNEK-ILAN-BASLIGI', $basliklar);
    }

    public function test_kategori_sayfasi_yalniz_ornek_iceriyorsa_noindex(): void
    {
        // Boş kategori sayfası noindex'ti (BosKategoriIndekslenmezTest); örnek
        // ilan onu "dolu" göstererek bu korumayı delmişti.
        $this->gercek->update(['status' => 'taslak']);

        $kategori = Category::query()->findOrFail($this->ornek->category_id);

        $this->get(route('listings.category', $kategori->slug))
            ->assertOk()
            ->assertSee('noindex', false);
    }

    public function test_vitrin_temasinda_da_ayni_kural_gecerli(): void
    {
        // Vitrin, listeyi ve sayacı KENDİ görünümünden basıyor
        // (resources/views/vitrin/listings/index.blade.php). Klasik tarafı
        // düzeltip burayı unutmak sessiz bir gerileme olurdu.
        Settings::setMany(['gorunum.tema' => 'vitrin']);
        Cache::flush();

        $this->get('/ilanlar')
            ->assertOk()
            ->assertSee('GERCEK-ILAN-BASLIGI')
            ->assertDontSee('ORNEK-ILAN-BASLIGI');

        $this->gercek->update(['status' => 'taslak']);

        $this->get('/ilanlar')
            ->assertOk()
            ->assertSee('Aşağıdakiler ÖRNEK ilanlardır')
            ->assertSee('ORNEK-ILAN-BASLIGI');
    }
}
