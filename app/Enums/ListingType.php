<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ListingType: string implements HasLabel
{
    case Hizmet = 'hizmet';
    case Urun = 'urun';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hizmet => 'Hizmet',
            self::Urun => 'Ürün',
        };
    }
}
