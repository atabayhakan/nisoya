<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FeatureRequestStatus: string implements HasLabel, HasColor
{
    case Beklemede = 'beklemede';
    case Onaylandi = 'onaylandi';
    case Reddedildi = 'reddedildi';

    public function getLabel(): string
    {
        return match ($this) {
            self::Beklemede => 'Beklemede',
            self::Onaylandi => 'Onaylandı',
            self::Reddedildi => 'Reddedildi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Beklemede => 'warning',
            self::Onaylandi => 'success',
            self::Reddedildi => 'danger',
        };
    }
}
