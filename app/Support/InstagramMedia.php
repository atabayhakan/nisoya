<?php

namespace App\Support;

/**
 * Instagram Reels & Post URL ayrıştırma, shortcode ve kullanıcı adı formatlama yardımcısı.
 */
class InstagramMedia
{
    /**
     * Verilen URL veya metinden Instagram shortcode'unu çıkarır.
     * Desteklenen formatlar:
     * - https://www.instagram.com/reel/C8xABC12345/
     * - https://www.instagram.com/reels/C8xABC12345/
     * - https://www.instagram.com/p/C8xABC12345/
     * - https://www.instagram.com/tv/C8xABC12345/
     * - https://instagr.am/reel/C8xABC12345/
     * - Veya doğrudan shortcode ("C8xABC12345")
     */
    public static function extractShortcode(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $trimmed = trim($url);

        $pattern = '~(?:instagram\.com|instagr\.am)/(?:reel|reels|p|tv)/([A-Za-z0-9_-]+)~i';
        if (preg_match($pattern, $trimmed, $m) === 1) {
            return $m[1];
        }

        // Eğer doğrudan shortcode verilmişse (ör: C8xABC12345)
        if (preg_match('/^[A-Za-z0-9_-]{7,35}$/', $trimmed) === 1) {
            return $trimmed;
        }

        return null;
    }

    /**
     * Instagram embed iframe URL'sini üretir.
     */
    public static function embedUrl(?string $urlOrShortcode): ?string
    {
        $shortcode = self::extractShortcode($urlOrShortcode);
        if (! $shortcode) {
            return null;
        }

        return "https://www.instagram.com/reel/{$shortcode}/embed";
    }

    /**
     * Kullanıcı adını '@' ile standartlaştırır veya profil linkinden ayıklar.
     */
    public static function normalizeUsername(?string $handleOrUrl): ?string
    {
        if (! $handleOrUrl) {
            return null;
        }

        $val = trim($handleOrUrl);

        // Profil URL'si verilmişse: https://www.instagram.com/berlin_turkleri/
        if (preg_match('~(?:instagram\.com|instagr\.am)/([A-Za-z0-9._]+)/?$~i', $val, $m) === 1) {
            if (! in_array(strtolower($m[1]), ['reel', 'reels', 'p', 'tv', 'explore', 'stories'], true)) {
                return '@'.ltrim($m[1], '@');
            }
        }

        $clean = ltrim($val, '@');

        return $clean !== '' ? '@'.$clean : null;
    }
}
