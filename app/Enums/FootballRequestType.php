<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FootballRequestType: string implements HasColor, HasLabel
{
    case OyuncuAraniyor = 'oyuncu_araniyor';
    case MacAriyorum = 'mac_ariyorum';

    public function getLabel(): string
    {
        return match ($this) {
            self::OyuncuAraniyor => 'Oyuncu Aranıyor',
            self::MacAriyorum => 'Maç Arıyorum',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OyuncuAraniyor => 'warning',
            self::MacAriyorum => 'info',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::OyuncuAraniyor => '👥',
            self::MacAriyorum => '⚽',
        };
    }
}
