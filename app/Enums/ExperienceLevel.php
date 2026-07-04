<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** İş ilanı için aranan deneyim seviyesi. */
enum ExperienceLevel: string implements HasLabel
{
    case Giris = 'giris';
    case Orta = 'orta';
    case Kidemli = 'kidemli';
    case Yonetici = 'yonetici';

    public function getLabel(): string
    {
        return match ($this) {
            self::Giris => 'Giriş seviyesi',
            self::Orta => 'Orta seviye',
            self::Kidemli => 'Kıdemli',
            self::Yonetici => 'Yönetici',
        };
    }
}
