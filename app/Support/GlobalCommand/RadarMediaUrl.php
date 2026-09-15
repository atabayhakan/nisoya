<?php

declare(strict_types=1);

namespace App\Support\GlobalCommand;

use App\Support\InstagramMedia;

final class RadarMediaUrl
{
    public static function instagram(?string $input): ?string
    {
        if (! is_string($input) || strlen($input) > 500) {
            return null;
        }

        $parts = parse_url(trim($input));

        if (! is_array($parts)
            || ($parts['scheme'] ?? '') !== 'https'
            || ! in_array(strtolower($parts['host'] ?? ''), ['instagram.com', 'www.instagram.com', 'instagr.am', 'www.instagr.am'], true)
            || isset($parts['user'], $parts['pass']) || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)
            || preg_match('~^/(?:reel|reels|p|tv)/([A-Za-z0-9_-]{7,35})/?$~D', $parts['path'] ?? '', $matches) !== 1) {
            return null;
        }

        return 'https://www.instagram.com/reel/'.$matches[1].'/';
    }

    public static function embed(?string $input): ?string
    {
        $canonical = self::instagram($input);

        // The existing helper is used only after strict host and path validation.
        return $canonical === null ? null : InstagramMedia::embedUrl($canonical);
    }

    public static function thumbnail(?string $input): ?string
    {
        if (! is_string($input) || strlen($input) > 500) {
            return null;
        }

        $parts = parse_url($input);

        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');

        foreach (['cdninstagram.com', 'fbcdn.net'] as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return $input;
            }
        }

        return null;
    }
}
