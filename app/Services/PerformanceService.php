<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Performans ölçüm servisi.
 *
 * Routes/controllers/middleware için sorgu sayısı, bellek, süre gibi
 * metrikleri toplar. Üretim ortamında opsiyonel olarak etkinleştirilebilir
 * (PERFORMANCE_LOG env varsa).
 */
class PerformanceService
{
    private float $startTime;

    private int $startMemory;

    private int $queryCount = 0;

    private float $queryTime = 0.0;

    private bool $enabled;

    public function __construct()
    {
        $this->enabled = (bool) config('app.performance_log', false);
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    /**
     * Middleware/Service Provider'da çağrılır — sorgu sayacını sıfırlar.
     *
     * Sorgu sayısı DB::getQueryLog() ile DEĞİL, hafif bir DB::listen()
     * dinleyicisiyle tutulur — getQueryLog() yalnızca DB::enableQueryLog()
     * çağrılmışsa dolar (varsayılan kapalı) ve tüm sorgu/binding'leri
     * bellekte tutar; bu servis yalnızca sayı+süre istiyor, tam SQL logu
     * her istekte belleğe yük bindirmesin diye enableQueryLog() kullanılmaz.
     * Yalnızca özellik açıkken (PERFORMANCE_LOG=true) dinleyici eklenir.
     */
    public function start(): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->queryCount = 0;
        $this->queryTime = 0.0;

        if ($this->enabled) {
            DB::listen(function ($query) {
                $this->queryCount++;
                $this->queryTime += $query->time;
            });
        }
    }

    /**
     * İstek tamamlandığında log'a yaz.
     */
    public function record(Request $request, int $status): void
    {
        if (! $this->enabled) {
            return;
        }

        $duration = (microtime(true) - $this->startTime) * 1000; // ms
        $memoryUsed = (memory_get_usage(true) - $this->startMemory) / 1024 / 1024; // MB
        $queryCount = $this->queryCount;

        // Sorgu sayısı threshold (50'den fazla N+1 riski)
        $suspicious = $queryCount > 50 || $duration > 1000;

        $payload = [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $status,
            'duration_ms' => round($duration, 2),
            'memory_mb' => round($memoryUsed, 2),
            'queries' => $queryCount,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ];

        if ($suspicious) {
            Log::warning('Performance: suspicious request', $payload);
        } else {
            Log::info('Performance: request', $payload);
        }
    }

    /**
     * Manuel ölçüm için: bir kod bloğunun süresi ve sorgu sayısı.
     *
     * getQueryLog() burada bilinçli olarak kullanılır (measure() tek seferlik,
     * açık bir profiling çağrısıdır — start()'taki request-genelindeki hafif
     * sayaçtan farklı olarak burada tam sorgu logu tutmanın bellek maliyeti
     * kabul edilebilir). Çağrı öncesi/sonrası query log durumunu koruyup geri yükler.
     *
     * @return array{duration_ms: float, queries: int}
     */
    public function measure(callable $callback): array
    {
        $wasEnabled = DB::logging();
        if (! $wasEnabled) {
            DB::enableQueryLog();
        }
        $before = count(DB::getQueryLog());

        $start = microtime(true);
        $result = $callback();
        $duration = (microtime(true) - $start) * 1000;

        $after = count(DB::getQueryLog());
        $queries = $after - $before;

        if (! $wasEnabled) {
            DB::disableQueryLog();
        }

        return [
            'duration_ms' => round($duration, 2),
            'queries' => $queries,
            'result' => $result,
        ];
    }

    /**
     * En yavaş endpoint'leri raporla.
     *
     * @return array<int, array{path: string, method: string, avg_ms: float, calls: int, last_seen: string}>
     */
    public function slowEndpoints(int $limit = 20): array
    {
        if (! $this->enabled) {
            return [];
        }

        $logFile = storage_path('logs/laravel.log');
        if (! file_exists($logFile)) {
            return [];
        }

        // Log'dan performance entry'lerini parse et
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats = [];

        foreach ($lines as $line) {
            if (! str_contains($line, 'Performance: request')) {
                continue;
            }
            // Log satırı JSON içeriyor, parse et
            if (preg_match('/\{.*\}/', $line, $matches)) {
                $data = json_decode($matches[0], true);
                if (! is_array($data) || ! isset($data['path'])) {
                    continue;
                }
                $key = $data['method'].' '.$data['path'];
                if (! isset($stats[$key])) {
                    $stats[$key] = [
                        'path' => $data['path'],
                        'method' => $data['method'],
                        'count' => 0,
                        'total_ms' => 0,
                        'last_seen' => null,
                    ];
                }
                $stats[$key]['count']++;
                $stats[$key]['total_ms'] += (float) ($data['duration_ms'] ?? 0);
                $stats[$key]['last_seen'] = $line[0] ?? null;
            }
        }

        $result = [];
        foreach ($stats as $key => $stat) {
            $result[] = [
                'path' => $stat['path'],
                'method' => $stat['method'],
                'avg_ms' => $stat['count'] > 0 ? round($stat['total_ms'] / $stat['count'], 2) : 0,
                'calls' => $stat['count'],
                'last_seen' => $stat['last_seen'],
            ];
        }

        usort($result, fn ($a, $b) => $b['avg_ms'] <=> $a['avg_ms']);

        return array_slice($result, 0, $limit);
    }
}
