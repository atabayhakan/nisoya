<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PriceUnit: string implements HasLabel
{
    case Saatlik = 'saatlik';
    case Gunluk = 'gunluk';
    case IsBasina = 'is_basina';
    case Paket = 'paket';
    case Adet = 'adet';
    case Gorusulur = 'gorusulur';

    public function getLabel(): string
    {
        return match ($this) {
            self::Saatlik => 'Saatlik',
            self::Gunluk => 'Günlük',
            self::IsBasina => 'İş başına',
            self::Paket => 'Paket',
            self::Adet => 'Adet',
            self::Gorusulur => 'Görüşülür',
        };
    }

    /** İlan tipine uygun fiyat birimleri. */
    public static function forType(string $type): array
    {
        return $type === 'urun'
            ? [self::Adet, self::Paket, self::Gorusulur]
            : [self::Saatlik, self::Gunluk, self::IsBasina, self::Paket, self::Gorusulur];
    }

    /** Fiyat alanında birim eki olarak gösterim (ör. "/saat"). */
    public function suffix(): string
    {
        return match ($this) {
            self::Saatlik => '/saat',
            self::Gunluk => '/gün',
            self::IsBasina => '/iş',
            self::Paket => '/paket',
            self::Adet => '/adet',
            self::Gorusulur => '',
        };
    }
}
