<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Medya kütüphanesi (Faz 3 · G9) — yüklenen dosyalara (storage/app/public)
 * bakış: dizin başına disk kullanımı, dosya listeleme ve güvenli silme.
 *
 * Sahibin disk alanını görüp artık kullanılmayan dosyaları temizleyebilmesi.
 * DİKKAT: bir dosya bir ilana/sayfaya ait olabilir; silmek o yerde görseli
 * kırar (arayüzde uyarılır). Silme yol-gezinti saldırılarına kapalıdır.
 */
class MediaLibrary
{
    /** Yönetilen kök dizin (public disk). */
    public static function root(): string
    {
        return storage_path('app/public');
    }

    /**
     * Üst-seviye dizin başına kullanım + toplam.
     *
     * @return array{dirs:array<string,array{count:int,size:int}>,total:array{count:int,size:int}}
     */
    public static function usage(): array
    {
        $root = self::root();
        $dirs = [];
        $total = ['count' => 0, 'size' => 0];

        if (! is_dir($root)) {
            return ['dirs' => $dirs, 'total' => $total];
        }

        foreach (glob($root.'/*', GLOB_ONLYDIR) ?: [] as $dirPath) {
            $stat = self::measure($dirPath);
            if ($stat['count'] > 0) {
                $dirs[basename($dirPath)] = $stat;
                $total['count'] += $stat['count'];
                $total['size'] += $stat['size'];
            }
        }

        // En büyükten küçüğe sırala (disk-yiyenler üstte).
        uasort($dirs, fn (array $a, array $b): int => $b['size'] <=> $a['size']);

        return ['dirs' => $dirs, 'total' => $total];
    }

    /**
     * Bir üst-seviye dizindeki dosyaları (özyinelemeli, en yeni önce) sayfalar.
     *
     * @return array{items:array<int,array{path:string,size:int,mtime:Carbon,is_image:bool}>,total:int,pages:int,page:int}
     */
    public static function files(string $topDir, int $page = 1, int $perPage = 24): array
    {
        $base = self::safeTopDir($topDir);
        $empty = ['items' => [], 'total' => 0, 'pages' => 0, 'page' => 1];

        if ($base === null || ! is_dir($base)) {
            return $empty;
        }

        $rootLen = strlen(self::root()) + 1;
        $all = [];

        foreach (self::iterate($base) as $file) {
            $absolute = $file->getPathname();
            $relative = str_replace('\\', '/', substr($absolute, $rootLen));
            $all[] = [
                'path' => $relative,
                'size' => (int) ($file->getSize() ?: 0),
                'mtime' => Carbon::createFromTimestamp($file->getMTime() ?: time()),
                'is_image' => self::isImage($relative),
            ];
        }

        usort($all, fn (array $a, array $b): int => $b['mtime'] <=> $a['mtime']);

        $total = count($all);
        $pages = (int) max(1, ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);

        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /** Bir dosyayı güvenle siler (kök dışına çıkamaz). */
    public static function delete(string $relative): bool
    {
        $path = self::resolveFile($relative);

        return $path !== null && @unlink($path);
    }

    /** Public disk üzerinden dosyanın herkese açık URL'i. */
    public static function url(string $relative): string
    {
        return Storage::disk('public')->url($relative);
    }

    /** Uzantıya göre görsel mi? */
    public static function isImage(string $path): bool
    {
        return in_array(
            strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'],
            true
        );
    }

    /** Bir dizinin özyinelemeli dosya sayısı + toplam boyutu. */
    private static function measure(string $path): array
    {
        $count = 0;
        $size = 0;

        foreach (self::iterate($path) as $file) {
            $count++;
            $size += (int) ($file->getSize() ?: 0);
        }

        return ['count' => $count, 'size' => $size];
    }

    /** @return iterable<SplFileInfo> */
    private static function iterate(string $path): iterable
    {
        if (! is_dir($path)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile()) {
                yield $file;
            }
        }
    }

    /** Güvenli üst-seviye dizin yolu (kök altında, tek segment). */
    private static function safeTopDir(string $topDir): ?string
    {
        $topDir = trim(str_replace('\\', '/', $topDir), '/');

        // Tek segment olmalı (alt yol / gezinti yok).
        if ($topDir === '' || str_contains($topDir, '/') || str_contains($topDir, '..')) {
            return null;
        }

        $path = self::root().DIRECTORY_SEPARATOR.$topDir;

        return is_dir($path) ? $path : null;
    }

    /** Kök altında güvenli bir dosya yolu çözer (traversal'a kapalı). */
    private static function resolveFile(string $relative): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $root = realpath(self::root());
        $real = realpath(self::root().DIRECTORY_SEPARATOR.$relative);

        if ($root === false || $real === false) {
            return null;
        }

        // Çözülen yol kökün İÇİNDE olmalı.
        if (! str_starts_with($real, $root.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return is_file($real) ? $real : null;
    }
}
