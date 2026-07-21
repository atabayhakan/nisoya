<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Medya kütüphanesi (Faz 3 · G9 & İleri Düzey Yönetim) — yüklenen tüm dosyalara
 * (storage/app/public) bakış: iOS tarzı depolama dağılım grafiği, dinamik klasör
 * oluşturma, arama, filtreleme ve güvenli silme.
 */
class MediaLibrary
{
    /** Yönetilen kök dizin (public disk). */
    public static function root(): string
    {
        return storage_path('app/public');
    }

    /**
     * Güvenli yeni klasör oluşturur.
     */
    public static function createDirectory(string $name): bool
    {
        $clean = Str::slug($name);

        if ($clean === '' || str_contains($clean, '.') || str_contains($clean, '/')) {
            return false;
        }

        $path = self::root() . DIRECTORY_SEPARATOR . $clean;

        if (is_dir($path)) {
            return true;
        }

        return @mkdir($path, 0755, true);
    }

    /**
     * iOS Depolama Alanı tarzı tür dağılımı (Görsel, Video, Doküman, Diğer).
     *
     * @return array{
     *   images: array{size: int, count: int, percent: float},
     *   videos: array{size: int, count: int, percent: float},
     *   documents: array{size: int, count: int, percent: float},
     *   others: array{size: int, count: int, percent: float},
     *   total_size: int,
     *   total_count: int,
     *   free_space: float
     * }
     */
    public static function typeBreakdown(): array
    {
        $root = self::root();
        $images = ['size' => 0, 'count' => 0];
        $videos = ['size' => 0, 'count' => 0];
        $documents = ['size' => 0, 'count' => 0];
        $others = ['size' => 0, 'count' => 0];

        if (is_dir($root)) {
            foreach (self::iterate($root) as $file) {
                $size = (int) ($file->getSize() ?: 0);
                $ext = strtolower($file->getExtension());

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'], true)) {
                    $images['size'] += $size;
                    $images['count']++;
                } elseif (in_array($ext, ['mp4', 'mov', 'webm', 'm4v', 'ogg', 'avi', 'mkv'], true)) {
                    $videos['size'] += $size;
                    $videos['count']++;
                } elseif (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'], true)) {
                    $documents['size'] += $size;
                    $documents['count']++;
                } else {
                    $others['size'] += $size;
                    $others['count']++;
                }
            }
        }

        $totalSize = $images['size'] + $videos['size'] + $documents['size'] + $others['size'];
        $totalCount = $images['count'] + $videos['count'] + $documents['count'] + $others['count'];

        $calcPercent = fn (int $size): float => $totalSize > 0 ? round(($size / $totalSize) * 100, 1) : 0.0;

        return [
            'images' => [
                'size' => $images['size'],
                'count' => $images['count'],
                'percent' => $calcPercent($images['size']),
            ],
            'videos' => [
                'size' => $videos['size'],
                'count' => $videos['count'],
                'percent' => $calcPercent($videos['size']),
            ],
            'documents' => [
                'size' => $documents['size'],
                'count' => $documents['count'],
                'percent' => $calcPercent($documents['size']),
            ],
            'others' => [
                'size' => $others['size'],
                'count' => $others['count'],
                'percent' => $calcPercent($others['size']),
            ],
            'total_size' => $totalSize,
            'total_count' => $totalCount,
            'free_space' => (float) (@disk_free_space($root) ?: 0),
        ];
    }

    /**
     * Üst-seviye dizin başına kullanım + toplam.
     */
    public static function usage(): array
    {
        $root = self::root();
        $dirs = [];
        $total = ['count' => 0, 'size' => 0];

        if (! is_dir($root)) {
            return ['dirs' => $dirs, 'total' => $total];
        }

        // Standart sistem klasörleri (ör. listings, avatars, highlights...)
        $standardDirs = ['listings', 'avatars', 'event-media', 'portfolio', 'highlights', 'pages', 'uploads'];
        foreach ($standardDirs as $std) {
            $path = $root . DIRECTORY_SEPARATOR . $std;
            if (is_dir($path)) {
                $stat = self::measure($path);
                $dirs[$std] = $stat;
                $total['count'] += $stat['count'];
                $total['size'] += $stat['size'];
            }
        }

        // Tüm diğer özel oluşturulan klasörleri tara
        foreach (File::directories($root) as $dirPath) {
            $name = basename($dirPath);
            if (! isset($dirs[$name])) {
                $stat = self::measure($dirPath);
                $dirs[$name] = $stat;
                $total['count'] += $stat['count'];
                $total['size'] += $stat['size'];
            }
        }

        uasort($dirs, fn (array $a, array $b): int => $b['size'] <=> $a['size']);

        return ['dirs' => $dirs, 'total' => $total];
    }

    /**
     * Bir dizindeki dosyaları tür ve arama filtresiyle sayfalar.
     */
    public static function files(string $topDir, int $page = 1, int $perPage = 24, ?string $type = null, ?string $search = null): array
    {
        $base = self::safeTopDir($topDir);
        $empty = ['items' => [], 'total' => 0, 'pages' => 0, 'page' => 1];

        if ($base === null || ! is_dir($base)) {
            return $empty;
        }

        $rootLen = strlen(self::root()) + 1;
        $all = [];
        $search = $search ? mb_strtolower(trim($search)) : null;

        foreach (self::iterate($base) as $file) {
            $absolute = $file->getPathname();
            $relative = str_replace('\\', '/', substr($absolute, $rootLen));
            $filename = mb_strtolower(basename($relative));

            if ($search !== null && $search !== '' && ! str_contains($filename, $search) && ! str_contains(mb_strtolower($relative), $search)) {
                continue;
            }

            $isImg = self::isImage($relative);
            $isVid = self::isVideo($relative);
            $isDoc = self::isDocument($relative);

            if ($type === 'image' && ! $isImg) continue;
            if ($type === 'video' && ! $isVid) continue;
            if ($type === 'document' && ! $isDoc) continue;

            $all[] = [
                'path' => $relative,
                'size' => (int) ($file->getSize() ?: 0),
                'mtime' => Carbon::createFromTimestamp($file->getMTime() ?: time()),
                'is_image' => $isImg,
                'is_video' => $isVid,
                'is_document' => $isDoc,
                'url' => self::url($relative),
            ];
        }

        usort($all, fn (array $a, array $b): int => $b['mtime'] <=> $a['mtime']);

        $total = count($all);
        $pages = (int) max(1, ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);

        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /** Bir dosyayı güvenle siler. */
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

    public static function isImage(string $path): bool
    {
        return in_array(
            strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'],
            true
        );
    }

    public static function isVideo(string $path): bool
    {
        return in_array(
            strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            ['mp4', 'mov', 'webm', 'm4v', 'ogg', 'avi', 'mkv'],
            true
        );
    }

    public static function isDocument(string $path): bool
    {
        return in_array(
            strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'],
            true
        );
    }

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

    private static function safeTopDir(string $topDir): ?string
    {
        $topDir = trim(str_replace('\\', '/', $topDir), '/');

        if ($topDir === '' || str_contains($topDir, '/') || str_contains($topDir, '..')) {
            return null;
        }

        $path = self::root() . DIRECTORY_SEPARATOR . $topDir;

        return is_dir($path) ? $path : null;
    }

    private static function resolveFile(string $relative): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $root = realpath(self::root());
        $real = realpath(self::root() . DIRECTORY_SEPARATOR . $relative);

        if ($root === false || $real === false) {
            return null;
        }

        if (! str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return is_file($real) ? $real : null;
    }
}
