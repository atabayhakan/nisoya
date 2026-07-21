<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

/**
 * Tam yedek (veritabanı + medya) alır ve eski yedekleri temizler.
 *
 * Hem günlük schedule (routes/console.php) hem de sahibin elle çalıştırabilmesi
 * için: `php artisan backup:run`. Yönetim panelindeki "Şimdi Yedek Al" butonuyla
 * aynı BackupService'i kullanır (bkz. Admin → Sistem → Yedekleme).
 */
class RunBackup extends Command
{
    protected $signature = 'backup:run {--keep= : Kaç günlük yedek saklansın (varsayılan: config/backup.php)}';

    protected $description = 'Tam yedek (veritabanı + medya) alır ve eski yedekleri temizler';

    public function handle(BackupService $backup): int
    {
        $this->info('Yedek alınıyor (veritabanı + medya)...');

        try {
            $name = $backup->create();
        } catch (\Throwable $e) {
            $this->error('Yedekleme başarısız: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('✓ Yedek oluşturuldu: '.$name);

        $keepOption = $this->option('keep');
        $removed = $backup->prune($keepOption !== null ? (int) $keepOption : null);

        if ($removed > 0) {
            $this->info("✓ {$removed} eski yedek temizlendi.");
        }

        return self::SUCCESS;
    }
}
