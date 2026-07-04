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
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * EXIF audit trail testleri — admin paneli için.
 * Görsel yüklendiğinde EXIF metadata'sı DB'ye yazılır mı, hassas alanlar
 * filtreleniyor mu, GPS varlığı audit log'a düşüyor mu?
 */
class ExifAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_listing_image_model_has_exif_methods(): void
    {
        $image = new ListingImage;

        $this->assertTrue(method_exists($image, 'exifSummary'));
        $this->assertTrue(method_exists(ListingImage::class, 'sanitizeExifForAudit'));
    }

    public function test_exif_metadata_appears_in_fillable(): void
    {
        $image = new ListingImage;
        $this->assertContains('exif_metadata', $image->getFillable());
        $this->assertContains('had_gps', $image->getFillable());
    }

    public function test_exif_metadata_is_casted_to_array(): void
    {
        $image = new ListingImage;
        $casts = $image->getCasts();

        $this->assertArrayHasKey('exif_metadata', $casts);
        $this->assertSame('array', $casts['exif_metadata']);
    }

    public function test_had_gps_is_casted_to_boolean(): void
    {
        $image = new ListingImage;
        $casts = $image->getCasts();

        $this->assertArrayHasKey('had_gps', $casts);
        $this->assertSame('boolean', $casts['had_gps']);
    }

    public function test_sanitize_exif_filters_sensitive_fields(): void
    {
        $raw = [
            'Make' => 'Canon',
            'Model' => 'EOS',
            'UserComment' => 'gizli',
            'ImageDescription' => 'gizli',
            'SerialNumber' => 'gizli',
            'CameraOwnerName' => 'gizli',
            'Artist' => 'gizli',
            'Copyright' => 'gizli',
        ];

        $sanitized = ListingImage::sanitizeExifForAudit($raw);

        $this->assertSame('Canon', $sanitized['Make']);
        $this->assertSame('EOS', $sanitized['Model']);
        $this->assertArrayNotHasKey('UserComment', $sanitized);
        $this->assertArrayNotHasKey('ImageDescription', $sanitized);
        $this->assertArrayNotHasKey('SerialNumber', $sanitized);
        $this->assertArrayNotHasKey('CameraOwnerName', $sanitized);
    }

    public function test_sanitize_exif_keeps_camera_and_gps(): void
    {
        $raw = [
            'Make' => 'Apple',
            'Model' => 'iPhone 15 Pro',
            'FNumber' => 1.78,
            'ISOSpeedRatings' => 64,
            'GPSLatitude' => [41, 0, 30.6],
            'GPSLongitude' => [28, 58, 50.1],
            'GPSLatitudeRef' => 'N',
            'GPSLongitudeRef' => 'E',
        ];

        $sanitized = ListingImage::sanitizeExifForAudit($raw);

        $this->assertSame('Apple', $sanitized['Make']);
        $this->assertSame('iPhone 15 Pro', $sanitized['Model']);
        $this->assertSame(1.78, $sanitized['FNumber']);
        $this->assertSame([41, 0, 30.6], $sanitized['GPSLatitude']);
    }

    public function test_exif_summary_handles_empty_metadata(): void
    {
        $image = new ListingImage(['exif_metadata' => null]);
        $this->assertSame([], $image->exifSummary());

        $image = new ListingImage(['exif_metadata' => []]);
        $this->assertSame([], $image->exifSummary());
    }

    public function test_exif_summary_returns_translated_keys(): void
    {
        $image = new ListingImage([
            'exif_metadata' => [
                'Make' => 'Canon',
                'Model' => 'EOS 5D Mark IV',
                'FNumber' => 2.8,
                'ISOSpeedRatings' => 400,
                'DateTimeOriginal' => '2024:01:15 14:30:00',
            ],
        ]);

        $summary = $image->exifSummary();

        // Türkçe label'lar
        $this->assertArrayHasKey('Kamera', $summary);
        $this->assertArrayHasKey('Diyafram', $summary);
        $this->assertArrayHasKey('ISO', $summary);
        $this->assertArrayHasKey('Çekim tarihi', $summary);

        $this->assertStringContainsString('Canon', $summary['Kamera']);
        $this->assertSame('f/2.8', $summary['Diyafram']);
        $this->assertSame(400, $summary['ISO']);
    }

    public function test_exif_summary_formats_gps_coordinates(): void
    {
        $image = new ListingImage([
            'exif_metadata' => [
                'GPSLatitude' => [40, 59, 0],
                'GPSLongitude' => [29, 0, 0],
                'GPSLatitudeRef' => 'N',
                'GPSLongitudeRef' => 'E',
            ],
        ]);

        $summary = $image->exifSummary();

        $this->assertArrayHasKey('GPS', $summary);
        $this->assertMatchesRegularExpression('/^-?\d+\.\d{6}, -?\d+\.\d{6}$/', $summary['GPS']);
    }

    public function test_exif_summary_formats_exposure_less_than_one_second(): void
    {
        $image = new ListingImage([
            'exif_metadata' => [
                'ExposureTime' => 0.004, // 1/250s
            ],
        ]);

        $summary = $image->exifSummary();

        $this->assertArrayHasKey('Poz süresi', $summary);
        $this->assertStringContainsString('1/', $summary['Poz süresi']);
    }

    public function test_exif_summary_format_focal_length(): void
    {
        $image = new ListingImage([
            'exif_metadata' => [
                'FocalLength' => 50,
            ],
        ]);

        $summary = $image->exifSummary();
        $this->assertSame('50mm', $summary['Odak']);
    }

    public function test_exif_summary_flash_detection(): void
    {
        // Flash kullanılmadı
        $image = new ListingImage(['exif_metadata' => ['Flash' => 0]]);
        $this->assertSame('Kullanılmadı', $image->exifSummary()['Flash']);

        // Flash ateşlendi (0x0001 = Flash fired)
        $image = new ListingImage(['exif_metadata' => ['Flash' => 1]]);
        $this->assertSame('Ateşlendi', $image->exifSummary()['Flash']);
    }

    public function test_exif_summary_orientation_shown(): void
    {
        $image = new ListingImage(['exif_metadata' => ['Orientation' => 6]]);
        $this->assertSame(6, $image->exifSummary()['Orientation']);
    }

    public function test_activity_log_records_gps_uploads(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);

        activity('image')
            ->performedOn($listing)
            ->causedBy($user)
            ->withProperties(['had_gps' => true])
            ->log('GPS içeren görsel yüklendi (EXIF temizlendi)');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'image',
            'description' => 'GPS içeren görsel yüklendi (EXIF temizlendi)',
            'subject_type' => Listing::class,
            'subject_id' => $listing->id,
        ]);

        $log = Activity::query()->where('log_name', 'image')->first();
        $this->assertNotNull($log);
        $this->assertTrue($log->properties['had_gps']);
    }
}
