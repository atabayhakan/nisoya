<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\ImageService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ImageService'in edge case'lerde (bozuk dosya, geçersiz EXIF, büyük görsel)
 * doğru davrandığını doğrular. Production'da kullanıcılar bozuk dosyalar
 * yükleyebilir; service bunları graceful handle etmeli (crash değil).
 */
class ExifErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_text_file_uploaded_as_image_throws_runtime_exception(): void
    {
        // Metin dosyası görsel olarak yüklendiğinde RuntimeException fırlatır
        // (controller'da try/catch ile yakalanır, kullanıcıya hata gösterilir).
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Görsel işlenemedi');

        $txtFile = UploadedFile::fake()->createWithContent('not-an-image.txt', 'Bu bir metin dosyasıdır');

        $service = app(ImageService::class);
        $service->storeOptimized($txtFile, 'listings');
    }

    public function test_corrupt_jpeg_throws_runtime_exception(): void
    {
        // JPEG magic bytes var ama içerik bozuk → RuntimeException
        $this->expectException(\RuntimeException::class);

        $corruptJpeg = UploadedFile::fake()->createWithContent(
            'corrupt.jpg',
            "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100)
        );

        $service = app(ImageService::class);
        $service->storeOptimized($corruptJpeg, 'listings');
    }

    public function test_empty_file_throws_runtime_exception(): void
    {
        $this->expectException(\RuntimeException::class);

        $emptyFile = UploadedFile::fake()->create('empty.jpg', 0);
        $service = app(ImageService::class);
        $service->storeOptimized($emptyFile, 'listings');
    }

    public function test_png_without_exif_strips_correctly(): void
    {
        // EXIF'siz PNG — sadece orientation/dimensions
        $pngFile = UploadedFile::fake()->image('no-exif.png', 800, 600);

        $service = app(ImageService::class);
        $result = $service->storeOptimized($pngFile, 'listings');

        $this->assertFalse($result['orientation_corrected']);
        $this->assertEmpty($result['exif_metadata']);
        $this->assertSame(800, $result['original_dimensions']['width']);
        $this->assertSame(600, $result['original_dimensions']['height']);
    }

    public function test_three_variants_are_always_returned(): void
    {
        $image = UploadedFile::fake()->image('test.jpg', 1200, 900);
        $service = app(ImageService::class);
        $result = $service->storeOptimized($image, 'listings');

        // thumb, medium, large her zaman mevcut
        $this->assertArrayHasKey('thumb', $result);
        $this->assertArrayHasKey('medium', $result);
        $this->assertArrayHasKey('large', $result);

        // thumb < medium < large dosya yolları
        $this->assertStringContainsString('thumb', $result['thumb']);
        $this->assertStringContainsString('medium', $result['medium']);
        $this->assertStringContainsString('large', $result['large']);
    }

    public function test_store_optimized_creates_valid_variants_in_db(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => \App\Models\Category::first()->id,
            'status' => \App\Enums\ListingStatus::Aktif,
        ]);

        $image = UploadedFile::fake()->image('test.jpg', 1000, 750);
        $service = app(ImageService::class);
        $result = $service->storeOptimized($image, 'listings');

        $listingImage = $listing->images()->create([
            'path_thumb' => $result['thumb'],
            'path_medium' => $result['medium'],
            'path_large' => $result['large'],
            'width' => $result['original_dimensions']['width'],
            'height' => $result['original_dimensions']['height'],
            'sort_order' => 1,
            'is_cover' => true,
        ]);

        // Tüm varyantlar gerçekten disk'te var mı?
        Storage::disk('public')->assertExists($listingImage->path_thumb);
        Storage::disk('public')->assertExists($listingImage->path_medium);
        Storage::disk('public')->assertExists($listingImage->path_large);
    }

    public function test_exif_orientation_1_image_unchanged(): void
    {
        // EXIF Orientation=1 (normal) — düzeltme yok
        $image = $this->createJpegWithOrientation(1200, 900, 1);
        $service = app(ImageService::class);
        $result = $service->storeOptimized($image, 'listings');

        $this->assertFalse($result['orientation_corrected']);
    }

    public function test_store_optimized_handles_missing_extension_throws_exception(): void
    {
        // Uzantısız dosya → RuntimeException (controller yakalar)
        $this->expectException(\RuntimeException::class);

        $image = UploadedFile::fake()->createWithContent('noext', "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100));

        $service = app(ImageService::class);
        $service->storeOptimized($image, 'listings');
    }

    public function test_exif_sanitization_removes_user_data_but_keeps_safe_fields(): void
    {
        // Unit test: sanitization helper
        $exif = [
            'Make' => 'Canon',
            'Model' => 'EOS',
            'GPSLatitude' => [40, 59, 0],
            'UserComment' => 'Hassas not',
            'SerialNumber' => '12345',
            'ImageDescription' => 'Özel açıklama',
        ];

        $sanitized = ListingImage::sanitizeExifForAudit($exif);

        // İzinli alanlar kalmalı
        $this->assertSame('Canon', $sanitized['Make']);
        $this->assertSame('EOS', $sanitized['Model']);
        $this->assertEquals([40, 59, 0], $sanitized['GPSLatitude']);

        // Hassas alanlar filtrelenmeli
        $this->assertArrayNotHasKey('UserComment', $sanitized);
        $this->assertArrayNotHasKey('SerialNumber', $sanitized);
        $this->assertArrayNotHasKey('ImageDescription', $sanitized);
    }

    public function test_has_sensitive_exif_detects_gps(): void
    {
        $this->assertTrue(ListingImage::hasSensitiveExif(['GPSLatitude' => [40, 59, 0]]));
        $this->assertTrue(ListingImage::hasSensitiveExif(['GPSAltitude' => 100]));
        $this->assertFalse(ListingImage::hasSensitiveExif(['Make' => 'Canon']));
        $this->assertFalse(ListingImage::hasSensitiveExif([]));
    }

    /**
     * Test amaçlı JPEG oluştur + EXIF orientation ekle.
     * Production'da Intervention Image'ın $manager->read() metodu
     * bu EXIF tag'ini okur ve düzeltme uygular.
     */
    private function createJpegWithOrientation(int $width, int $height, int $orientation): UploadedFile
    {
        // GD ile küçük JPEG oluştur
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_').'.jpg';
        $img = imagecreatetruecolor($width, $height);
        imagecolorallocate($img, 100, 100, 100);
        imagejpeg($img, $tmpPath, 90);
        imagedestroy($img);

        // EXIF APP1 segmenti ekle
        $exifSegment = $this->buildExifOrientationSegment($orientation);
        $jpeg = file_get_contents($tmpPath);
        $patched = substr($jpeg, 0, 2) . $exifSegment . substr($jpeg, 2);
        file_put_contents($tmpPath, $patched);

        return new UploadedFile($tmpPath, 'test.jpg', 'image/jpeg', null, true);
    }

    private function buildExifOrientationSegment(int $orientation): string
    {
        $tiffHeader = "\x49\x49\x2A\x00\x08\x00\x00\x00";
        $ifd = pack('v', 1);
        $ifd .= pack('vv', 0x0112, 3);
        $ifd .= pack('VV', 1, $orientation);
        $ifd .= pack('V', 0);
        $app1Data = "Exif\0\0" . $tiffHeader . $ifd;
        return "\xFF\xE1" . pack('n', strlen($app1Data) + 2) . $app1Data;
    }
}
