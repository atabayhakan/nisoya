<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EventType: string implements HasLabel
{
    case Dugun = 'dugun';
    case Nisan = 'nisan';
    case Kina = 'kina';
    case Sunnet = 'sunnet';
    case BabyShower = 'bebek';
    case DogumGunu = 'dogum_gunu';
    case Iftar = 'iftar';
    case Mevlid = 'mevlid';
    case Vaftiz = 'vaftiz';
    case Mezuniyet = 'mezuniyet';
    case Diger = 'diger';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dugun => 'Düğün',
            self::Nisan => 'Nişan',
            self::Kina => 'Kına Gecesi',
            self::Sunnet => 'Sünnet',
            self::BabyShower => 'Doğum / Baby Shower',
            self::DogumGunu => 'Doğum Günü',
            self::Iftar => 'İftar Daveti',
            self::Mevlid => 'Mevlid',
            self::Vaftiz => 'Vaftiz & Özel Gün',
            self::Mezuniyet => 'Mezuniyet',
            self::Diger => 'Diğer',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Dugun => '💍',
            self::Nisan => '💐',
            self::Kina => '🌙',
            self::Sunnet => '🎠',
            self::BabyShower => '🍼',
            self::DogumGunu => '🎂',
            self::Iftar => '🌙',
            self::Mevlid => '🕌',
            self::Vaftiz => '🕊️',
            self::Mezuniyet => '🎓',
            self::Diger => '🎉',
        };
    }

    /** Tür için varsayılan tema anahtarı (bkz. config/event_themes.php). */
    public function defaultTheme(): string
    {
        return match ($this) {
            self::Dugun, self::Nisan => 'zarif',
            self::Kina, self::Iftar, self::Mevlid => 'gece',
            self::Sunnet, self::DogumGunu, self::BabyShower => 'kutlama',
            self::Vaftiz, self::Mezuniyet, self::Diger => 'dogal',
        };
    }
}
