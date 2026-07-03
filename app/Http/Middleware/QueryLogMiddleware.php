<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Production ortamı için hafif query logger. Sadece yavaş sorguları (>200ms)
 * ve N+1 riski taşıyan sorguları (>10 tekrar eden SQL pattern) log'a yazar.
 * Debugbar'dan çok daha hafif — sadece performans exception'larını yakalar.
 *
 * Aktifleştirme: QUERY_LOG_SLOW_MS env değişkeni (varsayılan 200ms).
 */
class QueryLogMiddleware
{
    private float $requestStart;

    private int $queryCount = 0;

    private float $totalQueryTime = 0.0;

    private array $queries = [];

    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) env('QUERY_LOG_ENABLED', false)) {
            return $next($request);
        }

        $slowThreshold = (float) env('QUERY_LOG_SLOW_MS', 200);
        $this->requestStart = microtime(true);
        $this->queryCount = 0;
        $this->totalQueryTime = 0;
        $this->queries = [];

        DB::listen(function ($query) use ($slowThreshold) {
            $timeMs = $query->time;
            $this->queryCount++;
            $this->totalQueryTime += $timeMs;
            $this->queries[] = [
                'sql' => $query->sql,
                'time_ms' => $timeMs,
            ];

            // Yavaş sorguları logla
            if ($timeMs > $slowThreshold) {
                Log::warning('QueryLog: yavaş sorgu', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => round($timeMs, 2),
                    'path' => $request->path(),
                ]);
            }
        });

        $response = $next($request);

        // N+1 pattern tespiti: aynı SQL pattern >10 kez. Parametreli sorgular
        // (bindings ile) zaten aynı ham SQL metnine sahip olur, ama satır içi
        // sayısal literal içeren sorguları (ör. "id = 5" vs "id = 6") da aynı
        // pattern olarak gruplamak için sayıları normalize ediyoruz — önceki
        // hâli ('/\?/' → '?') hiçbir şey değiştirmeyen bir no-op'tu.
        $patterns = array_count_values(array_map(
            fn ($q) => preg_replace('/\d+/', '?', $q['sql']),
            $this->queries
        ));
        $suspicious = array_filter($patterns, fn ($count) => $count > 10);

        if (! empty($suspicious)) {
            Log::warning('QueryLog: olası N+1 sorgu pattern', [
                'path' => $request->path(),
                'patterns' => $suspicious,
            ]);
        }

        // Query count > 50 ise uyar
        if ($this->queryCount > 50) {
            Log::warning('QueryLog: yüksek sorgu sayısı', [
                'path' => $request->path(),
                'query_count' => $this->queryCount,
                'total_query_time_ms' => round($this->totalQueryTime, 2),
            ]);
        }

        // Genel istek metriği PerformanceMetricsMiddleware tarafından zaten
        // kaydediliyor (aynı PerformanceService singleton'ı) — burada tekrar
        // record() çağırmak "Performance: request" log satırını iki kez yazar.

        return $response;
    }
}
