<?php

namespace App\Support\Growth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * "Yeni keşif çalıştır" ile kuyruklanan parti (lot) için canlı ilerleme
 * durumu — panel {@see \App\Filament\Widgets\KesifIlerlemeWidget} bunu
 * pollar. Tek gerçek kaynak burası: {@see \App\Jobs\RunDiscoveryJob}
 * (tamamlanan sayacını artırır) ve
 * {@see \App\Filament\Resources\OutreachTargets\Pages\ListOutreachTargets}
 * (partiyi başlatır) aynı anahtar biçimini kullanır.
 *
 * DB'ye değil Cache'e yazılır: bu tamamen geçici, süslemelik bir durum —
 * kalıcılık gerektirmez ve TTL'siz kalan bir satırın temizlenmesini
 * düşünmeye gerek bırakmaz.
 */
class KesifIlerlemesi
{
    private const TTL_DAKIKA = 20;

    private static function aktifAnahtar(int $userId): string
    {
        return "kesif_aktif:{$userId}";
    }

    private static function tamamlananAnahtar(string $lot): string
    {
        return "kesif_lot:{$lot}:tamamlanan";
    }

    /** Yeni bir parti başlatır, RunDiscoveryJob'a verilecek lot kimliğini döndürür. */
    public static function baslat(int $userId, int $toplam): string
    {
        $lot = (string) Str::uuid();

        Cache::put(self::tamamlananAnahtar($lot), 0, now()->addMinutes(self::TTL_DAKIKA));

        Cache::put(self::aktifAnahtar($userId), [
            'lot' => $lot,
            'toplam' => $toplam,
            // DB'deki created_at biçimiyle (Y-m-d H:i:s) BİREBİR aynı olmalı —
            // widget bunu ham string karşılaştırmasıyla sorguluyor
            // (bkz. KesifIlerlemeWidget::getSonBulunanlar). ISO8601 ("T"/ofset)
            // burada sessizce YANLIŞ sonuç verir, hata fırlatmaz.
            'baslangic' => now()->toDateTimeString(),
        ], now()->addMinutes(self::TTL_DAKIKA));

        return $lot;
    }

    public static function tamamlaniyor(string $lot): void
    {
        Cache::increment(self::tamamlananAnahtar($lot));
    }

    /**
     * @return array{lot: string, toplam: int, baslangic: string, tamamlanan: int}|null
     */
    public static function aktifDurum(int $userId): ?array
    {
        $durum = Cache::get(self::aktifAnahtar($userId));

        if (! is_array($durum) || ! isset($durum['lot'], $durum['toplam'], $durum['baslangic'])) {
            return null;
        }

        $tamamlanan = (int) Cache::get(self::tamamlananAnahtar((string) $durum['lot']), 0);

        return [
            'lot' => (string) $durum['lot'],
            'toplam' => (int) $durum['toplam'],
            'baslangic' => (string) $durum['baslangic'],
            'tamamlanan' => min($tamamlanan, (int) $durum['toplam']),
        ];
    }
}
