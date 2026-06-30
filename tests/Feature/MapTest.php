<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Services\GeocodingService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_map_page_loads(): void
    {
        $this->get('/harita')->assertOk()->assertSee('Haritada Keşfet');
    }

    public function test_map_includes_only_listings_with_coordinates(): void
    {
        $user = User::factory()->create();
        Listing::factory()->for($user)->create(['status' => 'aktif', 'title' => 'Haritali ilan', 'latitude' => 52.52, 'longitude' => 13.405]);
        Listing::factory()->for($user)->create(['status' => 'aktif', 'title' => 'Haritasiz ilan', 'latitude' => null, 'longitude' => null]);

        $this->get('/harita')
            ->assertOk()
            ->assertSee('Haritali ilan')
            ->assertDontSee('Haritasiz ilan');
    }

    public function test_geocoding_falls_back_to_country_center(): void
    {
        // Testte ağ kullanılmaz → ülke merkezine düşer (CountrySeeder koordinatları).
        $coords = app(GeocodingService::class)->locate('Berlin', 'DE');

        $this->assertEqualsWithDelta(51.1657, $coords['latitude'], 0.001);
        $this->assertEqualsWithDelta(10.4515, $coords['longitude'], 0.001);
    }

    public function test_created_listing_receives_coordinates(): void
    {
        $user = User::factory()->create();
        $category = Category::whereNotNull('parent_id')->where('type', 'hizmet')->first();

        $this->actingAs($user)->post('/panel/ilan', [
            'type' => 'hizmet',
            'title' => 'Koordinatlı hizmet ilanı',
            'category_id' => $category->id,
            'description' => 'Açıklama metni yeterince uzun olmalı, en az yirmi karakter.',
            'currency' => 'EUR',
            'price_unit' => 'gorusulur',
            'country_code' => 'DE',
            'city' => 'Berlin',
        ]);

        $listing = Listing::first();
        $this->assertNotNull($listing->latitude);
        $this->assertNotNull($listing->longitude);
        $this->assertEqualsWithDelta(51.1657, $listing->latitude, 0.001);
    }
}
