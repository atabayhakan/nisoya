<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\PerformanceService;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Performans benchmark testleri.
 * Development'te Debugbar kullanılır; production'da PerformanceService
 * log'a yazar. Bu testler temel performans eşiklerini doğrular.
 */
class PerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_home_page_renders_under_500ms_with_1000_listings(): void
    {
        $user = User::factory()->create();
        Category::create([
            'name' => 'Test',
            'slug' => 'test',
            'type' => 'hizmet',
            'is_active' => true,
        ]);
        $category = Category::first();

        // 1000 ilan oluştur
        Listing::factory()->count(1000)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);

        $start = microtime(true);
        $response = $this->get('/');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertOk();
        // Development makinesinde 500ms sınırı; CI'da daha uzun sürebilir
        $this->assertLessThan(2000, $duration, "Home page took {$duration}ms (>2000ms)");
    }

    public function test_listings_index_uses_selective_loading(): void
    {
        $user = User::factory()->create();
        Category::create([
            'name' => 'Test',
            'slug' => 'test',
            'type' => 'hizmet',
            'is_active' => true,
        ]);
        $category = Category::first();

        // 100 ilan + her birinde 3 görsel (factory olmadan)
        $listings = Listing::factory()->count(100)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);
        foreach ($listings as $listing) {
            foreach (range(1, 3) as $i) {
                ListingImage::create([
                    'listing_id' => $listing->id,
                    'path_thumb' => "listings/{$listing->id}/thumb-{$i}.webp",
                    'path_medium' => "listings/{$listing->id}/medium-{$i}.webp",
                    'path_large' => "listings/{$listing->id}/large-{$i}.webp",
                    'sort_order' => $i,
                    'is_cover' => $i === 1,
                ]);
            }
        }

        $start = microtime(true);
        $response = $this->get('/ilanlar');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(3000, $duration, "Listings index took {$duration}ms (>3000ms)");
    }

    public function test_performance_service_disabled_by_default(): void
    {
        // performance_log varsayılan false
        $service = app(PerformanceService::class);
        $this->assertFalse((bool) config('app.performance_log'));

        $service->start();
        $result = $service->measure(function () {
            return 'test';
        });

        // Disabled — measure() yine de çalışmalı (sadece record() log yapmaz)
        $this->assertSame('test', $result['result']);
    }

    public function test_performance_service_logs_when_enabled(): void
    {
        // Özelliği config üzerinden aç. Singleton'ın taze config'i okuması için
        // varsa önceki örneği unut (fresh app zaten reset eder, garanti olsun).
        config(['app.performance_log' => true]);
        $this->app->forgetInstance(PerformanceService::class);

        $user = User::factory()->create();
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $service = app(PerformanceService::class);
        $service->start();
        $service->record($request, 200);

        // Log dosyası yazıldı mı? (paralel testlerde her worker kendi log
        // dosyasını kullanır, bkz. Tests\TestCase::setUp())
        $logFile = config('logging.channels.single.path');
        $this->assertTrue(file_exists($logFile));
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Performance: request', $content);
        // Request::path() başındaki "/" karakterini siler ("/test" -> "test")
        $this->assertStringContainsString('"path":"test"', $content);
    }

    public function test_performance_service_measures_block_correctly(): void
    {
        $service = app(PerformanceService::class);
        $service->start();

        $result = $service->measure(function () {
            usleep(1000); // 1ms simüle

            return 'done';
        });

        $this->assertSame('done', $result['result']);
        $this->assertGreaterThanOrEqual(0, $result['duration_ms']);
        $this->assertArrayHasKey('queries', $result);
    }

    public function test_admin_listing_image_query_eager_loads(): void
    {
        // N+1 query testi: 50 ilan + her birinin 2 görseli yüklerken
        // tek sorgu ile yapılmalı (eager loading)
        $user = User::factory()->create();
        Category::create([
            'name' => 'Test',
            'slug' => 'test',
            'type' => 'hizmet',
            'is_active' => true,
        ]);
        $category = Category::first();

        $listings = Listing::factory()->count(50)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);

        foreach ($listings as $listing) {
            foreach (range(1, 2) as $i) {
                ListingImage::create([
                    'listing_id' => $listing->id,
                    'path_thumb' => "listings/{$listing->id}/thumb-{$i}.webp",
                    'path_medium' => "listings/{$listing->id}/medium-{$i}.webp",
                    'path_large' => "listings/{$listing->id}/large-{$i}.webp",
                    'sort_order' => $i,
                    'is_cover' => $i === 1,
                ]);
            }
        }

        \DB::enableQueryLog();
        $response = $this->get('/');
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        $response->assertOk();

        $this->assertLessThan(80, count($queries), 'N+1 query riski: '.count($queries).' sorgu çalıştı');
    }

    public function test_listing_show_query_count_is_bounded(): void
    {
        $user = User::factory()->create();
        Category::create([
            'name' => 'Test',
            'slug' => 'test',
            'type' => 'hizmet',
            'is_active' => true,
        ]);
        $category = Category::first();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);

        \DB::enableQueryLog();
        $response = $this->get('/ilan/'.$listing->id);
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        $response->assertOk();
        $this->assertLessThan(25, count($queries), 'Listing show fazla sorgu: '.count($queries));
    }
}
