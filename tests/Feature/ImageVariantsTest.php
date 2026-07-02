<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageVariantsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_listing_image_model_has_variant_path_columns(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/large/abc.webp',
            'path_thumb' => 'listings/thumb/abc.webp',
            'path_medium' => 'listings/medium/abc.webp',
            'path_large' => 'listings/large/abc.webp',
            'sort_order' => 1,
            'is_cover' => true,
        ]);

        $this->assertSame('listings/thumb/abc.webp', $image->path_thumb);
        $this->assertSame('listings/medium/abc.webp', $image->path_medium);
        $this->assertSame('listings/large/abc.webp', $image->path_large);
    }

    public function test_listing_image_url_helper_returns_url(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/legacy.jpg',
            'sort_order' => 1,
            'is_cover' => true,
        ]);

        // url() her durumda path'ten URL üretir; varyant path'leri null ise
        // orijinal path'e fallback yapar
        $expectedUrl = \Illuminate\Support\Facades\Storage::disk('public')->url('listings/legacy.jpg');
        $this->assertSame($expectedUrl, $image->url('thumb'));
        $this->assertSame($expectedUrl, $image->url('medium'));
        $this->assertSame($expectedUrl, $image->url('large'));
    }

    public function test_url_returns_null_when_no_path_exists(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);

        $image = new ListingImage(['path' => null, 'sort_order' => 1, 'is_cover' => true]);
        $image->listing_id = $listing->id;

        $this->assertNull($image->url('thumb'));
        $this->assertNull($image->url('medium'));
        $this->assertNull($image->url('large'));
    }

    public function test_variant_paths_helper_returns_all_paths(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/large/x.webp',
            'path_thumb' => 'listings/thumb/x.webp',
            'path_medium' => 'listings/medium/x.webp',
            'path_large' => 'listings/large/x.webp',
            'sort_order' => 1,
            'is_cover' => true,
        ]);

        $paths = $image->variantPaths();
        $this->assertCount(4, $paths);
        $this->assertContains('listings/large/x.webp', $paths);
        $this->assertContains('listings/thumb/x.webp', $paths);
        $this->assertContains('listings/medium/x.webp', $paths);
    }

    public function test_listing_card_partial_renders_with_variants(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);

        $listing->images()->create([
            'path' => 'listings/large/cover.webp',
            'path_thumb' => 'listings/thumb/cover.webp',
            'path_medium' => 'listings/medium/cover.webp',
            'path_large' => 'listings/large/cover.webp',
            'sort_order' => 1,
            'is_cover' => true,
        ]);

        $response = $this->get(route('listings.index'));
        $response->assertOk();
        $response->assertSee('srcset=', false);
        $response->assertSee('sizes=', false);
    }

    public function test_listing_show_renders_with_responsive_srcset(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);

        $listing->images()->create([
            'path' => 'listings/large/hero.webp',
            'path_thumb' => 'listings/thumb/hero.webp',
            'path_medium' => 'listings/medium/hero.webp',
            'path_large' => 'listings/large/hero.webp',
            'sort_order' => 1,
            'is_cover' => true,
        ]);

        $response = $this->get(route('listings.show', $listing));
        $response->assertOk();
        $response->assertSee('sizes=', false);
        $response->assertSee('fetchpriority="high"', false);
    }

    public function test_listing_image_srcset_helper(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/large/abc.webp',
            'path_thumb' => 'listings/thumb/abc.webp',
            'path_medium' => 'listings/medium/abc.webp',
            'path_large' => 'listings/large/abc.webp',
            'sort_order' => 1,
            'is_cover' => true,
        ]);

        $srcset = $image->srcset();
        $this->assertIsArray($srcset);
        $this->assertCount(3, $srcset); // thumb, medium, large
        $this->assertArrayHasKey('thumb', $srcset);
        $this->assertArrayHasKey('medium', $srcset);
        $this->assertArrayHasKey('large', $srcset);
    }

    public function test_listing_create_form_supports_image_uploads(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/panel/ilan/yeni');
        $response->assertOk();
        $response->assertSee('images[]', false);
        $response->assertSee('accept="image/*"', false);
    }
}