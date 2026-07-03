<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    protected function leafCategory(): Category
    {
        return Category::whereNotNull('parent_id')->first();
    }

    public function test_activity_feed_shows_recent_active_listing_with_first_name_only(): void
    {
        $seller = User::factory()->create(['name' => 'Ahmet Yılmaz']);
        Listing::factory()->for($seller)->create([
            'status' => 'aktif',
            'category_id' => $this->leafCategory()->id,
            'country_code' => 'DE',
            'city' => 'Berlin',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Canlı');
        // Akış sadece ilk ismi gösterir (soyadı sızdırmaz) — kapanan </strong>
        // etiketinin hemen önünde "Ahmet" olması, "Yılmaz" eklenmediğini kanıtlar.
        $response->assertSee('>Ahmet</strong>', false);
        $response->assertSee('yeni bir ilan paylaştı');
        $response->assertSee('Berlin');
    }

    public function test_activity_feed_excludes_inactive_listings(): void
    {
        $seller = User::factory()->create(['name' => 'Beklemede Kullanici']);
        Listing::factory()->for($seller)->create([
            'status' => 'beklemede',
            'category_id' => $this->leafCategory()->id,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Beklemede');
    }

    public function test_activity_feed_hidden_when_no_active_listings(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('activityTicker', false);
    }
}
