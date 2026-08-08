<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\NabizService;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Demo sızıntısının İKİNCİ turu: örnek SATICI. (2026-08-08)
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Birinci tur (DemoSizintisiTest) örnek İLANLARI sayılmaz ve indekslenmez
 * yaptı. Bağımsız bir denetim, sızıntının yarım kapandığını gösterdi: her
 * örnek ilanın bir örnek SATICISI var ve o satıcı hiçbir yerde süzülmüyordu.
 *
 *   sitemap.xml : düzeltilen ilan döngüsünün 24 SATIR ALTINDA aynı hata —
 *                 örnek üyelerin /uye/... profilleri bildirilmeye devam
 *                 ediyordu. Kapı değişmişti, sızıntı kapanmamıştı.
 *   /uye/...     : profilde robots etiketi yoktu; sayfa uydurma bir kişi için
 *                 JSON-LD Person + AggregateRating (★ puan) yayınlıyordu.
 *   profilden mesaj : ilan kapısı kapalıydı, profil kapısı açıktı — aynı sahte
 *                 satıcıya ulaşmanın iki yolundan biri korumasızdı.
 *   kayıtlı arama : örnek ilanlar "3 yeni ilan" diye E-POSTALANIYORDU; üstelik
 *                 birinci tur bu ucu KÖTÜLEŞTİRDİ (e-postadaki düğme artık
 *                 ilanın listelenmediği bir sayfaya çıkıyordu).
 *   /panel       : boş panodaki "Ülkende son eklenenler" rozet basmayan bir
 *                 liste ve örnek ilan gösteriyordu.
 *   Nabız hedefi : "bu ay N yeni üye" çubuğu demo partisiyle zıplıyordu.
 *
 * Ders tek cümle: bir varlığı gizlerken ONA BAĞLI varlıkları da say — örnek
 * ilanı sakladım, örnek satıcıyı unuttum.
 */
class DemoSizintisiIkinciTurTest extends TestCase
{
    use RefreshDatabase;

    private User $gercekUye;

    private User $ornekUye;

    private Listing $gercekIlan;

