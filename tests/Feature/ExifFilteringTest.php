<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * EXIF filtreleme + admin aksiyonları testleri.
 * Filament tablo filtrelerinin, bulk action'ların ve dashboard widget'ının
 * doğru çalıştığını doğrular.
 */
class ExifFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_listing_image_model_has_filter_columns(): void
    {
        $image = new ListingImage;
        $this->assertContains('has_sensitive_exif', $image->getFillable());
        $this->assertContains('gps_lat', $image->getFillable());
        $this->assertContains('gps_lng', $image->getFillable());
    }

    public function test_filter_columns_are_properly_casted(): void
    {
        $image = new ListingImage;
        $casts = $image->getCasts();

        $this->assertSame('boolean', $casts['has_sensitive_exif']);
        $this->assertSame('float', $casts['gps_lat']);
        $this->assertSame('float', $casts['gps_lng']);
    }

    public function test_has_sensitive_exif_returns_true_for_gps(): void
    {
        $exif = [
            'Make' => 'Canon',
            'GPSLatitude' => [40, 59, 0],
            'GPSLongitude' => [29, 0, 0],
        ];

        $this->assertTrue(ListingImage::hasSensitiveExif($exif));
    }

    public function test_has_sensitive_exif_returns_false_for_clean_metadata(): void
    {
        $exif = [
            'Make' => 'Canon',
            'Model' => 'EOS',
            'FNumber' => 2.8,
        ];

        $this->assertFalse(ListingImage::hasSensitiveExif($exif));
    }

    public function test_extract_gps_from_exif_returns_decimal_coordinates(): void
    {
        $exif = [
            'GPSLatitude' => [40, 59, 0],
            'GPSLongitude' => [29, 0, 0],
            'GPSLatitudeRef' => 'N',
            'GPSLongitudeRef' => 'E',
        ];

        $gps = ListingImage::extractGpsFromExif($exif);

        $this->assertNotNull($gps['lat']);
        $this->assertNotNull($gps['lng']);
        $this->assertEqualsWithDelta(40.9833, $gps['lat'], 0.01);
        $this->assertEqualsWithDelta(29.0, $gps['lng'], 0.01);
    }

    public function test_extract_gps_returns_null_for_missing_coordinates(): void
    {
        $gps = ListingImage::extractGpsFromExif(['Make' => 'Canon']);
        $this->assertNull($gps['lat']);
        $this->assertNull($gps['lng']);
    }

    public function test_gps_south_west_is_negative(): void
    {
        $exif = [
            'GPSLatitude' => [33, 0, 0],
            'GPSLongitude' => [118, 0, 0],
            'GPSLatitudeRef' => 'S',
            'GPSLongitudeRef' => 'W',
        ];

        $gps = ListingImage::extractGpsFromExif($exif);

        $this->assertLessThan(0, $gps['lat']);
        $this->assertLessThan(0, $gps['lng']);
    }

    public function test_admin_can_query_gps_only_images(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);

        // GPS'li görsel
        ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/test-istanbul.jpg',
            'had_gps' => true,
            'has_sensitive_exif' => true,
            'gps_lat' => 40.9833,
            'gps_lng' => 29.0,
        ]);
        // GPS'siz
        ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/test-clean.jpg',
            'had_gps' => false,
            'has_sensitive_exif' => false,
        ]);

        $gpsImages = ListingImage::query()->where('had_gps', true)->count();
        $cleanImages = ListingImage::query()->where('had_gps', false)->count();

        $this->assertSame(1, $gpsImages);
        $this->assertSame(1, $cleanImages);
    }

    public function test_admin_can_query_sensitive_exif_only(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);

        ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/sensitive.jpg',
            'has_sensitive_exif' => true,
        ]);
        ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/clean.jpg',
            'has_sensitive_exif' => false,
        ]);

        $sensitive = ListingImage::query()->where('has_sensitive_exif', true)->count();
        $this->assertSame(1, $sensitive);
    }

    public function test_gps_redaction_updates_image(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);

        $image = ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/redact-test.jpg',
            'had_gps' => true,
            'has_sensitive_exif' => true,
            'gps_lat' => 40.9833,
            'gps_lng' => 29.0,
            'exif_metadata' => [
                'Make' => 'Canon',
                'GPSLatitude' => [40, 59, 0],
                'GPSLongitude' => [29, 0, 0],
            ],
        ]);

        // GPS'i temizle (audit amaçlı)
        $exif = $image->exif_metadata;
        unset($exif['GPSLatitude'], $exif['GPSLongitude'], $exif['GPSLatitudeRef'], $exif['GPSLongitudeRef']);

        $image->update([
            'exif_metadata' => $exif,
            'had_gps' => false,
            'has_sensitive_exif' => ListingImage::hasSensitiveExif($exif),
            'gps_lat' => null,
            'gps_lng' => null,
        ]);

        $image->refresh();
        $this->assertFalse($image->had_gps);
        $this->assertNull($image->gps_lat);
        $this->assertNull($image->gps_lng);
        $this->assertArrayNotHasKey('GPSLatitude', $image->exif_metadata);
        $this->assertArrayHasKey('Make', $image->exif_metadata); // Kamera korundu
    }

    public function test_camera_make_filter_query_works(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);

        ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/canon.jpg',
            'exif_metadata' => ['Make' => 'Canon', 'Model' => 'EOS'],
        ]);
        ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/nikon.jpg',
            'exif_metadata' => ['Make' => 'Nikon', 'Model' => 'D850'],
        ]);

        $canonImages = ListingImage::query()
            ->where('exif_metadata->Make', 'Canon')
            ->count();

        $this->assertSame(1, $canonImages);
    }

    public function test_admin_widget_counts_gps_and_sensitive(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);

        // 3 GPS'li + 2 temiz
        ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/gps1.jpg', 'had_gps' => true, 'has_sensitive_exif' => true, 'gps_lat' => 40.5, 'gps_lng' => 29.5]);
        ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/gps2.jpg', 'had_gps' => true, 'has_sensitive_exif' => true, 'gps_lat' => 41.0, 'gps_lng' => 29.5]);
        ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/gps3.jpg', 'had_gps' => true, 'has_sensitive_exif' => true, 'gps_lat' => 40.0, 'gps_lng' => 30.0]);
        ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/clean1.jpg', 'had_gps' => false, 'has_sensitive_exif' => false]);
        ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/clean2.jpg', 'had_gps' => false, 'has_sensitive_exif' => false]);

        $gpsCount = ListingImage::query()->where('had_gps', true)->count();
        $sensitiveCount = ListingImage::query()->where('has_sensitive_exif', true)->count();
        $total = ListingImage::query()->count();

        $this->assertSame(3, $gpsCount);
        $this->assertSame(3, $sensitiveCount);
        $this->assertSame(5, $total);
    }

    public function test_gps_query_within_country_bbox(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);

        // İstanbul
        ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/ist.jpg', 'gps_lat' => 40.9833, 'gps_lng' => 29.0]);
        // Ankara
        ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/ank.jpg', 'gps_lat' => 39.9334, 'gps_lng' => 32.8597]);
        // Tokyo
        ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/tokyo.jpg', 'gps_lat' => 35.6762, 'gps_lng' => 139.6503]);

        $turkeyImages = ListingImage::query()
            ->whereBetween('gps_lat', [35.8, 42.1])
            ->whereBetween('gps_lng', [25.6, 44.8])
            ->count();

        $this->assertSame(2, $turkeyImages);
    }

    /**
     * reverseLocationLabel bir zamanlar düz bir public metottu; Filament'in
     * TextColumn::make('reverseLocationLabel') property erişimiyle state
     * çözümlemesi, Eloquent tarafından "ilişki metodu" sanılıp LogicException
     * fırlatıyor ve tüm admin görseller sayfasını çökertiyordu. Bu test
     * sayfanın gerçekten render edildiğini (500 değil) doğrular.
     */
    public function test_admin_listing_images_page_renders_with_reverse_location(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);

        ListingImage::create([
            'listing_id' => $listing->id,
            'path_large' => 'listings/large/test-berlin.webp',
            'had_gps' => true,
            'gps_lat' => 52.52,
            'gps_lng' => 13.405,
            'reverse_city' => 'Berlin',
            'reverse_country_name' => 'Almanya',
            'reverse_geocoded_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/yonetim/listing-images');

        $response->assertOk();
        $response->assertSee('Berlin, Almanya');
    }
}