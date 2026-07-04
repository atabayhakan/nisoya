<?php

namespace App\Console\Commands;

use App\Models\ListingImage;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

/**
 * GPS koordinatı bilinen ama henüz reverse geocoded edilmemiş görselleri
 * toplu olarak işler. Nominatim rate limit'e takılmamak için 1.1 sn aralık
 * kullanır.
 *
 * Kullanım:
 *   php artisan images:reverse-geocode
 *   php artisan images:reverse-geocode --limit=100
 *   php artisan images:reverse-geocode --force  (mevcut cache'leri de yeniler)
 */
class ReverseGeocodeImages extends Command
{
    protected $signature = 'images:reverse-geocode
                            {--limit=0 : İşlenecek maksimum görsel sayısı (0 = sınırsız)}
                            {--force : Zaten geocoded edilmiş görselleri de yeniden kodla}
                            {--dry-run : Gerçek API çağrısı yapmadan sadece rapor}';

    protected $description = 'GPS koordinatı bilinen görselleri reverse geocoding ile şehir/ülke bilgisine çevirir.';

    public function handle(GeocodingService $geocoding): int
    {
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        $query = $force
            ? ListingImage::query()->withGps()
            : ListingImage::query()->pendingReverseGeocode();

        if ($limit > 0) {
            $query->limit($limit);
        }

        $count = $query->count();
        $this->info("İşlenecek görsel: {$count}");

        if ($count === 0) {
            $this->info('Tüm görseller zaten reverse geocoded edilmiş.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: Gerçek API çağrısı yapılmadı.');

            return self::SUCCESS;
        }

        $successCount = 0;
        $errorCount = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($query->cursor() as $image) {
            try {
                if ($image->applyReverseGeocode($geocoding)) {
                    $successCount++;
                }

                // Nominatim rate limit: max 1 req/s
                usleep(1_100_000);
            } catch (\Throwable $e) {
                $errorCount++;
                $this->error("ID {$image->id}: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Başarılı: {$successCount}");
        if ($errorCount > 0) {
            $this->warn("Hata: {$errorCount}");
        }

        return self::SUCCESS;
    }
}
