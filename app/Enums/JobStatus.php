<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** İş ilanı durumu. */
enum JobStatus: string implements HasColor, HasLabel
{
    case Beklemede = 'beklemede';
    case Aktif = 'aktif';
    case Kapali = 'kapali';
    case Dolu = 'dolu';

    public function getLabel(): string
    {
        return match ($this) {
            self::Beklemede => 'Beklemede',
            self::Aktif => 'Aktif',
            self::Kapali => 'Kapalı',
            self::Dolu => 'Dolduruldu',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Beklemede => 'warning',
            self::Aktif => 'success',
            self::Kapali => 'gray',
            self::Dolu => 'info',
        };
    }
}
