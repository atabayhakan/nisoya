<?php

use App\Support\Settings;
use App\Support\Tema;

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
        // Vitrin temasında favicon/theme-color da Vitrin birincilini kullanır
        // (marka_rengi ayarı klasiğin eksenidir — panelde de öyle etiketlenir).
        if (Tema::vitrinMi()) {
            return '#3E63F0';
        }

        $brand = setting('gorunum.marka_rengi') ?: 'emerald';
        $colors = config('brand_colors', []);

        return $colors[$brand]['hex'] ?? ($colors['emerald']['hex'] ?? '#059669');
    }
}
