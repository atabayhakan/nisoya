<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Bir anlaşmanın (Deal) durum makinesi. Nisoya para akışını GÖRMEZ — bu kayıt
 * yalnızca iki tarafın "anlaştık / tamamlandı / sorun var" niyetini paylaştığı
 * bir defterdir. Tamamlanmış anlaşma, değerlendirmeye "Doğrulanmış işlem"
 * rozeti kazandırır (bkz. [[nisoya-islem-guvenligi]] K-C).
 *
 *   teklif → kabul → tamamlandi
 *      ↘ iptal        ↘ sorunlu (itiraz)
 */
enum DealStatus: string implements HasColor, HasLabel
{
    case Teklif = 'teklif';
    case Kabul = 'kabul';
    case Tamamlandi = 'tamamlandi';
    case Iptal = 'iptal';
    case Sorunlu = 'sorunlu';

    public function getLabel(): string
    {
        return match ($this) {
            self::Teklif => 'Teklif edildi',
            self::Kabul => 'Kabul edildi',
            self::Tamamlandi => 'Tamamlandı',
            self::Iptal => 'İptal edildi',
            self::Sorunlu => 'Sorun bildirildi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Teklif => 'warning',
            self::Kabul => 'info',
            self::Tamamlandi => 'success',
            self::Iptal => 'gray',
            self::Sorunlu => 'danger',
        };
    }

    /** Hâlâ süren (sonuçlanmamış) bir anlaşma mı? */
    public function isOpen(): bool
    {
        return $this === self::Teklif || $this === self::Kabul;
    }
}
