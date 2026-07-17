<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ContactMessageStatus: string implements HasColor, HasLabel
{
    case Yeni = 'yeni';
    case Okundu = 'okundu';
    case Yanitlandi = 'yanitlandi';

    public function getLabel(): string
    {
        return match ($this) {
            self::Yeni => 'Yeni',
            self::Okundu => 'Okundu',
            self::Yanitlandi => 'Yanıtlandı',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Yeni => 'danger',
            self::Okundu => 'warning',
            self::Yanitlandi => 'success',
        };
    }
}
