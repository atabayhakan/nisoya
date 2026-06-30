<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Uye = 'uye';
    case Moderator = 'moderator';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Uye => 'Üye',
            self::Moderator => 'Moderatör',
            self::Admin => 'Yönetici',
        };
    }

    /** Yönetim paneline (Filament) erişebilen roller. */
    public function canAccessAdminPanel(): bool
    {
        return in_array($this, [self::Moderator, self::Admin], true);
    }
}
