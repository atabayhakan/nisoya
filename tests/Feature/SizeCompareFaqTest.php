<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\ProductCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SizeCompareFaqTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class, ProductCategorySeeder::class]);
    }

    private function productCategory(): Category
    {
        return Category::where('type', 'urun')->whereNotNull('parent_id')->first();
    }

    // --- Faz M5: boyut ---

    public function test_product_saves_dimensions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/panel/ilan', [
            'type' => 'urun',
            'title' => 'İkinci el ahşap masa',
            'category_id' => $this->productCategory()->id,
            'description' => 'Sağlam ahşap yemek masası, az kullanılmış durumda satılık.',
            'currency' => 'EUR',
            'price_unit' => 'adet',
            'country_code' => 'DE',
            'width_cm' => '120',
            'height_cm' => '75',
        ])->assertRedirect();

        $listing = Listing::first();
        $this->assertSame(120, $listing->width_cm);
        $this->assertSame(75, $listing->height_cm);
    }

    public function test_dimensions_ignored_for_service_type(): void
    {
        $user = User::factory()->create();
        $serviceCategory = Category::where('type', 'hizmet')->whereNotNull('parent_id')->first();

        $this->actingAs($user)->post('/panel/ilan', [
            'type' => 'hizmet',
            'title' => 'İngilizce özel ders veriyorum',
            'category_id' => $serviceCategory->id,
            'description' => 'Online İngilizce konuşma dersi, her seviyeye uygun program.',
            'currency' => 'EUR',
            'price_unit' => 'saatlik',
            'country_code' => 'DE',
            'width_cm' => '120',
            'height_cm' => '75',
        ])->assertRedirect();

        $listing = Listing::first();
        $this->assertNull($listing->width_cm);
        $this->assertNull($listing->height_cm);
    }

    public function test_listing_page_shows_size_compare_when_dimensions_present(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create([
            'type' => 'urun', 'status' => 'aktif', 'stock' => 1,
            'category_id' => $this->productCategory()->id,
            'width_cm' => 120, 'height_cm' => 75,
        ]);

        $this->get(route('listings.show', [$listing->id, $listing->slug]))
            ->assertOk()
            ->assertSee('Boyut karşılaştırma')
            ->assertSee('120 × 75 cm')
            ->assertSee('<svg', false);
    }

    public function test_listing_page_hides_size_compare_without_dimensions(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create([
            'type' => 'urun', 'status' => 'aktif', 'stock' => 1,
            'category_id' => $this->productCategory()->id,
            'width_cm' => null, 'height_cm' => null,
        ]);

        $this->get(route('listings.show', [$listing->id, $listing->slug]))
            ->assertOk()
            ->assertDontSee('Boyut karşılaştırma');
    }

    public function test_product_jsonld_has_used_condition_and_dimensions(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create([
            'type' => 'urun', 'status' => 'aktif', 'stock' => 1,
            'category_id' => $this->productCategory()->id,
            'width_cm' => 120, 'height_cm' => 75,
        ]);

        $this->get(route('listings.show', [$listing->id, $listing->slug]))
            ->assertOk()
            ->assertSee('UsedCondition', false)
            ->assertSee('QuantitativeValue', false)
            ->assertSee('CMT', false);
    }

    // --- Faz M6: FAQPage ---

    public function test_how_it_works_has_faqpage_jsonld_and_visible_faq(): void
    {
        $this->get('/nasil-calisir')
            ->assertOk()
            ->assertSee('FAQPage', false)
            ->assertSee('acceptedAnswer', false)
            ->assertSee('Sıkça sorulan sorular')
            ->assertSee('Nisoya ücretsiz mi?');
    }
}
