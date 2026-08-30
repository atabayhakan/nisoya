<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FootballPosition: string implements HasLabel
{
    case Kaleci = 'kaleci';
    case Defans = 'defans';
    case OrtaSaha = 'orta_saha';
    case Kanat = 'kanat';
    case Forvet = 'forvet';

    public function getLabel(): string
    {
        return match ($this) {
            self::Kaleci => 'Kaleci',
            self::Defans => 'Defans',
            self::OrtaSaha => 'Orta Saha',
            self::Kanat => 'Kanat',
            self::Forvet => 'Forvet',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Kaleci => '🧤',
            self::Defans => '🛡️',
            self::OrtaSaha => '⚙️',
            self::Kanat => '⚡',
            self::Forvet => '🎯',
        };
    }

    public function labelWithEmoji(): string
    {
        return $this->emoji().' '.$this->getLabel();
    }
}
