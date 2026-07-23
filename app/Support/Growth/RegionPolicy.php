<?php

namespace App\Support\Growth;

/**
 * Gönderim bölge kapısı. Keşif küreseldir (her ülke saklanabilir) ama TİCARİ
 * E-POSTA yalnızca "allowed" ülkelere gider. Karar iki kattan geçer:
 *  - AB-27 + Türkiye → her zaman engelli (Nisoya yurtdışı Türkleri için; AB'de
 *    GDPR/ePrivacy soğuk e-postayı zorlaştırır),
 *  - config('growth.restricted_countries') → engelli (ör. Rusya, hukuken katı),
 *  - allowlist doluysa ve ülke listede değilse → engelli.
 *
 * Bkz. docs/06-tanitim-agenti-plani.md §1.
 */
final class RegionPolicy
{
    public const ALLOWED = 'allowed';

    public const BLOCKED = 'region_blocked';

    /** AB-27 üye ülke kodları (ISO-2). */
    public const EU = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR',
        'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK',
        'SI', 'ES', 'SE',
    ];

    /** Bir ülke kodu için gönderim durumu: allowed | region_blocked. */
    public static function marketingStatus(?string $country): string
    {
        $c = strtoupper(trim((string) $country));

        if ($c === '') {
            return self::BLOCKED; // ülke bilinmiyorsa güvenli varsayılan: engelle
        }

        if ($c === 'TR' || in_array($c, self::EU, true)) {
            return self::BLOCKED;
        }

        if (in_array($c, (array) config('growth.restricted_countries', []), true)) {
            return self::BLOCKED;
        }

        $allowlist = array_map('strtoupper', (array) config('growth.sending_allowlist', []));
        if ($allowlist !== [] && ! in_array($c, $allowlist, true)) {
            return self::BLOCKED;
        }

        return self::ALLOWED;
    }

    /** Bu ülkeye ticari e-posta gönderilebilir mi? */
    public static function isSendable(?string $country): bool
    {
        return self::marketingStatus($country) === self::ALLOWED;
    }
}
