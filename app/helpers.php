<?php

use App\Support\Settings;

if (! function_exists('setting')) {
    /** Site içerik ayarını döndürür (cache'li, varsayılana düşer). */
    function setting(string $key, ?string $default = null): ?string
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('brandColorHex')) {
    /**
     * Seçili marka renginin CSS değişkeni KULLANAMAYAN yerlerde (favicon,
     * <meta name="theme-color">) kullanılacak hex karşılığını döndürür.
     */
    function brandColorHex(): string
    {
        $brand = setting('gorunum.marka_rengi') ?: 'emerald';
        $colors = config('brand_colors', []);

        return $colors[$brand]['hex'] ?? ($colors['emerald']['hex'] ?? '#059669');
    }
}
