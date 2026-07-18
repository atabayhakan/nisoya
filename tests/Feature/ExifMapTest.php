<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ExifMapPage;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EXIF haritası testleri.
 * Admin panelinde GPS koordinatı olan görselleri haritada gösterme.
 */
class ExifMapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    protected function createAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
    }

    protected function createListingWithImage(array $imageAttrs): array
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);
        $image = ListingImage::create(array_merge([
            'listing_id' => $listing->id,
            'path' => 'listings/test.jpg',
        ], $imageAttrs));

        return [$user, $listing, $image];
    }

    public function test_admin_can_access_exif_map_page(): void
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->get('/yonetim/exif-map-page');
        $response->assertOk();
        $response->assertSee('EXIF Haritası');
    }

    public function test_non_admin_cannot_access_exif_map_page(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Uye,
            'email_verified_at' => now(),
        ]);
        // Üye admin panele erişemez → çıplak 403 yerine kendi paneline yönlendirilir.
        $this->actingAs($user)->get('/yonetim/exif-map-page')->assertRedirect(route('dashboard'));
    }

    public function test_guest_cannot_access_exif_map_api(): void
    {
        $response = $this->get('/yonetim/harita/gorseller');
        $response->assertRedirect();
    }

    public function test_api_returns_only_gps_images(): void
    {
        $admin = $this->createAdmin();

        // 3 GPS'li, 2 GPS'siz
        $this->createListingWithImage(['gps_lat' => 41.0, 'gps_lng' => 29.0]);
        $this->createListingWithImage(['gps_lat' => 39.9, 'gps_lng' => 32.8]);
        $this->createListingWithImage(['gps_lat' => 38.7, 'gps_lng' => 35.5]);
        $this->createListingWithImage([]); // GPS'siz
        $this->createListingWithImage([]); // GPS'siz

        $response = $this->actingAs($admin)->get('/yonetim/harita/gorseller');
        $response->assertOk();

        $data = $response->json();
        $this->assertSame(3, $data['count']);
        $this->assertCount(3, $data['markers']);
    }

    public function test_api_markers_contain_required_fields(): void
    {
        $admin = $this->createAdmin();

        $this->createListingWithImage([
            'gps_lat' => 41.0082,
            'gps_lng' => 28.9784,
            'had_gps' => true,
            'has_sensitive_exif' => true,
            'exif_metadata' => ['Make' => 'Canon', 'Model' => 'EOS'],
        ]);

        $response = $this->actingAs($admin)->get('/yonetim/harita/gorseller');
        $data = $response->json();

        $marker = $data['markers'][0];
        $this->assertArrayHasKey('id', $marker);
        $this->assertArrayHasKey('lat', $marker);
        $this->assertArrayHasKey('lng', $marker);
        $this->assertArrayHasKey('thumb', $marker);
        $this->assertArrayHasKey('sensitive', $marker);
        $this->assertArrayHasKey('listing', $marker);
        $this->assertArrayHasKey('user', $marker);
        $this->assertArrayHasKey('camera', $marker);
        $this->assertEqualsWithDelta(41.0082, $marker['lat'], 0.001);
        $this->assertTrue($marker['sensitive']);
        $this->assertSame('Canon', $marker['camera']);
    }

    public function test_api_filters_by_sensitive_only(): void
    {
        $admin = $this->createAdmin();

        $this->createListingWithImage(['gps_lat' => 41.0, 'gps_lng' => 29.0, 'has_sensitive_exif' => true]);
        $this->createListingWithImage(['gps_lat' => 39.9, 'gps_lng' => 32.8, 'has_sensitive_exif' => false]);
        $this->createListingWithImage(['gps_lat' => 38.7, 'gps_lng' => 35.5, 'has_sensitive_exif' => true]);

        $response = $this->actingAs($admin)->get('/yonetim/harita/gorseller?sensitive=1');
        $data = $response->json();
        $this->assertSame(2, $data['count']);
    }

    public function test_api_filters_by_bounding_box(): void
    {
        $admin = $this->createAdmin();

        // Türkiye sınırları içinde 2, dışında 1
        $this->createListingWithImage(['gps_lat' => 41.0, 'gps_lng' => 29.0]);   // İstanbul
        $this->createListingWithImage(['gps_lat' => 39.9, 'gps_lng' => 32.8]);   // Ankara
        $this->createListingWithImage(['gps_lat' => 35.6, 'gps_lng' => 139.6]);  // Tokyo

        // Türkiye sınırları: 36-42 N, 26-45 E
        $response = $this->actingAs($admin)
            ->get('/yonetim/harita/gorseller?bbox=36,26,42,45');

        $data = $response->json();
        $this->assertSame(2, $data['count']);
    }

    public function test_api_respects_limit(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 10; $i++) {
            $this->createListingWithImage([
                'gps_lat' => 41.0 + ($i * 0.001),
                'gps_lng' => 29.0 + ($i * 0.001),
            ]);
        }

        $response = $this->actingAs($admin)->get('/yonetim/harita/gorseller?limit=5');
        $data = $response->json();
        $this->assertSame(5, $data['count']);
    }

    public function test_api_limit_max_is_2000(): void
    {
        $admin = $this->createAdmin();
        // 9999 > 2000 max — validation hatası
        // Web route'ta validate() exception fırlatır → 302 redirect (back with errors)
        $response = $this->actingAs($admin)->get('/yonetim/harita/gorseller?limit=9999');
        // Herhangi bir hata kodu: 302 (web redirect) / 422 (API) / 500 (exception)
        $this->assertGreaterThanOrEqual(300, $response->getStatusCode());
    }

    public function test_api_returns_bounds(): void
    {
        $admin = $this->createAdmin();

        $this->createListingWithImage(['gps_lat' => 41.0, 'gps_lng' => 29.0]);
        $this->createListingWithImage(['gps_lat' => 39.0, 'gps_lng' => 32.0]);
        $this->createListingWithImage(['gps_lat' => 40.0, 'gps_lng' => 30.0]);

        $response = $this->actingAs($admin)->get('/yonetim/harita/gorseller');
        $data = $response->json();

        $this->assertArrayHasKey('bounds', $data);
        $this->assertEqualsWithDelta(39.0, $data['bounds']['south'], 0.01);
        $this->assertEqualsWithDelta(41.0, $data['bounds']['north'], 0.01);
        $this->assertEqualsWithDelta(29.0, $data['bounds']['west'], 0.01);
        $this->assertEqualsWithDelta(32.0, $data['bounds']['east'], 0.01);
    }

    public function test_api_bounds_null_when_no_images(): void
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->get('/yonetim/harita/gorseller');
        $data = $response->json();

        $this->assertSame(0, $data['count']);
        $this->assertNull($data['bounds']);
    }

    public function test_clusters_api_groups_nearby_images(): void
    {
        $admin = $this->createAdmin();

        // 3 görsel İstanbul'da, 2 Ankara'da
        $this->createListingWithImage(['gps_lat' => 41.008, 'gps_lng' => 28.978]);
        $this->createListingWithImage(['gps_lat' => 41.009, 'gps_lng' => 28.979]);
        $this->createListingWithImage(['gps_lat' => 41.010, 'gps_lng' => 28.980]);
        $this->createListingWithImage(['gps_lat' => 39.933, 'gps_lng' => 32.859]);
        $this->createListingWithImage(['gps_lat' => 39.934, 'gps_lng' => 32.860]);

        $response = $this->actingAs($admin)->get('/yonetim/harita/cluster?tolerance=0.01');
        $data = $response->json();

        $this->assertSame(5, $data['total_images']);
        $this->assertSame(2, $data['cluster_count']); // İstanbul + Ankara

        // En büyük cluster İstanbul (3 görsel)
        $top = $data['clusters'][0];
        $this->assertSame(3, $top['count']);
    }

    public function test_clusters_sorted_by_count_desc(): void
    {
        $admin = $this->createAdmin();

        // 1 İstanbul
        $this->createListingWithImage(['gps_lat' => 41.0, 'gps_lng' => 29.0]);
        // 5 Ankara
        for ($i = 0; $i < 5; $i++) {
            $this->createListingWithImage(['gps_lat' => 39.9 + ($i * 0.001), 'gps_lng' => 32.8]);
        }

        $response = $this->actingAs($admin)->get('/yonetim/harita/cluster?tolerance=0.01');
        $data = $response->json();

        $this->assertSame(5, $data['clusters'][0]['count']);
        $this->assertSame(1, $data['clusters'][1]['count']);
    }

    public function test_stats_api_returns_summary(): void
    {
        $admin = $this->createAdmin();

        $this->createListingWithImage(['gps_lat' => 41.0, 'gps_lng' => 29.0]);
        $this->createListingWithImage(['gps_lat' => 39.9, 'gps_lng' => 32.8]);

        $response = $this->actingAs($admin)->get('/yonetim/harita/istatistik');
        $data = $response->json();

        $this->assertSame(2, $data['total_gps_images']);
        $this->assertArrayHasKey('cluster_count', $data);
    }

    public function test_listing_image_model_has_with_gps_scope(): void
    {
        $this->createListingWithImage(['gps_lat' => 41.0, 'gps_lng' => 29.0]);
        $this->createListingWithImage([]); // GPS'siz

        $gps = ListingImage::query()->withGps()->count();
        $all = ListingImage::query()->count();

        $this->assertSame(1, $gps);
        $this->assertSame(2, $all);
    }

    public function test_within_bounds_scope_filters_geographically(): void
    {
        $this->createListingWithImage(['gps_lat' => 41.0, 'gps_lng' => 29.0]);
        $this->createListingWithImage(['gps_lat' => 39.9, 'gps_lng' => 32.8]);
        $this->createListingWithImage(['gps_lat' => 35.6, 'gps_lng' => 139.6]);

        $count = ListingImage::query()->withGps()->withinBounds(36, 42, 26, 45)->count();
        $this->assertSame(2, $count);
    }

    public function test_near_coordinates_scope_finds_close_images(): void
    {
        // Aynı yere yakın 3 görsel
        $this->createListingWithImage(['gps_lat' => 41.0082, 'gps_lng' => 28.9784]);
        $this->createListingWithImage(['gps_lat' => 41.0083, 'gps_lng' => 28.9785]);
        // Uzak görsel
        $this->createListingWithImage(['gps_lat' => 39.9, 'gps_lng' => 32.8]);

        $count = ListingImage::query()
            ->nearCoordinates(41.0082, 28.9784, 0.001)
            ->count();

        $this->assertSame(2, $count);
    }

    public function test_api_marker_thumb_url_falls_back_to_path(): void
    {
        $admin = $this->createAdmin();
        [, , $image] = $this->createListingWithImage([
            'gps_lat' => 41.0,
            'gps_lng' => 29.0,
            'path' => 'listings/legacy.jpg',
        ]);

        $response = $this->actingAs($admin)->get('/yonetim/harita/gorseller');
        $data = $response->json();

        // Varyant path'leri olmadan thumb fallback → path URL'i
        $this->assertNotNull($data['markers'][0]['thumb']);
    }

    public function test_admin_navigation_badge_shows_count(): void
    {
        $this->createListingWithImage(['gps_lat' => 41.0, 'gps_lng' => 29.0]);
        $this->createListingWithImage(['gps_lat' => 39.9, 'gps_lng' => 32.8]);
        $this->createListingWithImage(['gps_lat' => 38.7, 'gps_lng' => 35.5]);

        $badge = ExifMapPage::getNavigationBadge();
        $this->assertSame('3', $badge);
    }
}
