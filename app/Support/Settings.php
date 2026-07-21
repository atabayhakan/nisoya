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

        self::logChange(array_keys($values));
    }

    /**
     * Denetim izi: kim hangi ayar alanını değiştirdi (Faz 4 · İşlem Geçmişi).
     * Yalnızca panelden (kimlik doğrulanmış) yapılan değişiklikler loglanır;
     * seeder/console/test sessizce atlanır. Değerler DEĞİL, yalnızca anahtarlar
     * kaydedilir (parola gibi hassas değerler günlüğe sızmaz).
     *
     * @param  array<int,string>  $keys
     */
    protected static function logChange(array $keys): void
    {
        if ($keys === [] || ! auth()->check()) {
            return;
        }

        try {
            activity('ayar')
                ->causedBy(auth()->user())
                ->withProperties(['keys' => $keys])
                ->log(self::describeChange($keys));
        } catch (\Throwable) {
            // Denetim kaydı ayar kaydını asla bozmamalı.
        }
    }

    /**
     * Değişen anahtarların ön eklerinden okunur bir alan adı üretir
     * (ör. "E-posta (SMTP), Modüller ayarları güncellendi").
     *
     * @param  array<int,string>  $keys
     */
    protected static function describeChange(array $keys): string
    {
        $labels = [
            'mail' => 'E-posta (SMTP)', 'mail_template' => 'E-posta metinleri',
            'modul' => 'Modüller', 'seo' => 'SEO', 'duyuru' => 'Duyuru bandı',
            'ai' => 'Yapay zeka', 'home' => 'Anasayfa', 'gorunum' => 'Görünüm',
            'reklam' => 'Reklam', 'bagis' => 'Bağış', 'nabiz' => 'Nabız',
            'iletisim' => 'İletişim', 'footer' => 'Footer', 'header' => 'Header',
            'genel' => 'Genel',
        ];

        $prefixes = array_values(array_unique(array_map(
            fn (string $key): string => explode('.', $key)[0],
            $keys
        )));

        $names = array_map(fn (string $prefix): string => $labels[$prefix] ?? $prefix, $prefixes);
        $shown = implode(', ', array_slice($names, 0, 4));

        return $shown.' ayarları güncellendi';
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
