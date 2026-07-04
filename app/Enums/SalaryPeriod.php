<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Maaş aralığının periyodu. */
enum SalaryPeriod: string implements HasLabel
{
    case Saatlik = 'saatlik';
    case Aylik = 'aylik';
    case Yillik = 'yillik';

    public function getLabel(): string
    {
        return match ($this) {
            self::Saatlik => 'Saatlik',
            self::Aylik => 'Aylık',
            self::Yillik => 'Yıllık',
        };
    }

    /** Kısa son ek (örn. "€2.000 / ay"). */
    public function suffix(): string
    {
        return match ($this) {
            self::Saatlik => 'saat',
            self::Aylik => 'ay',
            self::Yillik => 'yıl',
        };
    }
}
