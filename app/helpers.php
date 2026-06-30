<?php

use App\Support\Settings;

if (! function_exists('setting')) {
    /** Site içerik ayarını döndürür (cache'li, varsayılana düşer). */
    function setting(string $key, ?string $default = null): ?string
    {
        return Settings::get($key, $default);
    }
}
