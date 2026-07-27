<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ZzOlcumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_olcum_show(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Category::create(['name' => 'Test', 'slug' => 'test', 'type' => 'hizmet', 'is_active' => true]);
        $category = Category::first();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);
        Review::create([
            'listing_id' => $listing->id,
            'reviewee_id' => $user->id,
            'reviewer_id' => $other->id,
            'rating' => 5,
            'comment' => 'iyi',
            'status' => 'yayinda',
        ]);

        foreach (['klasik', 'vitrin'] as $tema) {
            SiteSetting::query()->updateOrCreate(['key' => 'tema'], ['value' => $tema]);
            Cache::flush();
            \DB::enableQueryLog();
            $response = $this->get('/ilan/'.$listing->id.'/'.$listing->slug);
            $queries = \DB::getQueryLog();
            \DB::disableQueryLog();
            $response->assertOk();
            fwrite(STDERR, "\n=== SHOW tema={$tema} :: ".count($queries)." sorgu ===\n");
            foreach ($queries as $i => $q) {
                fwrite(STDERR, ($i + 1).'. '.$q['query']."\n");
            }
        }
        $this->assertTrue(true);
    }

    public function test_olcum_index(): void
    {
        $user = User::factory()->create();
        Category::create(['name' => 'Test', 'slug' => 'test', 'type' => 'hizmet', 'is_active' => true]);
        $category = Category::first();
        $listings = Listing::factory()->count(30)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);
        foreach ($listings as $l) {
            ListingImage::create([
                'listing_id' => $l->id,
                'path_thumb' => "listings/{$l->id}/thumb.webp",
                'path_medium' => "listings/{$l->id}/medium.webp",
                'path_large' => "listings/{$l->id}/large.webp",
                'sort_order' => 1,
                'is_cover' => true,
            ]);
        }

        foreach (['klasik', 'vitrin'] as $tema) {
            SiteSetting::query()->updateOrCreate(['key' => 'tema'], ['value' => $tema]);
            Cache::flush();
            \DB::enableQueryLog();
            $response = $this->get('/ilanlar');
            $queries = \DB::getQueryLog();
            \DB::disableQueryLog();
            $response->assertOk();
            fwrite(STDERR, "\n=== INDEX tema={$tema} :: ".count($queries)." sorgu ===\n");
            foreach ($queries as $i => $q) {
                fwrite(STDERR, ($i + 1).'. '.$q['query']."\n");
            }
        }
        $this->assertTrue(true);
    }
}
