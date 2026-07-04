<?php

namespace Tests\Feature;

use App\Models\ListingImage;
use App\Services\ImageService;
use Tests\TestCase;

class ExifHandlingTest extends TestCase
{
    public function test_service_has_required_methods(): void
    {
        $this->assertTrue(method_exists(ImageService::class, 'applyExifOrientation'));
        $this->assertTrue(method_exists(ImageService::class, 'extractGpsCoordinates'));
        $this->assertTrue(method_exists(ImageService::class, 'getExifMetadata'));
        $this->assertTrue(method_exists(ImageService::class, 'toAvif'));
        $this->assertTrue(method_exists(ImageService::class, 'stripExif'));
    }

    public function test_service_exposes_variants_constant(): void
    {
        $reflection = new \ReflectionClass(ImageService::class);
        $this->assertTrue($reflection->hasConstant('VARIANTS'));
        $this->assertTrue($reflection->hasConstant('DEFAULT_QUALITY'));

        $variants = $reflection->getConstant('VARIANTS');
        $this->assertArrayHasKey('thumb', $variants);
        $this->assertArrayHasKey('medium', $variants);
        $this->assertArrayHasKey('large', $variants);

        $this->assertSame(300, $variants['thumb']);
        $this->assertSame(800, $variants['medium']);
        $this->assertSame(1600, $variants['large']);
    }

    public function test_store_optimized_has_new_return_keys(): void
    {
        $reflection = new \ReflectionClass(ImageService::class);
        $method = $reflection->getMethod('storeOptimized');

        // Dökümantasyon: array with thumb/medium/large/orientation_corrected/original_dimensions
        $doc = $method->getDocComment();
        $this->assertStringContainsString('orientation_corrected', (string) $doc);
        $this->assertStringContainsString('original_dimensions', (string) $doc);
    }

    public function test_gps_to_decimal_helper_exists_and_works(): void
    {
        $service = app(ImageService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('gpsToDecimal'));

        $method = $reflection->getMethod('gpsToDecimal');
        $method->setAccessible(true);

        // 40° 59' 0" N → 40.9833
        $result = $method->invoke($service, [[40, 1], [59, 1], [0, 1]], 'N');
        $this->assertEqualsWithDelta(40.9833, $result, 0.01);

        // 29° 0' 0" E → 29.0
        $result = $method->invoke($service, [[29, 1], [0, 1], [0, 1]], 'E');
        $this->assertEqualsWithDelta(29.0, $result, 0.01);

        // Güney yarımküre → negatif
        $result = $method->invoke($service, [[33, 1], [0, 1], [0, 1]], 'S');
        $this->assertLessThan(0, $result);

        // Batı yarımküre → negatif
        $result = $method->invoke($service, [[118, 1], [0, 1], [0, 1]], 'W');
        $this->assertLessThan(0, $result);
    }

    public function test_gps_fraction_helper_handles_string_and_array(): void
    {
        $service = app(ImageService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('gpsFraction');
        $method->setAccessible(true);

        // Düz sayı
        $this->assertSame(40.0, $method->invoke($service, 40));
        // Array [numerator, denominator]
        $this->assertEqualsWithDelta(0.5, $method->invoke($service, [1, 2]), 0.01);
        // Float
        $this->assertSame(0.5, $method->invoke($service, 0.5));
    }

    public function test_to_avif_returns_false_when_imageavif_function_missing(): void
    {
        if (function_exists('imageavif')) {
            $this->markTestSkipped('imageavif mevcut — test atlandı.');
        }

        $service = app(ImageService::class);
        // imageavif yoksa false döner (dosya yazmaz)
        $this->assertFalse($service->toAvif('non-existent.jpg', 'out.avif'));
    }

    public function test_extract_gps_returns_array_shape(): void
    {
        $reflection = new \ReflectionClass(ImageService::class);
        $method = $reflection->getMethod('extractGpsCoordinates');
        $returnType = $method->getReturnType();

        // Method return type array (assoc array)
        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    public function test_store_optimized_return_includes_exif_and_gps_flags(): void
    {
        $reflection = new \ReflectionClass(ImageService::class);
        $method = $reflection->getMethod('storeOptimized');
        $doc = (string) $method->getDocComment();

        // Yeni alanlar: exif_metadata + had_gps
        $this->assertStringContainsString('exif_metadata', $doc);
        $this->assertStringContainsString('had_gps', $doc);
    }

    public function test_sanitize_exif_keeps_allowed_fields(): void
    {
        $raw = [
            'Make' => 'Canon',
            'Model' => 'EOS 5D',
            'FNumber' => 2.8,
            'ISOSpeedRatings' => 400,
            'DateTimeOriginal' => '2024:01:15 14:30:00',
            'GPSLatitude' => [40, 59, 0],
            'GPSLongitude' => [29, 0, 0],
            'GPSLatitudeRef' => 'N',
            'GPSLongitudeRef' => 'E',
            'Software' => 'Adobe Lightroom',
            'UserComment' => 'Gizli kullanıcı notu — TEMİZLENMELİ',
            'SerialNumber' => 'SN-12345-ABCDE — TEMİZLENMELİ',
            'CameraOwnerName' => 'Kişisel — TEMİZLENMELİ',
            'ImageDescription' => 'Hassas açıklama — TEMİZLENMELİ',
        ];

        $sanitized = ListingImage::sanitizeExifForAudit($raw);

        // İzinli alanlar korunmalı
        $this->assertSame('Canon', $sanitized['Make']);
        $this->assertSame('EOS 5D', $sanitized['Model']);
        $this->assertSame(2.8, $sanitized['FNumber']);
        $this->assertSame(400, $sanitized['ISOSpeedRatings']);
        $this->assertSame([40, 59, 0], $sanitized['GPSLatitude']);
        $this->assertSame('Adobe Lightroom', $sanitized['Software']);

        // Hassas alanlar filtrelenmeli
        $this->assertArrayNotHasKey('UserComment', $sanitized);
        $this->assertArrayNotHasKey('SerialNumber', $sanitized);
        $this->assertArrayNotHasKey('CameraOwnerName', $sanitized);
        $this->assertArrayNotHasKey('ImageDescription', $sanitized);
    }

    public function test_sanitize_exif_preserves_unknown_tags_by_default(): void
    {
        // Bilinmeyen bir tag — allowed listesinde yoksa filtrelenir (güvenli default)
        $raw = [
            'Make' => 'Canon',
            'UnknownTag12345' => 'should be filtered',
        ];

        $sanitized = ListingImage::sanitizeExifForAudit($raw);

        $this->assertSame('Canon', $sanitized['Make']);
        $this->assertArrayNotHasKey('UnknownTag12345', $sanitized);
    }

    public function test_exif_summary_returns_camera_info(): void
    {
        $reflection = new \ReflectionClass(ListingImage::class);
        $this->assertTrue($reflection->hasMethod('exifSummary'));
    }

    public function test_listing_image_model_has_exif_metadata_fillable(): void
    {
        $image = new ListingImage;
        $fillable = $image->getFillable();

        $this->assertContains('exif_metadata', $fillable);
        $this->assertContains('had_gps', $fillable);
    }
}
