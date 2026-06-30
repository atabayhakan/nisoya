<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReviewStatus: string implements HasLabel
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
}
