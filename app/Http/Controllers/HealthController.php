<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Uygulama sağlık kontrolü. /health/detailed sadece adminler tarafından
 * erişilebilir; uptime monitor'ler için /health önerilir.
 */
class HealthController extends Controller
{
    /**
     * Basit health check — monitoring servisleri (UptimeRobot, BetterUptime)
     * için. JSON döner; HTTP 200 ise sağlıklı.
     */
    public function basic(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'nisoya',
            'version' => config('app.version', '1.0.0'),
            'time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detaylı sağlık kontrolü — admin paneli ve operasyon ekibi için.
     * DB, cache, storage, queue, session kontrollerini içerir.
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            'session' => $this->checkSession(),
        ];

        $allOk = collect($checks)->every(fn ($c) => $c['status'] === 'ok');
        $status = $allOk ? 'ok' : 'degraded';

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'system' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'db_driver' => DB::connection()->getDriverName(),
                'cache_driver' => config('cache.default'),
                'queue_driver' => config('queue.default'),
            ],
        ], $allOk ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            $stats = DB::selectOne("
                SELECT
                    (SELECT COUNT(*) FROM users) as users,
                    (SELECT COUNT(*) FROM listings) as listings,
                    (SELECT COUNT(*) FROM messages) as messages
            ");

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
                'stats' => $stats,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'fail',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'health_check_'.uniqid();
            $start = microtime(true);
            Cache::put($key, '1', 5);
            $read = Cache::get($key);
            Cache::forget($key);
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => $read === '1' ? 'ok' : 'fail',
                'latency_ms' => $latency,
                'driver' => config('cache.default'),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'error' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        try {
            $disk = Storage::disk(config('filesystems.default'));

            $start = microtime(true);
            $disk->put('health_check.txt', '1');
            $exists = $disk->exists('health_check.txt');
            $disk->delete('health_check.txt');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => $exists ? 'ok' : 'fail',
                'latency_ms' => $latency,
                'disk' => config('filesystems.default'),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'error' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $size = 0;
            if (config('queue.default') === 'database') {
                $size = DB::table('jobs')->count();
            } elseif (config('queue.default') === 'redis') {
                $size = \Illuminate\Support\Facades\Redis::llen('queues:default');
            }

            return [
                'status' => 'ok',
                'driver' => config('queue.default'),
                'pending_jobs' => (int) $size,
                'warning' => $size > 1000 ? 'Queue backlog büyük — kontrol edin.' : null,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'error' => $e->getMessage()];
        }
    }

    private function checkSession(): array
    {
        try {
            return [
                'status' => 'ok',
                'driver' => config('session.driver'),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'error' => $e->getMessage()];
        }
    }
}