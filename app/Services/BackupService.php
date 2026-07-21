<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

/**
 * Bağımlılıksız, sürücü-farkında yedekleme.
 *
 * Amaç: geliştiriciye veya harici bir servise ihtiyaç duymadan site sahibinin
 * tek başına tam yedek alabilmesi (bkz. [[nisoya-admin-panel-plani]] Faz 1 · G1).
 *
 * Bir yedek = tek .zip:
 *   database/  → veritabanı dökümü
 *                  · SQLite → VACUUM INTO ile tutarlı anlık kopya (WAL güvenli)
 *                  · MySQL/MariaDB → mysqldump (parola CLI'da GÖRÜNMEZ;
 *                    --defaults-extra-file ile geçici dosyadan okunur)
 *   media/     → yüklenen dosyalar (storage/app/public)
 *   manifest.json → ne, ne zaman, hangi sürücü + geri yükleme notu
 *
 * Yedekler web'e KAPALI storage/app/backups altında tutulur; indirme yalnızca
 * admin-korumalı route üzerinden yapılır.
 */
class BackupService
{
    /** Yedeklerin saklandığı (web'den erişilemeyen) dizin. */
    public function directory(): string
    {
        return storage_path('app/backups');
    }

    /**
     * Tam yedek oluşturur (veritabanı + medya) ve .zip dosya adını döndürür.
     *
     * @throws RuntimeException arşiv veya döküm oluşturulamazsa (yarım dosya bırakmaz)
     */
    public function create(): string
    {
        File::ensureDirectoryExists($this->directory());

        $name = 'nisoya-yedek-'.now()->format('Y-m-d_His').'.zip';
        $zipPath = $this->directory().DIRECTORY_SEPARATOR.$name;

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Yedek arşivi oluşturulamadı: '.$zipPath);
        }

        $dump = null;

