<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum FootballLevel: string implements HasColor, HasIcon, HasLabel
{
    case Baslangic = 'baslangic';
    case Orta = 'orta';
    case Iyi = 'iyi';
    case Ileri = 'ileri';

    public function getLabel(): string
    {
        return match ($this) {
            self::Baslangic => 'Başlangıç',
            self::Orta => 'Orta',
            self::Iyi => 'İyi',
            self::Ileri => 'İleri',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Baslangic => 'success',
            self::Orta => 'info',
            self::Iyi => 'warning',
            self::Ileri => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Baslangic => 'heroicon-m-sparkles',
            self::Orta => 'heroicon-m-bolt',
            self::Iyi => 'heroicon-m-fire',
            self::Ileri => 'heroicon-m-trophy',
        };
    }

    public function badgeEmoji(): string
    {
        return match ($this) {
            self::Baslangic => '🟢',
            self::Orta => '🔵',
            self::Iyi => '🟣',
            self::Ileri => '🔴',
        };
    }
}
