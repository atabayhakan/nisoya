<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FootballMatchStatus: string implements HasColor, HasLabel
{
    case Planlandi = 'planlandi';
    case Onaylandi = 'onaylandi';
    case Oynandi = 'oynandi';
    case Iptal = 'iptal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Planlandi => 'Planlandı (Onay Bekliyor)',
            self::Onaylandi => 'Onaylandı',
            self::Oynandi => 'Oynandı',
            self::Iptal => 'İptal Edildi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Planlandi => 'warning',
            self::Onaylandi => 'info',
            self::Oynandi => 'success',
            self::Iptal => 'gray',
        };
    }
}
