<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Destek bileti durumu. 'kapandi' destek sistemiyle (2026-07-27) eklendi:
 * öncesinde bir bilet "yanıtlandı"dan sonra sonsuza kadar listede kalıyordu,
 * dolayısıyla "işim bitti mi" sorusunun cevabı yoktu.
 */
enum ContactMessageStatus: string implements HasColor, HasLabel
{
    case Yeni = 'yeni';
    case Okundu = 'okundu';
    case Yanitlandi = 'yanitlandi';
    case Kapandi = 'kapandi';

    public function getLabel(): string
    {
        return match ($this) {
            self::Yeni => 'Yeni',
            self::Okundu => 'Okundu',
            self::Yanitlandi => 'Yanıtlandı',
            self::Kapandi => 'Kapandı',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Yeni => 'danger',
            self::Okundu => 'warning',
            self::Yanitlandi => 'success',
            self::Kapandi => 'gray',
        };
    }

    /** Hâlâ ilgilenilmesi gereken bilet mi? (sekme/rozet sayımları için) */
    public function acikMi(): bool
    {
        return in_array($this, [self::Yeni, self::Okundu], true);
    }
}
