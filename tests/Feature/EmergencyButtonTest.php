<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Acil düğmesi ve 3. katmanın (ilan bağlantıları) görünürlük kuralları.
 *
 * 2026-08-12'de kural değişti: ilan bağlantısı YALNIZ gerçekten ilanı olan
 * kategori için basılır. Gerekçe ve ölçüm [AcilMenusuTest] başlığında.
 * Buradaki testler bu yüzden artık kategoriyle birlikte İLAN da oluşturur —
 * kategori tek başına düğme üretmez.
 */
class EmergencyButtonTest extends TestCase
{
    use RefreshDatabase;

    /** Verilen slug'lı acil kategorilerine birer gerçek, aktif ilan koyar. */
    private function ilanla(string ...$slugs): void
    {
        $this->seed(CountrySeeder::class);

        foreach ($slugs as $slug) {
            Listing::factory()->create([
                'category_id' => Category::where('slug', $slug)->firstOrFail()->id,
                'status' => 'aktif',
                'is_demo' => false,
            ]);
        }
    }

    public function test_emergency_button_visible_with_doctors_and_locksmiths_in_order(): void
    {
        $this->seed(CategorySeeder::class);
        $this->ilanla('doktorlar', 'cilingirler', 'avukatlar');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeInOrder(['Acil Yardım', 'Doktorlar', 'Çilingirler', 'Avukatlar']);
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
         *
         * Düğme artık envanterden bağımsız; koşullu olan yalnız 3. katman.
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

    public function test_emergency_categories_cache_invalidates_when_child_category_saved(): void
    {
        $this->seed(CategorySeeder::class);
        $this->ilanla('doktorlar');

        // Cache'i doldur.
        $this->get('/')->assertSee('Doktorlar');

        $doktorlar = Category::where('slug', 'doktorlar')->firstOrFail();
        $doktorlar->update(['name' => 'Nöbetçi Doktorlar']);

        $response = $this->get('/');

        $response->assertSee('Nöbetçi Doktorlar');
        $response->assertDontSee('>Doktorlar<', false);
    }

    public function test_emergency_categories_cache_invalidates_when_child_category_deleted(): void
    {
        $this->seed(CategorySeeder::class);
        $this->ilanla('doktorlar', 'cilingirler');

        $this->get('/')->assertSee('Çilingirler');

        $cilingirler = Category::where('slug', 'cilingirler')->firstOrFail();
        Listing::where('category_id', $cilingirler->id)->delete();
        $cilingirler->delete();

        $response = $this->get('/');

        $response->assertSee('Doktorlar');
        $response->assertDontSee('Çilingirler');
    }

    public function test_ilan_yayinlaninca_cache_dusuyor(): void
    {
        /*
         * YENİ KANCA (Listing::booted). 3. katmanın görünürlüğü artık ilan
         * SAYISINA bağlı, ama liste `rememberForever` ile cache'li. İlan
         * değişikliği cache'i düşürmezse ilk gerçek doktor ilanı yayınlandığı
         * hâlde menüde hiç görünmezdi.
         */
        $this->seed(CategorySeeder::class);

        // Önce ilansız hâli cache'e girsin.
        $this->get('/')->assertDontSee('Türkçe konuşan yardım');

        $this->ilanla('doktorlar');

        $this->get('/')
            ->assertSee('Türkçe konuşan yardım')
            ->assertSee('Doktorlar');
    }

    public function test_ilan_kalkinca_kategori_menuden_dusuyor(): void
    {
        // Ters yön: son ilan silinince bağlantı boş sayfaya götürmemeli.
        $this->seed(CategorySeeder::class);
        $this->ilanla('doktorlar');

        $this->get('/')->assertSee('Doktorlar');

        /*
         * TEK TEK siliniyor, `Listing::query()->delete()` ile DEĞİL.
         *
         * Eloquent'te toplu silme/güncelleme model olaylarını HİÇ tetiklemez;
         * o yolla silinince cache düşmez ve menü olmayan ilana bağlantı
         * vermeye devam eder. İlk yazışta toplu sildim, test bunu yakaladı.
         * Sınırın kendisi Listing::booted() içinde yazılı.
         */
        Listing::all()->each->delete();

        $this->get('/')->assertDontSee('Türkçe konuşan yardım');
    }

    public function test_emergency_category_links_to_correct_category_route(): void
    {
        $this->seed(CategorySeeder::class);
        $this->ilanla('doktorlar');

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

    public function test_category_link_appends_selected_country_via_alpine(): void
    {
        $this->seed([CategorySeeder::class, CountrySeeder::class]);
        $this->ilanla('doktorlar');

        $doktorlar = Category::where('slug', 'doktorlar')->firstOrFail();

        $response = $this->get('/');

        $response->assertSee(
            "'".route('listings.category', $doktorlar->slug)."' + (ulke ? '?ulke=' + ulke : '')",
            false
        );
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
