<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PriceUnit: string implements HasLabel
{
    case Saatlik = 'saatlik';
    case Gunluk = 'gunluk';
    case Kilogram = 'kilogram';
    case IsBasina = 'is_basina';
    case Paket = 'paket';
    case Adet = 'adet';
    case Aylik = 'aylik';
    case Gecelik = 'gecelik';
    case Haftalik = 'haftalik';
    case Toplam = 'toplam';
    case Gorusulur = 'gorusulur';

    public function getLabel(): string
    {
        return match ($this) {
            self::Saatlik => 'Saatlik',
            self::Gunluk => 'Günlük',
            self::Kilogram => 'Kilogram',
            self::IsBasina => 'İş başına',
            self::Paket => 'Paket',
            self::Adet => 'Adet',
            self::Aylik => 'Aylık',
            self::Gecelik => 'Gecelik',
            self::Haftalik => 'Haftalık',
            self::Toplam => 'Toplam (satış)',
            self::Gorusulur => 'Görüşülür',
        };
    }

    /** İlan tipine uygun fiyat birimleri. */
    public static function forType(string $type): array
    {
        return match ($type) {
            'urun' => [self::Adet, self::Paket, self::Gorusulur],
            'emlak' => [self::Aylik, self::Gecelik, self::Toplam, self::Gorusulur],
            'vasita' => [self::Toplam, self::Gunluk, self::Haftalik, self::Gorusulur],
            default => [self::Saatlik, self::Gunluk, self::Kilogram, self::IsBasina, self::Paket, self::Gorusulur],
        };
    }

    /** Fiyat alanında birim eki olarak gösterim (ör. "/saat"). */
    public function suffix(): string
    {
        return match ($this) {
            self::Saatlik => '/saat',
            self::Gunluk => '/gün',
            self::Kilogram => '/kg',
            self::IsBasina => '/iş',
            self::Paket => '/paket',
            self::Adet => '/adet',
            self::Aylik => '/ay',
            self::Gecelik => '/gece',
            self::Haftalik => '/hafta',
            self::Toplam => '',
            self::Gorusulur => '',
        };
    }
}
