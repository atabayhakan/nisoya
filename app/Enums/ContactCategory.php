<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContactCategory: string implements HasLabel
{
    case Genel = 'genel';
    case TeknikDestek = 'teknik_destek';
    case IsBirligi = 'is_birligi';
    case Sikayet = 'sikayet';
    case Diger = 'diger';

    public function getLabel(): string
    {
        return match ($this) {
            self::Genel => 'Genel soru',
            self::TeknikDestek => 'Teknik destek',
            self::IsBirligi => 'İş birliği / reklam',
            self::Sikayet => 'Şikayet / sorun bildirimi',
            self::Diger => 'Diğer',
        };
    }
}
