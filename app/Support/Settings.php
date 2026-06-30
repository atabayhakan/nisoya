<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    public const CACHE_KEY = 'site_settings';

    /** Tüm ayarları (key => value) cache'den döndürür. */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => SiteSetting::query()->pluck('value', 'key')->all());
    }

    /** Bir ayarı döndürür; DB'de yoksa config varsayılanına, o da yoksa $default'a düşer. */
    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::all();

        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        // Not: anahtarlar nokta içerdiği için config() nokta-notasyonu yerine
        // diziyi doğrudan literal anahtarla indeksliyoruz.
        $fields = config('site_defaults.fields', []);

        return $fields[$key]['default'] ?? $default;
    }

    /** Ayarları toplu kaydeder (upsert) ve cache'i temizler. */
    public static function setMany(array $values): void
    {
        $fields = config('site_defaults.fields', []);

        foreach ($values as $key => $value) {
            $group = $fields[$key]['group'] ?? 'genel';
            SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        }

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
