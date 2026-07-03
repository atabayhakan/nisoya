<?php

namespace App\Console\Commands;

use App\Models\ListingImage;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Mevcut tüm ilan görsellerini EXIF orientation düzeltmesi ve metadata
 * temizliği ile yeniden işle. İlk deploy sonrası bir kez çalıştırılmalı.
 *
 * Kullanım:
 *   php artisan images:reprocess
 *   php artisan images:reprocess --dry-run
 *   php artisan images:reprocess --limit=100
 */
class ReprocessImages extends Command
{
    protected $signature = 'images:reprocess
                            {--limit=0 : Maksimum işlenecek görsel sayısı (0 = sınırsız)}
                            {--dry-run : Gerçek dosyayı değiştirmeden rapor}
                            {--delete-original : Eski tek-path görselleri sil}';

    protected $description = 'Mevcut görselleri EXIF orientation düzeltmesi ve metadata temizliği ile yeniden işler.';

    public function handle(): int
    {
        $imageService = app(ImageService::class);
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');
        $deleteOriginal = (bool) $this->option('delete-original');

        $query = ListingImage::query()->orderBy('id');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $count = 0;
        $orientationFixed = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        foreach ($query->cursor() as $image) {
            $count++;
            $bar->advance();

            // Zaten WebP varyantları varsa atla
            if ($image->path_thumb && $image->path_medium && $image->path_large) {
                continue;
            }

            if ($dryRun) {
                continue;
            }

            try {
                $sourcePath = Storage::disk('public')->path($image->path_large);
                if (! file_exists($sourcePath)) {
                    $errors++;
                    continue;
                }

                // Eski tek-path'i yedekle, yeni varyantlar üret
                $oldPath = $image->path_large;
                $variants = $imageService->generateVariants($image->path_large, 'listings');

                if ($variants) {
                    $image->update([
                        'path_thumb' => $variants['thumb'],
                        'path_medium' => $variants['medium'],
                        'path_large' => $variants['large'],
                    ]);
                    $orientationFixed++;

                    if ($deleteOriginal && $oldPath !== $variants['large']) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->error("ID {$image->id}: ".$e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine();

        $this->info("Toplam: {$count}");
        $this->info("Orientation düzeltildi: {$orientationFixed}");
        if ($errors > 0) {
            $this->warn("Hata: {$errors}");
        }

        return self::SUCCESS;
    }
}