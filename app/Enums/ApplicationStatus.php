<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** İş başvurusu durumu (işveren tarafından güncellenir). */
enum ApplicationStatus: string implements HasColor, HasLabel
{
    case Gonderildi = 'gonderildi';
    case Incelendi = 'incelendi';
    case Gorusme = 'gorusme';
    case Kabul = 'kabul';
    case Red = 'red';

    public function getLabel(): string
    {
        return match ($this) {
            self::Gonderildi => 'Gönderildi',
            self::Incelendi => 'İncelendi',
            self::Gorusme => 'Görüşmeye çağrıldı',
            self::Kabul => 'Kabul edildi',
            self::Red => 'Olumsuz',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Gonderildi => 'gray',
            self::Incelendi => 'info',
            self::Gorusme => 'warning',
            self::Kabul => 'success',
            self::Red => 'danger',
        };
    }
}
