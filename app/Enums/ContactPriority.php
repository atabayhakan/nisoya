<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Destek biletinin önceliği. Bilinçli olarak 3 kademe: tek kişilik ekipte
 * 5 kademeli bir ölçek karar yorgunluğundan başka bir şey üretmez.
 * Varsayılan 'normal' — sahibin çoğu bilete dokunmadan geçebilmesi için.
 */
enum ContactPriority: string implements HasColor, HasLabel
{
    case Dusuk = 'dusuk';
    case Normal = 'normal';
    case Acil = 'acil';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dusuk => 'Düşük',
            self::Normal => 'Normal',
            self::Acil => 'Acil',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Dusuk => 'gray',
            self::Normal => 'info',
            self::Acil => 'danger',
        };
    }

    /** Tabloda acil olanların üste gelmesi için sıralama ağırlığı. */
    public function agirlik(): int
    {
        return match ($this) {
            self::Acil => 3,
            self::Normal => 2,
            self::Dusuk => 1,
        };
    }
}
