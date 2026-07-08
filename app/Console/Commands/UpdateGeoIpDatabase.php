<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class UpdateGeoIpDatabase extends Command
{
    protected $signature = 'geoip:update';

    protected $description = 'MaxMind GeoLite2-Country veritabanını indirir/günceller (header ülke bayrağı için, ücretsiz lisans anahtarı gerekir).';

    public function handle(): int
    {
        $licenseKey = config('services.maxmind.license_key');

        if (! $licenseKey) {
            $this->error('MAXMIND_LICENSE_KEY tanımlı değil. Ücretsiz anahtar: https://www.maxmind.com/en/geolite2/signup');

            return self::FAILURE;
        }

        $targetPath = config('services.maxmind.database_path');
        $tmpDir = storage_path('app/geoip/tmp-'.Str::random(8));
        $archivePath = $tmpDir.'/geolite2-country.tar.gz';

        mkdir($tmpDir, 0755, true);

        try {
            $this->info('GeoLite2-Country indiriliyor...');

            $response = Http::timeout(60)->get('https://download.maxmind.com/app/geoip_download', [
                'edition_id' => 'GeoLite2-Country',
                'license_key' => $licenseKey,
                'suffix' => 'tar.gz',
            ]);

            if (! $response->ok()) {
                $this->error("İndirme başarısız (HTTP {$response->status()}). Lisans anahtarını kontrol edin.");

                return self::FAILURE;
            }

            file_put_contents($archivePath, $response->body());

            (new \PharData($archivePath))->extractTo($tmpDir);

            $mmdbFiles = glob($tmpDir.'/*/GeoLite2-Country.mmdb');

            if ($mmdbFiles === [] || $mmdbFiles === false) {
                $this->error('İndirilen arşivde .mmdb dosyası bulunamadı.');

                return self::FAILURE;
            }

            if (! is_dir(dirname($targetPath))) {
                mkdir(dirname($targetPath), 0755, true);
            }

            rename($mmdbFiles[0], $targetPath);

            $this->info('GeoLite2-Country veritabanı güncellendi: '.$targetPath);

            return self::SUCCESS;
        } finally {
            $this->cleanUp($tmpDir);
        }
    }

    protected function cleanUp(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
