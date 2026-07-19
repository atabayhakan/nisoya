<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Bir şikayetin niteliği. Dolandırıcılık, moderasyon kuyruğunda öncelikli ve
 * ayrı ele alınır (bkz. FreezeFraudsterAction — dondur + parmak izi al).
 */
enum ReportCategory: string implements HasColor, HasLabel
{
    case Dolandiricilik = 'dolandiricilik';
    case Diger = 'diger';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dolandiricilik => 'Dolandırıcılık',
            self::Diger => 'Diğer',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Dolandiricilik => 'danger',
            self::Diger => 'gray',
        };
    }
}
