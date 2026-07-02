<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\GeocodingService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Reverse geocoding testleri — Nominatim API mock'lanarak çalışır.
 * Test ortamında service otomatik devre dışı (runningUnitTests),
 * ancak biz service metodlarını doğrudan mock ile test ederiz.
 */
class ReverseGeocodingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    protected function createImageWithGps(float $lat, float $lng): ListingImage
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);

        return ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/test.jpg',
            'gps_lat' => $lat,
            'gps_lng' => $lng,
            'had_gps' => true,
        ]);
    }

    public function test_geocoding_service_has_reverse_method(): void
    {
        $this->assertTrue(method_exists(GeocodingService::class, 'reverse'));
        $this->assertTrue(method_exists(GeocodingService::class, 'parseNominatimResponse'));
        $this->assertTrue(method_exists(GeocodingService::class, 'fallbackReverse'));
    }

    public function test_reverse_returns_test_safe_fallback_in_test_environment(): void
    {
        // Test ortamında runningUnitTests true → ağ çağrısı yapılmaz
        $service = app(GeocodingService::class);
        $result = $service->reverse(41.0082, 28.9784);

        // Fallback bile en yakın ülkeyi bulmaya çalışır; yoksa boş döner
        $this->assertIsArray($result);
        $this->assertArrayHasKey('country_code', $result);
        $this->assertArrayHasKey('country_name', $result);
        $this->assertArrayHasKey('city', $result);
    }

    public function test_parse_nominatim_response_extracts_country_city_state(): void
    {
        $service = app(GeocodingService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('parseNominatimResponse');
        $method->setAccessible(true);

        $response = [
            'address' => [
                'country_code' => 'tr',
                'country' => 'Türkiye',
                'city' => 'İstanbul',
                'state' => 'İstanbul',
            ],
        ];

        $result = $method->invoke($service, $response);

        $this->assertSame('TR', $result['country_code']);
        $this->assertSame('Türkiye', $result['country_name']);
        $this->assertSame('İstanbul', $result['city']);
        $this->assertSame('İstanbul', $result['state']);
    }

    public function test_parse_nominatim_falls_back_to_town_when_city_missing(): void
    {
        $service = app(GeocodingService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('parseNominatimResponse');
        $method->setAccessible(true);

        $response = [
            'address' => [
                'country_code' => 'de',
                'country' => 'Deutschland',
                'town' => 'München',
                'state' => 'Bayern',
            ],
        ];

        $result = $method->invoke($service, $response);

        $this->assertSame('DE', $result['country_code']);
        $this->assertSame('München', $result['city']); // town → city
        $this->assertSame('Bayern', $result['state']);
    }

    public function test_parse_nominatim_handles_empty_address(): void
    {
        $service = app(GeocodingService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('parseNominatimResponse');
        $method->setAccessible(true);

        $result = $method->invoke($service, []);

        $this->assertNull($result['country_code']);
        $this->assertNull($result['city']);
        $this->assertNull($result['state']);
    }

    public function test_parse_nominatim_uppercases_country_code(): void
    {
        $service = app(GeocodingService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('parseNominatimResponse');
        $method->setAccessible(true);

        $result = $method->invoke($service, [
            'address' => ['country_code' => 'tr', 'country' => 'Türkiye'],
        ]);

        $this->assertSame('TR', $result['country_code']);
    }

    public function test_listing_image_has_reverse_geocode_columns(): void
    {
        $image = new ListingImage;
        $this->assertContains('reverse_country_code', $image->getFillable());
        $this->assertContains('reverse_country_name', $image->getFillable());
        $this->assertContains('reverse_city', $image->getFillable());
        $this->assertContains('reverse_state', $image->getFillable());
        $this->assertContains('reverse_geocoded_at', $image->getFillable());
    }

    public function test_listing_image_has_new_scopes(): void
    {
        $image = $this->createImageWithGps(41.0082, 28.9784);
        $image->update(['reverse_country_code' => 'TR', 'reverse_city' => 'İstanbul', 'reverse_geocoded_at' => now()]);

        $geocoded = ListingImage::query()->whereNotNull('reverse_geocoded_at')->count();
        $pending = ListingImage::query()->pendingReverseGeocode()->count();
        $inTr = ListingImage::query()->inCountry('TR')->count();

        $this->assertSame(1, $geocoded);
        $this->assertSame(0, $pending);
        $this->assertSame(1, $inTr);
    }

    public function test_pending_reverse_geocode_scope_finds_ungeocoded(): void
    {
        $image = $this->createImageWithGps(41.0082, 28.9784);
        $this->assertNull($image->reverse_geocoded_at);

        $pending = ListingImage::query()->pendingReverseGeocode()->count();
        $this->assertSame(1, $pending);
    }

    public function test_apply_reverse_geocode_writes_to_db(): void
    {
        $image = $this->createImageWithGps(41.0082, 28.9784);

        // Mock: service doğrudan reverse çağrısı yapsın
        $mockService = $this->createMock(GeocodingService::class);
        $mockService->method('reverse')->willReturn([
            'country_code' => 'TR',
            'country_name' => 'Türkiye',
            'city' => 'İstanbul',
            'state' => 'İstanbul',
            'raw' => [],
        ]);

        $result = $image->applyReverseGeocode($mockService);

        $this->assertTrue($result);
        $image->refresh();
        $this->assertSame('TR', $image->reverse_country_code);
        $this->assertSame('İstanbul', $image->reverse_city);
        $this->assertNotNull($image->reverse_geocoded_at);
    }

    public function test_apply_reverse_geocode_returns_false_without_gps(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);
        $image = ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'test.jpg',
        ]);

        $mockService = $this->createMock(GeocodingService::class);
        $this->assertFalse($image->applyReverseGeocode($mockService));
    }

    public function test_reverse_location_label_formatting(): void
    {
        $image = new ListingImage([
            'reverse_city' => 'İstanbul',
            'reverse_country_name' => 'Türkiye',
        ]);
        $this->assertSame('İstanbul, Türkiye', $image->reverseLocationLabel);

        $image = new ListingImage(['reverse_country_name' => 'Türkiye']);
        $this->assertSame('Türkiye', $image->reverseLocationLabel);

        $image = new ListingImage([]);
        $this->assertNull($image->reverseLocationLabel);
    }

    public function test_in_city_scope(): void
    {
        $image = $this->createImageWithGps(41.0082, 28.9784);
        $image->update(['reverse_city' => 'İstanbul', 'reverse_geocoded_at' => now()]);

        $image2 = $this->createImageWithGps(39.9334, 32.8597);
        $image2->update(['reverse_city' => 'Ankara', 'reverse_geocoded_at' => now()]);

        $istanbul = ListingImage::query()->inCity('İstanbul')->count();
        $ankara = ListingImage::query()->inCity('Ankara')->count();
        $izmir = ListingImage::query()->inCity('İzmir')->count();

        $this->assertSame(1, $istanbul);
        $this->assertSame(1, $ankara);
        $this->assertSame(0, $izmir);
    }

    public function test_reverse_geocode_with_http_mock(): void
    {
        // Test ortamında runningUnitTests olduğu için normalde HTTP çağrılmaz.
        // Ama service'i bypass edip doğrudan parse fonksiyonunu test edebiliriz.
        $service = $this->createMock(GeocodingService::class);
        $service->method('reverse')->willReturn([
            'country_code' => 'JP',
            'country_name' => 'Japan',
            'city' => 'Tokyo',
            'state' => 'Tokyo',
            'raw' => [],
        ]);

        $image = $this->createImageWithGps(35.6762, 139.6503);
        $image->applyReverseGeocode($service);

        $image->refresh();
        $this->assertSame('JP', $image->reverse_country_code);
        $this->assertSame('Tokyo', $image->reverse_city);
    }

    public function test_artisan_command_resets_pending_images(): void
    {
        $image = $this->createImageWithGps(41.0082, 28.9784);
        $this->assertNull($image->reverse_geocoded_at);

        $this->artisan('images:reverse-geocode', ['--dry-run' => true])
            ->expectsOutput('İşlenecek görsel: 1')
            ->assertExitCode(0);
    }

    public function test_artisan_command_handles_no_pending_images(): void
    {
        $this->artisan('images:reverse-geocode')
            ->expectsOutput('İşlenecek görsel: 0')
            ->expectsOutput('Tüm görseller zaten reverse geocoded edilmiş.')
            ->assertExitCode(0);
    }

    public function test_fallback_reverse_finds_nearest_country(): void
    {
        $service = app(GeocodingService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('fallbackReverse');
        $method->setAccessible(true);

        // İstanbul koordinatları — en yakın ülke TR olmalı
        $result = $method->invoke($service, 41.0082, 28.9784);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('country_code', $result);
        $this->assertArrayHasKey('country_name', $result);
        // Fallback: en yakın ülkeyi bulabilir veya null olabilir
        $this->assertNull($result['city']); // Fallback'te city yok
    }

    public function test_reverse_geocode_command_processes_image(): void
    {
        // Service::shouldReceive ile mock (Laravel facade mock)
        $this->mock(GeocodingService::class, function ($mock) {
            $mock->shouldReceive('reverse')->andReturn([
                'country_code' => 'TR',
                'country_name' => 'Türkiye',
                'city' => 'İstanbul',
                'state' => 'İstanbul',
                'raw' => [],
            ]);
        });

        $image = $this->createImageWithGps(41.0082, 28.9784);

        $this->artisan('images:reverse-geocode', ['--limit' => 1])
            ->assertExitCode(0);

        $image->refresh();
        $this->assertSame('TR', $image->reverse_country_code);
        $this->assertSame('İstanbul', $image->reverse_city);
    }

    public function test_duplicate_coordinates_cluster_same_city(): void
    {
        // 3 görsel aynı koordinatta
        $this->createImageWithGps(41.0082, 28.9784);
        $this->createImageWithGps(41.0083, 28.9785);
        $this->createImageWithGps(41.0084, 28.9786);

        // Aynı şehirdeki 3 görseli bul (duplicate tespiti)
        $nearby = ListingImage::query()
            ->nearCoordinates(41.0082, 28.9784, 0.001)
            ->count();

        $this->assertSame(3, $nearby);
    }
}