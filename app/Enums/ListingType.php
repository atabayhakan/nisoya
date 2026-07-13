<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ListingType: string implements HasLabel
{
    case Hizmet = 'hizmet';
    case Urun = 'urun';
    case Emlak = 'emlak';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hizmet => 'Hizmet',
            self::Urun => 'Ürün',
            self::Emlak => 'Emlak',
        };
    }
}
