<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FootballMemberStatus: string implements HasColor, HasLabel
{
    case DavetEdildi = 'davet_edildi';
    case Basvurdu = 'basvurdu';
    case Aktif = 'aktif';
    case Ayrildi = 'ayrildi';
    case Reddedildi = 'reddedildi';

    public function getLabel(): string
    {
        return match ($this) {
            self::DavetEdildi => 'Davet Gönderildi',
            self::Basvurdu => 'Katılma Talebi',
            self::Aktif => 'Kadroda (Aktif)',
            self::Ayrildi => 'Ayrıldı',
            self::Reddedildi => 'Reddedildi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DavetEdildi, self::Basvurdu => 'warning',
            self::Aktif => 'success',
            self::Ayrildi => 'gray',
            self::Reddedildi => 'danger',
        };
    }
}
