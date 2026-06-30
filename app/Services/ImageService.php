<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageService
{
    /**
     * Yüklenen görseli küçültüp WebP olarak saklar; başarısızsa orijinali saklar.
     * Depolama/bant genişliği tasarrufu için.
     */
    public function storeOptimized(UploadedFile $file, string $dir, int $maxWidth = 1600, int $quality = 80): string
    {
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());

            if ($image->width() > $maxWidth) {
                $image->scaleDown(width: $maxWidth);
            }

            $path = $dir.'/'.Str::uuid()->toString().'.webp';
            Storage::disk('public')->put($path, (string) $image->toWebp($quality));

            return $path;
        } catch (\Throwable $e) {
            // Optimizasyon mümkün olmazsa orijinal dosyayı sakla
            return $file->store($dir, 'public');
        }
    }
}
