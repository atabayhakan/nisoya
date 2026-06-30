<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReportStatus: string implements HasLabel, HasColor
{
    case Acik = 'acik';
    case Incelendi = 'incelendi';
    case Kapandi = 'kapandi';

    public function getLabel(): string
    {
        return match ($this) {
            self::Acik => 'Açık',
            self::Incelendi => 'İncelendi',
            self::Kapandi => 'Kapandı',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Acik => 'danger',
            self::Incelendi => 'warning',
            self::Kapandi => 'success',
        };
    }
}
