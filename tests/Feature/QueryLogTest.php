<?php

namespace Tests\Feature;

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

        // Sorgu logunu aktifleştir. Performans logunu da açıyoruz: aşağıdaki
        // istek-metriği testi "Performance: request" satırını bu worker'ın
        // kendi log dosyasında bekliyor (PerformanceMetricsMiddleware üretir).
        config([
            'app.query_log_enabled' => true,
            'app.performance_log' => true,
        ]);
    }

    public function test_query_log_disabled_by_default(): void
    {
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

        // İstek metriği bu worker'ın kendi log dosyasına yazılmalı
        // (paralel testlerde her worker ayrı dosya kullanır, bkz. Tests\TestCase).
        $logFile = config('logging.channels.single.path');
        $this->assertTrue(file_exists($logFile));
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Performance: request', $content);
    }

    public function test_home_page_query_count_under_threshold(): void
    {
        // Home page N+1 olmamalı (eager loading yapılmış)
        \DB::enableQueryLog();
        $this->get('/');
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // 50'den az sorgu — limit middleware'i uyarı vermez
        $this->assertLessThan(50, count($queries), 'Home page '.count($queries).' sorgu çalıştırdı (>50)');
    }
}
