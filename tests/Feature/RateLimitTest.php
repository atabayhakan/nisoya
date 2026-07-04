<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Rate limit policy'lerinin admin ve public endpoint'lerde düzgün
 * çalıştığını doğrular. Brute force + DoS koruması.
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
        RateLimiter::clear('health-basic');
        RateLimiter::clear('health-detailed');
        RateLimiter::clear('exif-map');
    }

    public function test_health_basic_allows_60_requests_per_minute(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $response = $this->get('/health');
            $response->assertOk();
        }
    }

    public function test_health_basic_blocks_61st_request(): void
    {
        // 60 istek başarılı
        for ($i = 0; $i < 60; $i++) {
            $this->get('/health');
        }

        // 61. istek rate limit yüzünden 429 dönmeli
        $response = $this->get('/health');
        $response->assertStatus(429);
    }

    public function test_health_basic_rate_limit_is_per_ip(): void
    {
        // Bir IP 60 istek yapabilir, ama farklı IP'ler bağımsız
        for ($i = 0; $i < 60; $i++) {
            $this->get('/health', ['REMOTE_ADDR' => '10.0.0.1']);
        }

        // Farklı IP'den 60 istek daha — yine OK olmalı
        for ($i = 0; $i < 60; $i++) {
            $response = $this->get('/health', ['REMOTE_ADDR' => '10.0.0.2']);
            $response->assertOk();
        }
    }

    public function test_health_detailed_requires_admin(): void
    {
        // Guest (admin değil) → redirect
        $response = $this->get('/yonetim/health/detailed');
        $response->assertRedirect();
    }

    public function test_health_detailed_works_for_admin(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Aktif,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/yonetim/health/detailed');
        $response->assertOk();
        $response->assertJsonStructure(['status', 'checks' => ['database', 'cache', 'storage']]);
    }

    public function test_health_detailed_blocked_after_30_requests(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Aktif,
            'email_verified_at' => now(),
        ]);

        for ($i = 0; $i < 30; $i++) {
            $response = $this->actingAs($admin)->get('/yonetim/health/detailed');
            $response->assertOk();
        }

        // 31. istek rate limit yüzünden 429
        $response = $this->actingAs($admin)->get('/yonetim/health/detailed');
        $response->assertStatus(429);
    }

    public function test_exif_map_api_rate_limited_per_admin(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Aktif,
            'email_verified_at' => now(),
        ]);

        // Admin'in kendi ilanına GPS ata (factory kullanmadan doğrudan)
        $user = User::factory()->create();
        Category::create([
            'parent_id' => null,
            'name' => 'Test Kategori',
            'slug' => 'test-kategori',
            'type' => 'hizmet',
            'is_active' => true,
        ]);
        $category = Category::where('slug', 'test-kategori')->first();

        $listing = Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => 'hizmet',
            'title' => 'Test Listing',
            'slug' => 'test-listing',
            'description' => 'Test description',
            'currency' => 'EUR',
            'price_unit' => 'gorusulur',
            'country_code' => 'TR',
            'city' => 'İstanbul',
            'gps_lat' => 41.0,
            'gps_lng' => 29.0,
            'is_remote' => false,
            'status' => ListingStatus::Aktif->value,
        ]);

        ListingImage::create([
            'listing_id' => $listing->id,
            'path_thumb' => 'listings/thumb/test.webp',
            'path_medium' => 'listings/medium/test.webp',
            'path_large' => 'listings/large/test.webp',
            'gps_lat' => 41.0,
            'gps_lng' => 29.0,
            'had_gps' => true,
            'sort_order' => 1,
            'is_cover' => true,
        ]);

        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($admin)->get('/yonetim/harita/gorseller');
            $response->assertOk();
        }

        // 61. istek rate limit
        $response = $this->actingAs($admin)->get('/yonetim/harita/gorseller');
        $response->assertStatus(429);
    }

    public function test_different_admins_have_separate_rate_limits(): void
    {
        $adminA = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Aktif,
            'email_verified_at' => now(),
        ]);
        $adminB = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Aktif,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($adminA)->get('/yonetim/health/detailed');
        $this->actingAs($adminA)->get('/yonetim/health/detailed');

        // adminA 2 istek yaptı; adminB bağımsız rate limiter
        $response = $this->actingAs($adminB)->get('/yonetim/health/detailed');
        $response->assertOk();
    }

    public function test_rate_limit_response_contains_retry_after_header(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Aktif,
            'email_verified_at' => now(),
        ]);

        for ($i = 0; $i < 31; $i++) {
            $this->actingAs($admin)->get('/yonetim/health/detailed');
        }
        $response = $this->actingAs($admin)->get('/yonetim/health/detailed');
        $response->assertStatus(429);
        // Retry-After header olmalı (RFC 6585)
        $this->assertNotNull($response->headers->get('Retry-After'));
    }
}
