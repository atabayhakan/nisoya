<?php

namespace App\Support;

/**
 * Dikey modüllerin (emlak, vasıta, davetiye, iş ilanları) açık/kapalı durumu.
 *
 * Sahibin panelden tek tıkla bir dikeyi kapatabilmesi (Faz 2 · G4). Kapalı
 * modülün public rotaları 404 döner (EnsureModuleEnabled middleware), yeni
 * içerik oluşturulamaz, footer/sitemap girişleri gizlenir. Menü DB-tabanlı
 * (NavigationLinks) olduğundan menü linkini sahip Menü sayfasından yönetir.
 *
 * Değer DB'de (site_settings, anahtar "modul.<key>"); yoksa varsayılan AÇIK.
 */
class Modules
{
    /** Yönetilebilir dikey modüller. */
    public const KEYS = ['emlak', 'vasita', 'davetiye', 'is_ilanlari', 'rehber'];

    /** İnsan-okunur etiketler (admin sayfası ve mesajlar için). */
    public const LABELS = [
        'emlak' => 'Emlak',
        'vasita' => 'Vasıta',
        'davetiye' => 'Davetiye',
        'is_ilanlari' => 'İş İlanları',
        'rehber' => 'Ülke Rehberi',
    ];

    /** Modül açık mı? Bilinmeyen/ayarsız anahtar varsayılan olarak AÇIK. */
    public static function enabled(string $key): bool
    {
        return (Settings::get("modul.{$key}") ?? '1') === '1';
    }

    /**
     * Tüm modüllerin durumu.
     *
     * @return array<string,bool>
     */
    public static function all(): array
    {
        $out = [];
        foreach (self::KEYS as $key) {
            $out[$key] = self::enabled($key);
        }

        return $out;
    }
}
