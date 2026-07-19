<?php

namespace App\Enums;

/**
 * Bir satıcının kademeli GÜVEN düzeyi. Admin'in elle açtığı `is_verified`
 * boolean'ının aksine bu tamamen HESAPLANIR ve taklit edilemez sinyallere
 * dayanır (üyelik yaşı, e-posta doğrulaması, aldığı değerlendirmeler, profil
 * eksiksizliği) — dolayısıyla keyfî değildir. Nisoya ödemeye aracılık
 * etmediğinden alıcının en güvenilir koruması, satıcının bu objektif
 * itibarını görüp ödeme kararını ona göre vermesidir (bkz. payment-safety-card).
 *
 * NOT: "gerçek tamamlanmış işlem sayısı" sinyali, ileride bir anlaşma/işlem
 * kaydı (K-C) eklenince bu hesaba dahil edilerek kademeler güçlendirilebilir.
 */
enum TrustTier: string
{
    case Yeni = 'yeni';
    case Uye = 'uye';
    case Guvenilir = 'guvenilir';

    public function label(): string
    {
        return match ($this) {
            self::Yeni => 'Yeni üye',
            self::Uye => 'Aktif üye',
            self::Guvenilir => 'Güvenilir satıcı',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Yeni => '🌱',
            self::Uye => '✓',
            self::Guvenilir => '⭐',
        };
    }

    /** Rozet için Tailwind sınıfları (açık + koyu tema). */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Yeni => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-400/20',
            self::Uye => 'bg-stone-100 text-stone-600 ring-stone-500/20 dark:bg-stone-800 dark:text-stone-300 dark:ring-stone-400/20',
            self::Guvenilir => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-400/20',
        };
    }

    /** Rozetin üstüne gelince görünen açıklama (kullanıcı ne anlama geldiğini bilsin). */
    public function description(): string
    {
        return match ($this) {
            self::Yeni => 'Bu üye platforma yeni katılmış ya da henüz değerlendirilmemiş. Ödeme yaparken ekstra dikkatli ol.',
            self::Uye => 'E-postası doğrulanmış, profili eksiksiz, bir süredir aktif bir üye.',
            self::Guvenilir => 'Uzun süredir aktif, çok sayıda olumlu değerlendirme almış bir satıcı.',
        };
    }

    /** Bu kademe "yeni/riskli" mi? Ödeme uyarısının sertliğini belirler. */
    public function isNew(): bool
    {
        return $this === self::Yeni;
    }
}
