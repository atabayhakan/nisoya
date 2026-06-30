<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PageStatus: string implements HasLabel, HasColor
{
    case Taslak = 'taslak';
    case Yayin = 'yayin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Taslak => 'Taslak',
            self::Yayin => 'Yayında',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Taslak => 'gray',
            self::Yayin => 'success',
        };
    }
}
