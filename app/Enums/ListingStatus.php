<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ListingStatus: string implements HasColor, HasLabel
{
    case Taslak = 'taslak';
    case Beklemede = 'beklemede';
    case Aktif = 'aktif';
    case Pasif = 'pasif';
    case Reddedildi = 'reddedildi';

    public function getLabel(): string
    {
        return match ($this) {
            self::Taslak => 'Taslak',
            self::Beklemede => 'Onay bekliyor',
            self::Aktif => 'Aktif',
            self::Pasif => 'Pasif',
            self::Reddedildi => 'Reddedildi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Taslak => 'gray',
            self::Beklemede => 'warning',
            self::Aktif => 'success',
            self::Pasif => 'gray',
            self::Reddedildi => 'danger',
        };
    }
}