    private Listing $ornekIlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);

        $kategori = Category::query()->whereNotNull('parent_id')->firstOrFail();

        $this->gercekUye = User::factory()->create([
            'name' => 'GERCEK SATICI',
            'username' => 'gercek-satici',
            'is_demo' => false,
        ]);

        $this->ornekUye = User::factory()->create([
            'name' => 'ORNEK SATICI',
            'username' => 'ornek-satici',
            'is_demo' => true,
        ]);

        $this->gercekIlan = Listing::factory()->create([
            'user_id' => $this->gercekUye->id,
            'category_id' => $kategori->id,
            'title' => 'GERCEK-ILAN',
            'slug' => 'gercek-ilan',
            'status' => 'aktif',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'is_demo' => false,
        ]);

        $this->ornekIlan = Listing::factory()->create([
            'user_id' => $this->ornekUye->id,
            'category_id' => $kategori->id,
            'title' => '[ÖRNEK] ORNEK-ILAN',
            'slug' => 'ornek-ilan',
            'status' => 'aktif',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'is_demo' => true,
        ]);
    }

    // ---------------------------------------------------------------- sitemap

    public function test_sitemap_ornek_saticinin_profilini_bildirmez(): void
    {
        $yanit = $this->get('/sitemap.xml')->assertOk();

        $yanit->assertSee(route('profiles.show', 'gercek-satici'), false);
        $yanit->assertDontSee(route('profiles.show', 'ornek-satici'), false);
    }

    public function test_elinde_yalniz_ornek_ilan_kalan_gercek_uye_sitemape_girmez(): void
    {
        // İkinci gercek(): "aktif ilanı var" koşulu örnek ilanla sağlanamaz.
        // Aksi hâlde gerçek bir üye, sitede hiç görünmeyen bir ilan yüzünden
        // içeriksiz bir profil sayfasıyla arama motoruna önerilirdi.
        $bosUye = User::factory()->create(['username' => 'yalniz-ornekli', 'is_demo' => false]);

        Listing::factory()->create([
            'user_id' => $bosUye->id,
            'category_id' => $this->ornekIlan->category_id,
            'title' => '[ÖRNEK] Devredilmis',
            'slug' => 'devredilmis-ornek',
            'status' => 'aktif',
            'is_demo' => true,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee(route('profiles.show', 'yalniz-ornekli'), false);
    }

    // ----------------------------------------------------------------- profil

    public function test_ornek_satici_profili_acilir_ama_noindex_tir(): void
    {
        $this->get(route('profiles.show', 'ornek-satici'))
            ->assertOk()
            ->assertSee('noindex', false)
            ->assertSee('Bu bir ÖRNEK profildir');

        $this->get(route('profiles.show', 'gercek-satici'))
            ->assertOk()
            ->assertDontSee('name="robots"', false);
    }

    public function test_ornek_profilde_uydurma_yapilandirilmis_veri_basilmaz(): void
    {
        // JSON-LD arama motoruna "bu gerçek bir kişi, puanı şu" der. Uydurma
        // bir kişi için yayınlamak noindex ile bile savunulabilir değil.
        //
        // "application/ld+json" HİÇ geçmemeli DENMEZ: her sayfa layout'tan
        // site geneli WebSite + Organization şeması alır
        // (components/layout-head-scripts.blade.php). Ölçülen şey KİŞİ
        // şemasının basılmaması — ilk yazımında geniş iddia kullanılmış ve
        // test kendi kurduğu tuzağa düşmüştü.
        $this->get(route('profiles.show', 'ornek-satici'))
            ->assertOk()
            ->assertDontSee('"@type": "Person"', false)
            ->assertDontSee('AggregateRating', false);

        $this->get(route('profiles.show', 'gercek-satici'))
            ->assertOk()
            ->assertSee('"@type": "Person"', false);
    }

    public function test_ornek_ilan_detayinda_urun_semasi_basilmaz(): void
    {
        // Aynı gerekçe ilan tarafında: uydurma bir ilan için fiyat/stok/
        // sağlayıcı iddiası yayınlanmaz. Klasik ve Vitrin ayrı dosyalar —
        // ikisi de ölçülür, biri düzeltilip diğeri unutulmasın.
        foreach (['klasik', 'vitrin'] as $tema) {
            Settings::setMany(['gorunum.tema' => $tema]);
            Cache::flush();

            // Yine dar iddia: "schema.org" site geneli şemada da geçiyor.
            // Ölçülen şey İLANA ait blokların basılmaması.
            $this->get(route('listings.show', [$this->ornekIlan, $this->ornekIlan->slug]))
                ->assertOk()
                ->assertDontSee('BreadcrumbList', false)
                ->assertDontSee('"@type": "Service"', false)
                ->assertDontSee('"@type": "Product"', false)
                ->assertDontSee('RealEstateListing', false);

            $this->get(route('listings.show', [$this->gercekIlan, $this->gercekIlan->slug]))
                ->assertOk()
                ->assertSee('BreadcrumbList', false);
        }
    }

    public function test_gercek_profil_ornek_ilan_saymaz_ve_listelemez(): void
    {
        // Örnek ilan gerçek bir üyeye devrolsa bile onun vitrinine girmez.
        $this->ornekIlan->update(['user_id' => $this->gercekUye->id]);

        $this->get(route('profiles.show', 'gercek-satici'))
            ->assertOk()
            ->assertSee('GERCEK-ILAN')
            ->assertDontSee('ORNEK-ILAN');
    }

    public function test_ornek_profil_kendi_ornek_ilanlarini_gosterir(): void
    {
        // Profil sahibinin gerçekliğini izler: örnek profili de boşaltmak
        // demo gezintisini anlamsız kılardı. Sayfa zaten bantlı ve noindex.
        $this->get(route('profiles.show', 'ornek-satici'))
            ->assertOk()
            ->assertSee('ORNEK-ILAN');
    }

    // ------------------------------------------------------------------ mesaj

    public function test_ornek_saticiya_profilinden_mesaj_gonderilemez(): void
    {
        // İlan kapısı zaten kapalıydı (MessageController::start); açık olan
        // profil kapısıydı.
        $gonderen = User::factory()->create(['is_demo' => false]);

        $this->actingAs($gonderen)
            ->post(route('messages.startWithUser', 'ornek-satici'), ['body' => 'Merhaba, müsait misiniz?'])
            ->assertRedirect();

        $this->assertDatabaseCount('conversations', 0);
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_gercek_saticiya_profilinden_mesaj_gonderilebilir(): void
    {
        $gonderen = User::factory()->create(['is_demo' => false]);

        $this->actingAs($gonderen)
            ->post(route('messages.startWithUser', 'gercek-satici'), ['body' => 'Merhaba, musait misiniz?'])
            ->assertRedirect();

        $this->assertDatabaseCount('messages', 1);
    }

    // --------------------------------------------------------- kayıtlı arama

    public function test_kayitli_arama_ornek_ilani_yeni_ilan_saymaz(): void
    {
        $arama = SavedSearch::query()->create([
            'user_id' => $this->gercekUye->id,
            'label' => 'Almanya',
            'ulke' => 'DE',
        ]);

        $baslıklar = $arama->matchingQuery()->pluck('title')->all();

        $this->assertContains('GERCEK-ILAN', $baslıklar);
        $this->assertNotContains('[ÖRNEK] ORNEK-ILAN', $baslıklar);
    }

    // ------------------------------------------------------------------ panel

    public function test_panel_bos_durumu_ornek_ilan_onermez(): void
    {
        // Yeni üyenin gördüğü İLK vaat bu liste; rozet basmıyor.
        $this->gercekIlan->update(['status' => 'taslak']);

        $yeniUye = User::factory()->create(['country_code' => 'DE', 'is_demo' => false]);

        $this->actingAs($yeniUye)
            ->get('/panel')
            ->assertOk()
            ->assertDontSee('ORNEK-ILAN');
    }

    // ------------------------------------------------------------------ nabız

    public function test_nabiz_hedefi_ornek_uye_ve_ilani_saymaz(): void
    {
        Settings::setMany(['nabiz.hedef_sayi' => '10', 'nabiz.hedef_metrik' => 'yeni_uye']);
        Cache::flush();

        $nabiz = app(NabizService::class);

        // Kurulumda 1 gerçek + 1 örnek üye var (setUp). Örnek sayılmamalı.
        $this->assertSame(
            User::query()->gercek()->where('created_at', '>=', now()->startOfMonth())->count(),
            $nabiz->goalProgress()['mevcut'],
        );
        $this->assertNotContains($this->ornekUye->id, [$nabiz->goalProgress()['mevcut']]);

        Settings::setMany(['nabiz.hedef_metrik' => 'yeni_ilan']);
        Cache::flush();

        $this->assertSame(1, $nabiz->goalProgress()['mevcut']);
    }
}
