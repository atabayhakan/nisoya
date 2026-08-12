<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Acil düğmesi ve 3. katmanın (yardım kategorileri) davranışı.
 *
 * ---------------------------------------------------------------------------
 * KURAL BİR KEZ DEĞİŞTİ — ikisini de bilmek gerekiyor
 *
 * 2026-08-12 sabahı: kategoriler HER ülkede "0 ilan bulundu" döndürdüğü için
 * ilanı olmayan kategori hiç basılmıyordu (boş vaat vermeme kuralı).
 *
 * 2026-08-12 akşamı, SAHİP KARARIYLA: dört düğme (doktor/avukat/çilingir/
 * cenaze) HER ZAMAN görünüyor ve şehir süzgeciyle arama sayfasına götürüyor.
 * Vaat değişti — artık "burada yardım var" değil, "şehrinde ara" deniyor.
 *
 * Dürüstlük başka yerden geliyor: düğmeler küçük ve ikincil (hayatî katmanla
 * karışmıyor) ve altlarında "henüz kayıtlı kimse olmayabilir" yazıyor.
 */
class EmergencyButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_dort_yardim_dugmesi_de_basiliyor(): void
    {
        /*
         * İlan YOK ve olmamalı da — bu testin ayırt ettiği şey tam olarak bu:
         * düğmeler envanterden bağımsız görünür.
         */
        $this->seed(CategorySeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Acil Yardım', 'Doktorlar', 'Çilingirler', 'Avukatlar', 'Cenaze Hizmetleri']);
    }

    public function test_cenaze_hizmetleri_acil_kategorisinin_altinda(): void
    {
        // Diasporada vefat, ailenin en çaresiz kaldığı an; kategori acil
        // grubunun altında olmalı ki panelde görünsün.
        $this->seed(CategorySeeder::class);

        $cenaze = Category::where('slug', 'cenaze-hizmetleri')->first();

        $this->assertNotNull($cenaze, 'Cenaze Hizmetleri kategorisi yok.');
        $this->assertSame(
            Category::where('slug', Category::EMERGENCY_SLUG)->value('id'),
            $cenaze->parent_id,
            'Cenaze Hizmetleri, Acil Yardım altında değil — panelde görünmez.'
        );
    }

    public function test_acil_dugmesi_acil_kategorisi_hic_yokken_de_gorunur(): void
    {
        /*
         * BU TEST ESKİDEN TERSİNİ İDDİA EDİYORDU ve o iddia bir hatayı
         * koruyordu: acil kategorisi yoksa düğmenin tamamı gizleniyordu.
         *
         * Panelin hayatî içeriği (ülkenin acil numarası + konsolosluk çağrı
         * merkezi) pazaryeri taksonomisiyle hiç ilgili değil. Eski kuralla,
         * sahip panelden "Acil Yardım" kategorilerini pasife çektiğinde
         * sitedeki tek acil numarası da sessizce kaybolurdu.
         */
        Category::create([
            'name' => 'Eğitim & Ders',
            'slug' => 'egitim-ders',
            'icon' => '📚',
            'type' => 'hizmet',
            'sort_order' => 0,
            'is_active' => true,
            'parent_id' => null,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Acil yardım — hızlı erişim', false);
        $response->assertSee('Acil servis');
        $response->assertSee('+90 312 292 29 29');
    }

    public function test_yardim_baglantisi_ulke_ve_sehri_birlikte_tasiyor(): void
    {
        /*
         * Sahibin istediği davranış: düğmeye basan kişi KENDİ ŞEHRİNDEKİ
         * kişileri bulsun. Bağlantı Alpine'da kuruluyor çünkü ülke panelden
         * değiştirilebiliyor; şehir ise sunucudan geliyor.
         */
        $this->seed([CategorySeeder::class, CountrySeeder::class]);
        $user = User::factory()->create(['country_code' => 'DE', 'city' => 'Berlin']);

        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('kategoriBaglantisi', $html,
            'Kategori bağlantısı şehir/ülke birleştiricisini kullanmıyor.');
        $this->assertStringContainsString("sehir: 'Berlin'", $html,
            'Üyenin şehri panele geçmemiş — arama ülke geneli kalır.');
    }

    public function test_sehri_olmayan_uye_icin_sehir_bos_gecer(): void
    {
        // Şehir bilinmiyorsa arama ülke geneli olmalı; uydurma şehir gitmemeli.
        $this->seed([CategorySeeder::class, CountrySeeder::class]);
        $user = User::factory()->create(['country_code' => 'DE', 'city' => null]);

        $this->actingAs($user)->get('/')->assertOk()->assertSee("sehir: ''", false);
    }

    public function test_emergency_categories_cache_invalidates_when_child_category_saved(): void
    {
        $this->seed(CategorySeeder::class);

        // Cache'i doldur.
        $this->get('/')->assertSee('Doktorlar');

        Category::where('slug', 'doktorlar')->firstOrFail()->update(['name' => 'Nöbetçi Doktorlar']);

        $response = $this->get('/');

        $response->assertSee('Nöbetçi Doktorlar');
        $response->assertDontSee('>Doktorlar<', false);
    }

    public function test_emergency_categories_cache_invalidates_when_child_category_deleted(): void
    {
        $this->seed(CategorySeeder::class);

        $this->get('/')->assertSee('Çilingirler');

        Category::where('slug', 'cilingirler')->firstOrFail()->delete();

        $response = $this->get('/');

        $response->assertSee('Doktorlar');
        $response->assertDontSee('Çilingirler');
    }

    public function test_emergency_category_links_to_correct_category_route(): void
    {
        $this->seed(CategorySeeder::class);

        $doktorlar = Category::where('slug', 'doktorlar')->firstOrFail();

        $this->get('/')->assertSee(route('listings.category', $doktorlar->slug), false);
    }

    public function test_country_selector_shows_active_countries_and_defaults_empty_for_guest(): void
    {
        $this->seed([CategorySeeder::class, CountrySeeder::class]);

        $response = $this->get('/');

        $response->assertOk();
        // Alpine x-data başlangıç değeri misafir için boş (ülke filtresiz).
        $response->assertSee("ulke: ''", false);
        $response->assertSee('🇩🇪 Almanya');
    }

    public function test_country_selector_defaults_to_authenticated_users_country(): void
    {
        $this->seed([CategorySeeder::class, CountrySeeder::class]);
        $user = User::factory()->create(['country_code' => 'DE']);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee("ulke: 'DE'", false);
    }

    public function test_country_selector_hidden_when_no_active_countries(): void
    {
        $this->seed(CategorySeeder::class);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Hangi ülkedesin?');
    }

    public function test_active_countries_cache_invalidates_when_country_saved(): void
    {
        $this->seed([CategorySeeder::class, CountrySeeder::class]);

        $this->get('/')->assertSee('Almanya');

        Country::where('code', 'DE')->firstOrFail()->update(['name_tr' => 'Almanya (güncel)']);

        $response = $this->get('/');

        $response->assertSee('Almanya (güncel)');
    }

    protected function tearDown(): void
    {
        Cache::forget(Category::EMERGENCY_CACHE_KEY);
        Cache::forget(Country::ACTIVE_LIST_CACHE_KEY);
        parent::tearDown();
    }
}
