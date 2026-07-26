<?php

namespace App\Support;

/**
 * Tema motoru (Vitrin — Faz P0).
 *
 * İki tema ekseni vardır ve KARIŞTIRILMAMALIDIR:
 * - `gorunum.tema` (klasik|vitrin): hangi VIEW AĞACININ yükleneceğini seçer
 *   (bkz. TemaViewYollari middleware'i — vitrin'de resources/views/vitrin
 *   finder yollarının başına eklenir, override yoksa klasiğe düşülür).
 * - `gorunum.tasarim_modu` (eski|yeni|obsidian|nordic): KLASİK temanın kendi
 *   iç varyantıdır (CSS değişken preset'leri). Vitrin aktifken hükümsüzdür —
 *   aksi hâlde kalıntı bir 'yeni'/'obsidian' değeri, vitrin'e henüz
 *   taşınmamış fallback sayfalara serif başlık / mühür rozeti / koyu-mod
 *   kilidi sızdırırdı. Bu yüzden TÜM tasarim_modu okumaları tasarimModu()
 *   tek kapısından geçmelidir; setting('gorunum.tasarim_modu') doğrudan
 *   OKUNMAZ (bekçi: tests/Feature/TemaTest.php).
 *
 * Uzun ömürlü süreçler (feature test süiti, ileride Octane): finder 'view'
 * singleton'ında yaşadığı için TemaViewYollari yolları İDEMPOTENT eşitler —
 * yol yalnız durum değişiminde eklenir/çıkarılır ve finder cache'i o anda
 * flush edilir (bkz. finderYollariniEsitle, P0 inceleme bulgusu #1).
 */
final class Tema
{
    public const TEMALAR = ['klasik', 'vitrin'];

    /**
     * Aktif tema. Sıra: admin oturum önizlemesi > kayıtlı ayar > klasik.
     * Tema motoru siteyi asla düşürmez — her hatada klasiğe düşer.
     */
    public static function aktif(): string
    {
        try {
            // Admin gizli önizlemesi (?tema_onizleme=vitrin): yalnızca
            // TemaViewYollari, panel yetkisi olan kullanıcı için yazar.
            $onizleme = app()->bound('session') && app('session')->isStarted()
                ? session('tema_onizleme')
                : null;

            $tema = $onizleme ?: Settings::get('gorunum.tema', 'klasik');

            return in_array($tema, self::TEMALAR, true) ? $tema : 'klasik';
        } catch (\Throwable) {
            return 'klasik';
        }
    }

    public static function vitrinMi(): bool
    {
        return self::aktif() === 'vitrin';
    }

    /**
     * Tek kapı: tasarim_modu yalnız klasik temada hükümlüdür.
     * Vitrin aktifken daima 'eski' döner (nötr varsayılan) — böylece
     * 'yeni' serif/mühür ve 'obsidian' koyu-kilit dalları hiç çalışmaz.
     */
    public static function tasarimModu(): string
    {
        if (self::vitrinMi()) {
            return 'eski';
        }

        return Settings::get('gorunum.tasarim_modu', 'eski') ?? 'eski';
    }

    /** Koyu-mod kilidi (Midnight Obsidian) — yalnız klasik temada mümkün. */
    public static function koyuKilit(): bool
    {
        return self::tasarimModu() === 'obsidian';
    }
}
