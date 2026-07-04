<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserStatus: string implements HasColor, HasLabel
{
    case Aktif = 'aktif';
    case Askida = 'askida';
    case Silinmis = 'silinmis';

    public function getLabel(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Askida => 'Askıda',
            self::Silinmis => 'Silinmiş',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Aktif => 'success',
            self::Askida => 'warning',
            self::Silinmis => 'danger',
        };
    }
}
