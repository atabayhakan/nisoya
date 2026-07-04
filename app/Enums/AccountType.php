<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Hesap tipi. Bireysel üyeler hizmet/ürün ilanı verir; kurumsal hesaplar
 * (şirket/işveren) iş ilanı verebilir. Bir üye şirket profili oluşturarak
 * kurumsal hale gelir.
 */
enum AccountType: string implements HasLabel
{
    case Bireysel = 'bireysel';
    case Kurumsal = 'kurumsal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bireysel => 'Bireysel',
            self::Kurumsal => 'Kurumsal (İşveren)',
        };
    }
}
