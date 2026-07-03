<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Kullanıcının profilinde "hangi yöntemlerle ödeme kabul ediyorum" diye
 * beyan ettiği seçenekler. Bilgilendirme amaçlıdır — Nisoya bu yöntemler
 * üzerinden hiçbir para transferi işlemez, sadece alıcı/satıcının kendi
 * aralarında anlaşmasını kolaylaştırır (bkz. Faz 0 ödeme rehberi).
 */
enum PaymentMethod: string implements HasLabel
{
    case SepaIban = 'sepa_iban';
    case Kaspi = 'kaspi';
    case Click = 'click';
    case Payme = 'payme';
    case Mbank = 'mbank';
    case Zelle = 'zelle';
    case Venmo = 'venmo';
    case PayPal = 'paypal';
    case Nakit = 'nakit';
    case Diger = 'diger';

    public function getLabel(): string
    {
        return match ($this) {
            self::SepaIban => 'Banka Havalesi (IBAN)',
            self::Kaspi => 'Kaspi',
            self::Click => 'Click',
            self::Payme => 'Payme',
            self::Mbank => 'MBANK',
            self::Zelle => 'Zelle',
            self::Venmo => 'Venmo',
            self::PayPal => 'PayPal',
            self::Nakit => 'Nakit (elden)',
            self::Diger => 'Diğer',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SepaIban => '🏦',
            self::Kaspi => '📱',
            self::Click => '📱',
            self::Payme => '📱',
            self::Mbank => '📱',
            self::Zelle => '💸',
            self::Venmo => '💸',
            self::PayPal => '💳',
            self::Nakit => '💵',
            self::Diger => '➕',
        };
    }

    /**
     * Kullanıcının ülkesine göre önerilen ödeme yöntemleri (profil formunda
     * öne çıkarmak için). Bölgesel araştırmaya dayanır: Kazakistan'da Kaspi
     * nakit-dışı işlemlerin çoğunu, Kırgızistan'da MBANK yetişkin nüfusun
     * büyük kısmını, Özbekistan'da Click/Payme'yi kapsıyor; Avrupa'da SEPA
     * IBAN ortak rayı, ABD'de Zelle/Venmo/PayPal yaygın. Diğer ülkeler için
     * doğrulanmamış varsayım kurmamak adına genel seçeneklere düşülür.
     *
     * @return list<self>
     */
    public static function suggestedFor(?string $countryCode): array
    {
        return match ($countryCode) {
            'KZ' => [self::Kaspi, self::SepaIban, self::Nakit],
            'KG' => [self::Mbank, self::SepaIban, self::Nakit],
            'UZ' => [self::Click, self::Payme, self::Nakit],
            'US' => [self::Zelle, self::Venmo, self::PayPal],
            'CA', 'AU', 'GB' => [self::PayPal, self::SepaIban, self::Nakit],
            'DE', 'NL', 'FR', 'AT', 'BE', 'CH', 'SE', 'NO', 'DK', 'IT', 'ES', 'PL' => [self::SepaIban, self::PayPal, self::Nakit],
            default => [self::SepaIban, self::PayPal, self::Nakit],
        };
    }
}