        try {
            // 1) Veritabanı dökümü (dosya, arşiv kapanana kadar diskte durmalı).
            //    Disk üzerindeki geçici dosya benzersiz/gizli; arşiv içinde temiz ad.
            $dump = $this->dumpDatabase();
            $dumpEntry = 'database/veritabani.'.pathinfo($dump, PATHINFO_EXTENSION);
            $zip->addFile($dump, $dumpEntry);

            // 2) Yüklenen medya
            $this->addDirectory($zip, storage_path('app/public'), 'media');

            // 3) Manifest
            $zip->addFromString(
                'manifest.json',
                (string) json_encode($this->manifest($dumpEntry), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            if (! $zip->close()) {
                throw new RuntimeException('Yedek arşivi kapatılamadı.');
            }
        } catch (\Throwable $e) {
            // Yarım/bozuk arşiv bırakma.
            @$zip->close();
            @unlink($zipPath);

            throw $e;
        } finally {
            // Geçici DB dökümü artık arşivin içinde — diskten temizle.
            if ($dump !== null && is_file($dump)) {
                @unlink($dump);
            }
        }

        return $name;
    }

    /**
     * Aktif veritabanı bağlantısının dökümünü geçici bir dosyaya alır ve yolunu
     * döndürür. Çağıran, dosyayı kullandıktan sonra silmekle yükümlüdür.
     */
    protected function dumpDatabase(): string
    {
        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");
        $stamp = now()->format('YmdHis').'-'.substr(md5(uniqid('', true)), 0, 6);

        return match ($driver) {
            'sqlite' => $this->dumpSqlite($stamp),
            'mysql', 'mariadb' => $this->dumpMysql($connection, $stamp),
            default => throw new RuntimeException(
                "Bu veritabanı sürücüsü ({$driver}) için otomatik yedekleme henüz desteklenmiyor."
            ),
        };
    }

    /**
     * SQLite: salt-okuma sorgularla mantıksal SQL dökümü.
     *
     * VACUUM INTO yerine bilinçli tercih: VACUUM bir transaction içinden
     * çalışamaz (ör. testler) ve :memory: veritabanını dosyaya alamaz. Mantıksal
     * döküm yalnızca SELECT + sqlite_master okuduğu için her koşulda çalışır ve
     * taşınabilir bir .sql üretir. (Üretimde MySQL → dumpMysql kullanılır; bu yol
     * yalnızca yerel/dosya-tabanlı SQLite içindir.)
     */
    protected function dumpSqlite(string $stamp): string
    {
        $target = $this->directory().DIRECTORY_SEPARATOR.".db-{$stamp}.sql";

        $pdo = DB::connection()->getPdo();
        $handle = fopen($target, 'w');
        if ($handle === false) {
            throw new RuntimeException('Döküm dosyası açılamadı: '.$target);
        }

        try {
            fwrite($handle, '-- Nisoya SQLite yedeği · '.now()->toDateTimeString()."\n");
            fwrite($handle, "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n");

            $objects = $pdo->query(
                "SELECT type, name, sql FROM sqlite_master
                 WHERE sql IS NOT NULL AND name NOT LIKE 'sqlite_%'
                 ORDER BY CASE type WHEN 'table' THEN 1 WHEN 'view' THEN 2 WHEN 'index' THEN 3 ELSE 4 END, name"
            )->fetchAll(\PDO::FETCH_ASSOC);

            // 1) Önce tablolar ve verileri
            foreach ($objects as $obj) {
                if ($obj['type'] !== 'table') {
                    continue;
                }
                fwrite($handle, $obj['sql'].";\n");
                $this->writeSqliteTableData($pdo, $handle, (string) $obj['name']);
            }

            // 2) Sonra view / index / trigger (veriler yüklendikten sonra)
            foreach ($objects as $obj) {
                if ($obj['type'] === 'table') {
                    continue;
                }
                fwrite($handle, $obj['sql'].";\n");
            }

            fwrite($handle, "COMMIT;\nPRAGMA foreign_keys=ON;\n");
        } finally {
            fclose($handle);
        }

        return $target;
    }

    /** Bir tablonun tüm satırlarını INSERT ifadeleri olarak dosyaya yazar. */
    private function writeSqliteTableData(\PDO $pdo, mixed $handle, string $table): void
    {
        $quotedTable = '"'.str_replace('"', '""', $table).'"';
        $rows = $pdo->query('SELECT * FROM '.$quotedTable);

        foreach ($rows as $row) {
            $columns = array_map(
                fn ($c): string => '"'.str_replace('"', '""', (string) $c).'"',
                array_keys($row)
            );

            $values = array_map(function ($v) use ($pdo): string {
                if ($v === null) {
                    return 'NULL';
                }
                if (is_int($v) || is_float($v)) {
                    return (string) $v;
                }

                return $pdo->quote((string) $v);
            }, array_values($row));

            fwrite(
                $handle,
                'INSERT INTO '.$quotedTable.' ('.implode(', ', $columns).') VALUES ('.implode(', ', $values).");\n"
            );
        }
    }

    /** MySQL/MariaDB: mysqldump; parola geçici defaults dosyasından okunur. */
    protected function dumpMysql(string $connection, string $stamp): string
    {
        $cfg = (array) config("database.connections.{$connection}");
        $target = $this->directory().DIRECTORY_SEPARATOR.".db-{$stamp}.sql";

        // Parolayı komut satırı argümanı YAPMA (ps/`/proc` üzerinden sızar) —
        // geçici, 0600 izinli bir [client] dosyasına yaz.
        $defaults = tempnam(sys_get_temp_dir(), 'nsy-my');
        file_put_contents($defaults, implode("\n", [
            '[client]',
            "host = '".($cfg['host'] ?? '127.0.0.1')."'",
            "port = '".($cfg['port'] ?? 3306)."'",
            "user = '".($cfg['username'] ?? '')."'",
            "password = '".($cfg['password'] ?? '')."'",
            '',
        ]));
        @chmod($defaults, 0600);

        $handle = fopen($target, 'w');
        if ($handle === false) {
            @unlink($defaults);
            throw new RuntimeException('Döküm dosyası açılamadı: '.$target);
        }

        try {
            $result = Process::timeout(1800)->run([
                (string) config('backup.mysqldump_path', 'mysqldump'),
                '--defaults-extra-file='.$defaults,
                '--single-transaction', // InnoDB için tutarlı, kilitsiz döküm
                '--quick',              // satırları belleğe yığmadan akıt
                '--no-tablespaces',     // PROCESS yetkisi gerektirmesin
                '--default-character-set=utf8mb4',
                '--routines',
                '--events',
                (string) ($cfg['database'] ?? ''),
            ], function (string $type, string $chunk) use ($handle) {
                if ($type === 'out') {
                    fwrite($handle, $chunk);
                }
            });
        } finally {
            fclose($handle);
            @unlink($defaults);
        }

        if (! $result->successful()) {
            @unlink($target);
            throw new RuntimeException(
                'mysqldump başarısız oldu: '.trim($result->errorOutput() ?: 'bilinmeyen hata')
            );
        }

        return $target;
    }

    /** Bir dizini (varsa) özyinelemeli olarak arşive ekler. */
    protected function addDirectory(ZipArchive $zip, string $source, string $prefix): void
    {
        if (! is_dir($source)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (! $file->isFile()) {
                continue;
            }

            $absolute = $file->getPathname();
            $relative = $prefix.'/'.str_replace(
                '\\', '/', ltrim(substr($absolute, strlen($source)), '\\/')
            );

            $zip->addFile($absolute, $relative);
        }
    }

    /**
     * @param  string  $dumpEntry  arşiv içindeki döküm dosyasının yolu (ör. database/veritabani.sql)
     * @return array<string,mixed>
     */
    protected function manifest(string $dumpEntry): array
    {
        $connection = (string) config('database.default');

        return [
            'app' => config('app.name'),
            'created_at' => now()->toIso8601String(),
            'db_driver' => config("database.connections.{$connection}.driver"),
            'db_dump' => $dumpEntry,
            'media_dir' => 'media/',
            'nasil_geri_yuklenir' => 'database/ içindeki döküm dosyasını ilgili '
                .'veritabanına içe aktarın (SQLite: dosyayı database/database.sqlite '
                .'olarak değiştirin · MySQL: mysql ile içe aktarın). Ardından media/ '
                .'klasörünün içeriğini storage/app/public altına kopyalayın.',
        ];
    }

    /**
     * Mevcut yedekleri (en yeni önce) döndürür.
     *
     * @return array<int,array{name:string,size:int,created_at:Carbon}>
     */
    public function list(): array
    {
        if (! is_dir($this->directory())) {
            return [];
        }

        $out = [];
        foreach (glob($this->directory().DIRECTORY_SEPARATOR.'*.zip') ?: [] as $path) {
            $out[] = [
                'name' => basename($path),
                'size' => (int) (filesize($path) ?: 0),
                'created_at' => Carbon::createFromTimestamp(filemtime($path) ?: time()),
            ];
        }

        usort($out, fn (array $a, array $b): int => $b['created_at'] <=> $a['created_at']);

        return $out;
    }

    /**
     * Verilen ada karşılık gelen güvenli dosya yolunu döndürür (yol-gezinti
     * saldırılarına kapalı). Dosya yoksa null.
     */
    public function path(string $name): ?string
    {
        $name = basename($name); // "../" vb. temizle
        if (! str_ends_with($name, '.zip')) {
            return null;
        }

        $path = $this->directory().DIRECTORY_SEPARATOR.$name;

        return is_file($path) ? $path : null;
    }

    /** Bir yedeği siler. Dosya yoksa/silemezse false. */
    public function delete(string $name): bool
    {
        $path = $this->path($name);

        return $path !== null && @unlink($path);
    }

    /**
     * $keepDays günden eski yedekleri temizler; en yeni yedek DAİMA korunur.
     * Silinen yedek sayısını döndürür.
     */
    public function prune(?int $keepDays = null): int
    {
        $keepDays = $keepDays ?? (int) config('backup.keep_days', 7);
        if ($keepDays <= 0) {
            return 0; // temizlik kapalı
        }

        $all = $this->list(); // en yeni önce
        $cutoff = now()->subDays($keepDays);
        $removed = 0;

        foreach ($all as $index => $backup) {
            if ($index === 0) {
                continue; // en yeni yedeği asla silme
            }
            if ($backup['created_at']->lt($cutoff) && $this->delete($backup['name'])) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Yönetim sayfasındaki sağlık kartları için özet.
     *
     * @return array{count:int,total_size:int,latest:?Carbon,free_space:float,driver:string}
     */
    public function stats(): array
    {
        $all = $this->list();
        $connection = (string) config('database.default');

        return [
            'count' => count($all),
            'total_size' => (int) array_sum(array_column($all, 'size')),
            'latest' => $all[0]['created_at'] ?? null,
            'free_space' => is_dir($this->directory()) ? (float) (disk_free_space($this->directory()) ?: 0) : 0.0,
            'driver' => (string) config("database.connections.{$connection}.driver"),
        ];
    }

    /** Üretimde (MySQL) mysqldump erişilebilir mi? UI bilgilendirmesi için. */
    public function mysqldumpAvailable(): bool
    {
        try {
            return Process::timeout(15)
                ->run([(string) config('backup.mysqldump_path', 'mysqldump'), '--version'])
                ->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /** Bayt sayısını okunur biçime çevirir (kartlarda/gösterimde). */
    public static function humanSize(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max((float) $bytes, 0);
        $pow = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), 2).' '.$units[$pow];
    }
}
