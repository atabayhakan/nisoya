<?php

namespace Tests\Feature;

use App\Models\Category;
use Database\Seeders\CategorySeeder;
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

    protected function tearDown(): void
    {
        Cache::forget(Category::EMERGENCY_CACHE_KEY);
        parent::tearDown();
    }
}
