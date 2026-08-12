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

    /** Kuyruğun azalıp azalmadığını izlemek için tutulan gözlem kaydı. */
    private const KUYRUK_GOZLEM_ANAHTARI = 'kuyruk_gozlem';

    protected function getStats(): array
    {
        // DB ping (milisaniye)
        $dbLatency = $this->measureDbLatency();
        $cacheStatus = $this->checkCache();
        $storageStatus = $this->checkStorage();
        $queueSize = $this->getQueueSize();
        $kuyruk = $this->kuyrukSagligi($queueSize);

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
                ->description($kuyruk['aciklama'])
                ->descriptionIcon($kuyruk['ikon'])
                ->color($kuyruk['renk']),

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

    /**
     * Kuyruk sağlığı — SAYIYA DEĞİL, EN ESKİ İŞİN YAŞINA bakar.
     *
     * ---------------------------------------------------------------------
     * NEDEN DEĞİŞTİ
     *
     * Eskiden kural `$queueSize > 1000` idi: bin işin altındaki her durum
     * yeşil "başarılı" görünüyordu. Ama worker ÖLDÜĞÜNDE kuyrukta binlerce iş
     * birikmez — üç beş iş takılır ve gösterge yeşil kalır. Yani göstergenin
     * en çok işe yarayacağı arıza, göstergenin göremediği tek arızaydı.
     *
     * Bu 2026-08-12'de canlıda bir ilan görselinin kaybolmasıyla ortaya çıktı:
     * `ListingImage` kaydı YALNIZCA `ProcessListingImage` kuyruk işinde
     * oluşuyor, iş koşmazsa görsel hiç doğmuyor ve kimse fark etmiyor.
     *
     * Doğru sinyal yaştır: sağlıklı bir kuyrukta iş saniyeler içinde tükenir.
     * Dakikalarca bekleyen tek bir iş bile "worker çalışmıyor" demektir.
     *
     * ---------------------------------------------------------------------
     * İKİ ÖLÇÜM, ÇÜNKÜ TEK ÖLÇÜM ÜRETİMİ KAÇIRDI
     *
     * Yaş ölçümü yalnız `database` sürücüsünde mümkün (jobs.available_at).
     * İlk yazışta yalnız o vardı ve ölçülemeyen sürücülerde "başarılı"
     * dönüyordu — CANLIDA SÜRÜCÜ REDIS, yani alarm hiç çalışamazdı. Üstelik
     * eski `>1000` uyarısı da kalktığı için gösterge bir yönden eskisinden
     * kötü olmuştu. (Testim de geçmişti: sürücüyü `database`e ayarlamıştım,
     * yani üretimin hiç girmediği dalı sınıyordu.)
     *
     * İkinci ölçüm sürücüden BAĞIMSIZ: kuyruk TÜKENİYOR MU? Gördüğümüz en
     * düşük boyut ve o anın zamanı saklanır; boyut düşmeden dakikalar geçerse
     * worker iş almıyor demektir. Redis'te işlerin üzerinde zaman damgası
     * yok, ama "azalmıyor" bilgisi aynı soruya cevap veriyor.
     *
     * @return array{aciklama: string, ikon: string, renk: string}
     */
    private function kuyrukSagligi(int $boyut): array
    {
        $surucu = 'Driver: '.config('queue.default');

        if ($boyut === 0) {
            Cache::forget(self::KUYRUK_GOZLEM_ANAHTARI);

            return ['aciklama' => $surucu.' · kuyruk boş', 'ikon' => 'heroicon-m-check-circle', 'renk' => 'success'];
        }

        // 5 dakika: normal işlemede asla ulaşılmayacak kadar uzun, geçici
        // yoğunlukta ise yanlış alarm vermeyecek kadar toleranslı.
        $alarm = fn (string $sebep) => [
            'aciklama' => $surucu.' · '.$sebep.' — worker çalışmıyor olabilir',
            'ikon' => 'heroicon-m-exclamation-triangle',
            'renk' => 'danger',
        ];

        // 1) DOĞRUDAN ÖLÇÜM — yalnız `database` sürücüsünde mümkün.
        $yasDakika = $this->enEskiIsinYasiDakika();

        if ($yasDakika !== null && $yasDakika >= 5) {
            return $alarm('EN ESKİ İŞ '.$yasDakika.' DK BEKLİYOR');
        }

        // 2) DOLAYLI ÖLÇÜM — her sürücüde çalışır: kuyruk azalıyor mu?
        $takiliDakika = $this->kuyrukKacDakikadirAzalmiyor($boyut);

        if ($takiliDakika !== null && $takiliDakika >= 5) {
            return $alarm('KUYRUK '.$takiliDakika.' DK\'DIR AZALMIYOR');
        }

        return [
            'aciklama' => $surucu.' · '.($yasDakika !== null
                ? 'en eski iş '.$yasDakika.' dk'
                : $boyut.' iş bekliyor'),
            'ikon' => 'heroicon-m-queue-list',
            'renk' => $boyut > 1000 ? 'warning' : 'success',
        ];
    }

    /** Bekleyen en eski işin yaşı (dakika); ölçülemiyorsa null. */
    private function enEskiIsinYasiDakika(): ?int
    {
        if (config('queue.default') !== 'database') {
            return null;
        }

        try {
            $enEski = DB::table('jobs')->min('available_at');
        } catch (\Throwable) {
            return null;
        }

        if (! $enEski) {
            return null;
        }

        return max(0, (int) floor((time() - (int) $enEski) / 60));
    }

    /**
     * Kuyruk kaç dakikadır AZALMIYOR? Ölçülemiyorsa null.
     *
     * Gördüğümüz en düşük boyutu ve o anı saklar. Boyut daha da düşerse
     * kuyruk tükeniyor demektir ve sayaç sıfırlanır; düşmeden zaman geçerse
     * worker iş almıyor demektir.
     *
     * Nesne DEĞİL düz dizi saklanıyor — bu depoda cache sürücüsü nesne
     * unserialize'ını bilerek engelliyor (gadget-chain koruması).
     */
    private function kuyrukKacDakikadirAzalmiyor(int $boyut): ?int
    {
        try {
            $kayit = Cache::get(self::KUYRUK_GOZLEM_ANAHTARI);

            if (! is_array($kayit) || ! isset($kayit['en_dusuk'], $kayit['zaman']) || $boyut < (int) $kayit['en_dusuk']) {
                Cache::put(self::KUYRUK_GOZLEM_ANAHTARI, ['en_dusuk' => $boyut, 'zaman' => time()], now()->addHours(6));

                return 0;
            }

            return max(0, (int) floor((time() - (int) $kayit['zaman']) / 60));
        } catch (\Throwable) {
            return null;
        }
    }
}
