<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Admin dashboard sağlık özeti.
 * Uptime monitoring, queue backlog, hata göstergeleri.
 */
class SystemHealthWidget extends BaseWidget
{
    protected static ?int $sort = -2; // StatsOverview'un üstünde

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        // DB ping (milisaniye)
        $dbLatency = $this->measureDbLatency();
        $cacheStatus = $this->checkCache();
        $storageStatus = $this->checkStorage();
        $queueSize = $this->getQueueSize();

        return [
            Stat::make('Veritabanı', $dbLatency.'ms')
                ->description('Son DB sorgusu gecikmesi')
                ->descriptionIcon($dbLatency < 50 ? 'heroicon-m-bolt' : 'heroicon-m-clock')
                ->color($dbLatency < 50 ? 'success' : ($dbLatency < 200 ? 'warning' : 'danger')),

            Stat::make('Cache', $cacheStatus['ok'] ? 'Çalışıyor' : 'HATA')
                ->description('Driver: '.config('cache.default').' · '.$cacheStatus['latency'].'ms')
                ->descriptionIcon($cacheStatus['ok'] ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($cacheStatus['ok'] ? 'success' : 'danger'),

            Stat::make('Storage', $storageStatus['ok'] ? 'Çalışıyor' : 'HATA')
                ->description('Disk: '.config('filesystems.default').' · '.$storageStatus['latency'].'ms')
                ->descriptionIcon($storageStatus['ok'] ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($storageStatus['ok'] ? 'success' : 'danger'),

            Stat::make('Queue Bekleyen', number_format($queueSize))
                ->description('Driver: '.config('queue.default'))
                ->descriptionIcon($queueSize > 1000 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-queue-list')
                ->color($queueSize > 1000 ? 'warning' : 'success'),
        ];
    }

    private function measureDbLatency(): float
    {
        $start = microtime(true);
        try {
            DB::select('SELECT 1');
        } catch (\Throwable) {
            return 9999.99;
        }

        return round((microtime(true) - $start) * 1000, 2);
    }

    private function checkCache(): array
    {
        $start = microtime(true);
        try {
            $key = 'sys_health_'.uniqid();
            Cache::put($key, '1', 5);
            $ok = Cache::get($key) === '1';
            Cache::forget($key);
        } catch (\Throwable) {
            return ['ok' => false, 'latency' => 0];
        }

        return [
            'ok' => $ok,
            'latency' => round((microtime(true) - $start) * 1000, 2),
        ];
    }

    private function checkStorage(): array
    {
        $start = microtime(true);
        try {
            $disk = Storage::disk(config('filesystems.default'));
            $disk->put('sys_health.txt', '1');
            $ok = $disk->exists('sys_health.txt');
            $disk->delete('sys_health.txt');
        } catch (\Throwable) {
            return ['ok' => false, 'latency' => 0];
        }

        return [
            'ok' => $ok,
            'latency' => round((microtime(true) - $start) * 1000, 2),
        ];
    }

    private function getQueueSize(): int
    {
        try {
            if (config('queue.default') === 'database') {
                return (int) DB::table('jobs')->count();
            }
            if (config('queue.default') === 'redis') {
                return (int) \Illuminate\Support\Facades\Redis::llen('queues:default');
            }
        } catch (\Throwable) {
            return 0;
        }

        return 0;
    }
}