<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RsvpStatus: string implements HasLabel
{
    case Geliyor = 'geliyor';
    case Belki = 'belki';
    case Gelmiyor = 'gelmiyor';

    public function getLabel(): string
    {
        return match ($this) {
            self::Geliyor => 'Geliyorum 🎉',
            self::Belki => 'Belki',
            self::Gelmiyor => 'Gelemiyorum',
        };
    }
}
