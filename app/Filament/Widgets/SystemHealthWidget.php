<?php

namespace App\Filament\Widgets;

use App\Providers\Filament\AdminPanelProvider;
use App\Services\BackupService;
use App\Support\Settings;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

/**
 * Admin dashboard sağlık özeti.
 * Uptime monitoring, queue backlog, hata göstergeleri.
 */
class SystemHealthWidget extends BaseWidget
{
    /**
     * Sıra merdiveni {@see AdminPanelProvider} içinde.
     *
     * Eski yorum "StatsOverview'un üstünde" diyordu ama değeri (-2) onu
     * StatsOverview'un (-3) ALTINA koyuyordu — yorum niyeti anlatıyor, kod
     * başka şey yapıyordu. Sıra artık testle mühürlü.
     */
    protected static ?int $sort = 70;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        // DB ping (milisaniye)
        $dbLatency = $this->measureDbLatency();
        $cacheStatus = $this->checkCache();
        $storageStatus = $this->checkStorage();
        $queueSize = $this->getQueueSize();

        // Sahibin "kendi kendine yeten site" sağlığı (Faz 1 · G10)
        $backup = $this->backupStat();
        $disk = $this->diskStat();
        $mail = $this->mailStat();

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

            Stat::make('Son Yedek', $backup['label'])
                ->description($backup['desc'])
                ->descriptionIcon($backup['icon'])
                ->color($backup['color']),

            Stat::make('Boş Disk', $disk['label'])
                ->description('Sunucu disk alanı')
                ->descriptionIcon($disk['icon'])
                ->color($disk['color']),

            Stat::make('E-posta', $mail['label'])
                ->description($mail['desc'])
                ->descriptionIcon($mail['icon'])
                ->color($mail['color']),
        ];
    }

    /** Son yedek yaşı/sağlığı (bkz. Yedekleme sayfası). */
    private function backupStat(): array
    {
        $stats = app(BackupService::class)->stats();
        $latest = $stats['latest'];

        if ($latest === null) {
            return [
                'label' => 'Yok',
                'desc' => 'Henüz hiç yedek alınmadı',
                'icon' => 'heroicon-m-exclamation-triangle',
                'color' => 'danger',
            ];
        }

        $fresh = $latest->greaterThan(now()->subDays(7));

        return [
            'label' => $latest->diffForHumans(),
            'desc' => $stats['count'].' yedek · '.BackupService::humanSize($stats['total_size']),
            'icon' => $fresh ? 'heroicon-m-check-circle' : 'heroicon-m-clock',
            'color' => $fresh ? 'success' : 'warning',
        ];
    }

    /** Sunucudaki boş disk alanı. */
    private function diskStat(): array
    {
        $free = (float) (@disk_free_space(base_path()) ?: 0);
        $gb = $free / (1024 ** 3);

        return [
            'label' => BackupService::humanSize($free),
            'icon' => $gb < 2 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-circle-stack',
            'color' => $gb < 0.5 ? 'danger' : ($gb < 2 ? 'warning' : 'success'),
        ];
    }

    /** Gerçek e-posta gönderimi yapılandırılmış mı? (bkz. Mail Ayarları) */
    private function mailStat(): array
    {
        $default = config('mail.default');
        $host = Settings::get('mail.host') ?: config('mail.mailers.smtp.host');

        $configured = ! in_array($default, ['log', 'array'], true)
            && ! empty($host)
            && $host !== '127.0.0.1';

        return [
            'label' => $configured ? 'Yapılandırıldı' : 'Varsayılan',
            'desc' => $configured ? ('Sürücü: '.$default) : 'Gerçek gönderim kapalı olabilir',
            'icon' => $configured ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle',
            'color' => $configured ? 'success' : 'warning',
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
                return (int) Redis::llen('queues:default');
            }
        } catch (\Throwable) {
            return 0;
        }

        return 0;
    }
}
