<?php

namespace App\Support;

/**
 * Vurgu kartı medya bloklarında (bkz. App\Filament\Support\HighlightMediaBlocks,
 * App\Models\HomeHighlight::hasMedia) kullanılan YouTube link ayrıştırma yardımcısı.
 * `watch?v=`, `youtu.be/`, `shorts/` formatlarını kabul eder.
 */
class HighlightMedia
{
    public static function youtubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $pattern = '~(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~i';

        return preg_match($pattern, $url, $m) === 1 ? $m[1] : null;
    }
}
