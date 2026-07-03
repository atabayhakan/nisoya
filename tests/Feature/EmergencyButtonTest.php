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

class EmergencyButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_emergency_button_visible_with_doctors_and_locksmiths_in_order(): void
    {
        $this->seed(CategorySeeder::class);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeInOrder(['Acil Yardım', 'Doktorlar', 'Çilingirler']);
    }

    public function test_emergency_button_hidden_when_no_emergency_category_exists(): void
    {
        // "Acil Yardım" kategorisi seed edilmeden diğer kategoriler oluşturulur.
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
        $response->assertDontSee('Acil yardım — hızlı erişim', false);
    }

    public function test_emergency_categories_cache_invalidates_when_child_category_saved(): void
    {
        $this->seed(CategorySeeder::class);

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

    public function test_category_link_appends_selected_country_via_alpine(): void
    {
        $this->seed([CategorySeeder::class, CountrySeeder::class]);

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
