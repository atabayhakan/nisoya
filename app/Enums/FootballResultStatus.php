<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FootballResultStatus: string implements HasColor, HasLabel
{
    case Beklemede = 'beklemede';
    case Girildi = 'girildi';
    case Dogrulandi = 'dogrulandi';
    case Itiraz = 'itiraz';

    public function getLabel(): string
    {
        return match ($this) {
            self::Beklemede => 'Skor Bekleniyor',
            self::Girildi => 'Rakip Onayı Bekliyor',
            self::Dogrulandi => 'Doğrulanmış Maç',
            self::Itiraz => 'İtiraz Edildi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Beklemede => 'gray',
            self::Girildi => 'warning',
            self::Dogrulandi => 'success',
            self::Itiraz => 'danger',
        };
    }
}
