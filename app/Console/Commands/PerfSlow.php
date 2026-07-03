<?php

namespace App\Console\Commands;

use App\Services\PerformanceService;
use Illuminate\Console\Command;

/**
 * Log dosyasından en yavaş endpoint'leri raporlar.
 * PerformanceService::slowEndpoints() kullanır.
 *
 * Kullanım:
 *   php artisan perf:slow --limit=20
 *   php artisan perf:slow --json
 */
class PerfSlow extends Command
{
    protected $signature = 'perf:slow
                            {--limit=20 : Gösterilecek endpoint sayısı}
                            {--json : JSON çıktı}';

    protected $description = 'Laravel log dosyasından en yavaş endpoint\'leri listeler (PerformanceService tabanlı).';

    public function handle(PerformanceService $service): int
    {
        $endpoints = $service->slowEndpoints((int) $this->option('limit'));

        if (empty($endpoints)) {
            $this->info('Log dosyasında performans kaydı bulunamadı (PERFORMANCE_LOG=true olmalı).');
            return self::SUCCESS;
        }

        if ($this->option('json')) {
            $this->line(json_encode($endpoints, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $this->table(
            ['Method', 'Path', 'Avg (ms)', 'Calls', 'Last seen'],
            array_map(fn ($e) => [
                $e['method'],
                substr($e['path'], 0, 50),
                $e['avg_ms'],
                $e['calls'],
                substr($e['last_seen'] ?? '-', 0, 19),
            ], $endpoints)
        );

        return self::SUCCESS;
    }
}
