<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReviewStatus: string implements HasColor, HasLabel
{
    case Yayinda = 'yayinda';
    case Gizli = 'gizli';

    public function getLabel(): string
    {
        return match ($this) {
            self::Yayinda => 'Yayında',
            self::Gizli => 'Gizli',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Yayinda => 'success',
            self::Gizli => 'danger',
        };
    }
}
