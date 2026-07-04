<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\SiteSettingSeeder;
use Database\Seeders\StaticPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonetizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
    }

    protected function seedBaseData(): void
    {
        $this->seed([
            CurrencySeeder::class,
            CountrySeeder::class,
            CategorySeeder::class,
            ProductCategorySeeder::class,
            SiteSettingSeeder::class,
        ]);
    }

    public function test_gizlilik_sayfasi_aciliyor(): void
    {
        // Gizlilik sayfası Faz B'de CMS'e (pages tablosu) taşındı.
        $this->seed(StaticPagesSeeder::class);

        $this->get('/gizlilik')->assertOk()->assertSee('Gizlilik Politikası');
    }

    public function test_cerez_tercihleri_sayfasi_aciliyor(): void
    {
        $this->get('/cerez-tercihleri')->assertOk()
            ->assertSee('Çerez Tercihleri')
            ->assertSee('Zorunlu çerezler')
            ->assertSee('Analitik çerezleri')
            ->assertSee('Reklam çerezleri');
    }

    public function test_layout_adsense_devre_disiyken_reklam_gostermez(): void
    {
        $this->seedBaseData();

        $response = $this->get('/')->assertOk();
        // AdSense script tag'ı (data-ad-client ile başlayan) yüklenmemeli
        $response->assertDontSee('adsbygoogle.js', false);
        $response->assertDontSee('ca-pub-', false);
    }

    public function test_layout_adsense_aktifken_script_yukler(): void
    {
        $this->seedBaseData();

        Settings::setMany([
            'reklam.adsense_publisher' => 'ca-pub-1234567890123456',
        ]);
        Settings::forget();

        // Config'i de manuel override et (AppServiceProvider test'te sadece tablo varsa çalışır,
        // fakat Settings::all() cache'i nedeniyle değeri de set edelim)
        config(['services.adsense.publisher_id' => 'ca-pub-1234567890123456']);
        config(['services.adsense.enabled' => true]);

        $response = $this->get('/')->assertOk();
        $response->assertSee('ca-pub-1234567890123456', false);
        $response->assertSee('adsbygoogle.js', false);
    }

    public function test_layout_analytics_aktifken_gtag_yukler(): void
    {
        $this->seedBaseData();

        Settings::setMany([
            'reklam.analytics_measurement_id' => 'G-TESTID1234',
        ]);
        Settings::forget();

        config(['services.analytics.measurement_id' => 'G-TESTID1234']);
        config(['services.analytics.enabled' => true]);

        $response = $this->get('/')->assertOk();
        $response->assertSee('G-TESTID1234', false);
        $response->assertSee('gtag/js', false);
    }

    public function test_adsense_auto_ads_kodu_temel_script_yerine_kullanilir(): void
    {
        $this->seedBaseData();

        config(['services.adsense.publisher_id' => 'ca-pub-1234567890123456']);
        config(['services.adsense.enabled' => true]);
        config(['services.adsense.auto_ads_code' => '<script data-test-marker="OZEL-AUTO-ADS-KODU"></script>']);

        $response = $this->get('/')->assertOk();
        $response->assertSee('OZEL-AUTO-ADS-KODU', false);
        // Auto ads kodu varken temel script tag'ı ayrıca eklenmemeli (çift yükleme olmasın)
        $response->assertDontSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=', false);
    }

    public function test_analytics_ozel_kodu_gtagdan_sonra_render_edilir(): void
    {
        $this->seedBaseData();

        config(['services.analytics.measurement_id' => 'G-TESTID1234']);
        config(['services.analytics.enabled' => true]);
        config(['services.analytics.custom_code' => '<script data-test-marker="OZEL-ANALYTICS-KODU"></script>']);

        $response = $this->get('/')->assertOk();
        $response->assertSee('OZEL-ANALYTICS-KODU', false);
    }

    public function test_header_ve_footer_ozel_kodu_sayfaya_enjekte_edilir(): void
    {
        $this->seedBaseData();

        config(['services.custom_head_code' => '<meta name="ozel-test-head" content="1">']);
        config(['services.custom_footer_code' => '<script data-test-marker="OZEL-FOOTER-KODU"></script>']);

        $response = $this->get('/')->assertOk();
        $response->assertSee('ozel-test-head', false);
        $response->assertSee('OZEL-FOOTER-KODU', false);
    }

    public function test_footer_telif_metni_ve_sosyal_medya_linkleri_gorunur(): void
    {
        $this->seedBaseData();

        Settings::setMany([
            'footer.telif_metni' => 'Test Şirketi A.Ş. Tüm hakları saklıdır.',
            'footer.sosyal_instagram' => 'https://instagram.com/nisoya',
        ]);

        $response = $this->get('/')->assertOk();
        $response->assertSee('Test Şirketi A.Ş. Tüm hakları saklıdır.');
        $response->assertSee('https://instagram.com/nisoya', false);
    }

    public function test_donation_modal_bileseni_placeholder_gorunur(): void
    {
        $this->seedBaseData();

        $response = $this->get('/')->assertOk();
        $response->assertSee('Destek Ol');
        $response->assertSee('donationModal', false);
    }

    public function test_donation_modal_paypal_ve_iban_gorunur(): void
    {
        $this->seedBaseData();

        Settings::setMany([
            'bagis.paypal_me' => 'paypal.me/nisoyatest',
            'bagis.iban' => 'TR12 3456 7890 1234 5678 9012 34',
            'bagis.iban_sahibi' => 'Nisoya Test',
        ]);

        $response = $this->get('/')->assertOk();
        $response->assertSee('paypal.me/nisoyatest');
        $response->assertSee('TR12 3456 7890 1234 5678 9012 34');
        $response->assertSee('Nisoya Test');
    }

    public function test_donation_modal_maliyet_seffafligi_dolu_ise_gorunur(): void
    {
        $this->seedBaseData();

        Settings::setMany([
            'bagis.maliyet_baslik' => 'Bağışların nereye gittiği',
            'bagis.maliyet1' => 'Sunucu barındırma — ayda ~15€',
            'bagis.maliyet2' => 'Alan adı — yılda ~15€',
        ]);

        $response = $this->get('/')->assertOk();
        $response->assertSee('Bağışların nereye gittiği');
        $response->assertSee('Sunucu barındırma — ayda ~15€');
        $response->assertSee('Alan adı — yılda ~15€');
    }

    public function test_donation_modal_maliyet_seffafligi_bos_ise_gorunmez(): void
    {
        $this->seedBaseData();

        $response = $this->get('/')->assertOk();
        $response->assertDontSee('Sunucu barındırma');
    }

    public function test_json_ld_websites_chema_ekleniyor(): void
    {
        $this->seedBaseData();

        $response = $this->get('/')->assertOk();
        $response->assertSee('"@type": "WebSite"', false);
        $response->assertSee('"@type": "Organization"', false);
        $response->assertSee('"@context": "https://schema.org"', false);
    }

    public function test_json_ld_listing_showda_product_olarak_cikar(): void
    {
        $this->seedBaseData();

        $user = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::first();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => 'urun',
            'status' => 'aktif',
            'price' => 100,
            'currency' => 'EUR',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'stock' => 5,
        ]);

        $response = $this->get(route('listings.show', $listing))->assertOk();
        $response->assertSee('"@type": "Product"', false);
        $response->assertSee('"@type": "BreadcrumbList"', false);
        $response->assertSee('InStock', false);
    }

    public function test_json_ld_listing_showda_service_olarak_cikar(): void
    {
        $this->seedBaseData();

        $user = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::first();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => 'hizmet',
            'status' => 'aktif',
            'price' => 50,
            'currency' => 'EUR',
            'country_code' => 'DE',
            'city' => 'Berlin',
        ]);

        $response = $this->get(route('listings.show', $listing))->assertOk();
        $response->assertSee('"@type": "Service"', false);
    }

    public function test_preconnect_ve_dns_prefetch_layoutta_var(): void
    {
        $this->seedBaseData();

        $response = $this->get('/')->assertOk();
        $response->assertSee('rel="dns-prefetch"', false);
        $response->assertSee('rel="preconnect"', false);
        $response->assertSee('googletagmanager.com', false);
        $response->assertSee('googlesyndication.com', false);
    }

    public function test_robots_txt_adsense_botlarina_izin_veriyor(): void
    {
        $content = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Mediapartners-Google', $content);
        $this->assertStringContainsString('AdsBot-Google', $content);
    }

    public function test_sitemap_gizlilik_ve_cerez_tercihlerini_icerir(): void
    {
        $this->seedBaseData();
        $this->seed(StaticPagesSeeder::class);

        $response = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringContainsString('/gizlilik', $response->getContent());
        $this->assertStringContainsString('/cerez-tercihleri', $response->getContent());
    }
}
