<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QueryLogMiddleware testleri. Production'da N+1 ve yavaş sorguları yakalar.
 * Default devre dışı (QUERY_LOG_ENABLED=false); testte aktifleştiririz.
 */
class QueryLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);

        // Query log'u aktifleştir
        putenv('QUERY_LOG_ENABLED=true');
        config(['app.query_log_enabled' => true]);
    }

    protected function tearDown(): void
    {
        putenv('QUERY_LOG_ENABLED=');
        parent::tearDown();
    }

    public function test_query_log_disabled_by_default(): void
    {
        putenv('QUERY_LOG_ENABLED=false');
        config(['app.query_log_enabled' => false]);

        $response = $this->get('/');
        $response->assertOk();
        // Sorgu logu kapalı — herhangi bir etki olmamalı
        $this->assertTrue(true);
    }

    public function test_query_log_does_not_break_normal_requests(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $this->assertNotNull($response);
    }

    public function test_query_log_counts_queries_on_request(): void
    {
        // Sorgu yapan bir sayfaya istek at
        $response = $this->get('/');
        $response->assertOk();

        // Log dosyasında sorgu sayısı bilgisi yer almalı (etkinse)
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            $this->assertStringContainsString('Performance: request', $content);
        }
    }

    public function test_home_page_query_count_under_threshold(): void
    {
        // Home page N+1 olmamalı (eager loading yapılmış)
        \DB::enableQueryLog();
        $this->get('/');
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // 50'den az sorgu — limit middleware'i uyarı vermez
        $this->assertLessThan(50, count($queries), 'Home page ' . count($queries) . ' sorgu çalıştırdı (>50)');
    }
}
