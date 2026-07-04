<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * "Gurbet Günlüğü" (Nisoya Nabzı'nın anonim hikaye duvarı) — kullanıcı
 * hikayeleri yayınlanmadan önce admin onayından geçer.
 */
enum StoryStatus: string implements HasColor, HasLabel
{
    case Beklemede = 'beklemede';
    case Onaylandi = 'onaylandi';
    case Reddedildi = 'reddedildi';

    public function getLabel(): string
    {
        return match ($this) {
            self::Beklemede => 'Beklemede',
            self::Onaylandi => 'Onaylandı',
            self::Reddedildi => 'Reddedildi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Beklemede => 'warning',
            self::Onaylandi => 'success',
            self::Reddedildi => 'danger',
        };
    }
}
