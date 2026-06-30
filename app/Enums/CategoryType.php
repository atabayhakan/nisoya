<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CategoryType: string implements HasLabel
{
    case Hizmet = 'hizmet';
    case Urun = 'urun';
    case Ikisi = 'ikisi';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hizmet => 'Hizmet',
            self::Urun => 'Ürün',
            self::Ikisi => 'Hizmet ve Ürün',
        };
    }
}
