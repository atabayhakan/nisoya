<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\BlocklistEntry;
use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Dolandırıcılık parmak izi kara listesi (K-D). Nisoya ödemeye aracılık
 * etmediğinden dolandırıcıyı işlem anında durduramayız; ama dondurulduğunda
 * en KIT kaynaklarını (gerçek banka hesabı/ödeme kanalı ve e-postası)
 * kaydederiz, böylece aynı kişi yeni bir hesapla ya da aynı IBAN/handle ile
 * geri dönemez.
 *
 * Değerler DÜZ saklanmaz: normalize edilip HMAC-SHA256 (APP_KEY ile) ile
 * hash'lenir — exact-match için yeterli, GDPR için daha temiz.
 */
class FraudBlocklist
{
    public const TYPE_EMAIL = 'email';

    public const TYPE_IBAN = 'iban';

    public const TYPE_HANDLE = 'payment_handle';

    public const TYPE_IP = 'ip';

    /** Karşılaştırma öncesi değeri türüne göre normalize et. */
    public function normalize(string $type, string $value): string
    {
        $value = trim($value);

        return match ($type) {
            self::TYPE_EMAIL, self::TYPE_HANDLE => Str::lower($value),
            self::TYPE_IBAN => Str::upper((string) preg_replace('/\s+/', '', $value)),
            default => $value,
        };
    }

    public function hash(string $type, string $value): string
    {
        return hash_hmac('sha256', $type.':'.$this->normalize($type, $value), (string) config('app.key'));
    }

    public function isBlocked(string $type, string $value): bool
    {
        if (trim($value) === '') {
            return false;
        }

        return BlocklistEntry::query()
            ->where('type', $type)
            ->where('value_hash', $this->hash($type, $value))
            ->exists();
    }

    /** Bir değeri kara listeye al (idempotent). */
    public function block(string $type, string $value, ?int $blockedBy = null, ?string $reason = null): void
    {
        if (trim($value) === '') {
            return;
        }

        BlocklistEntry::firstOrCreate(
            ['type' => $type, 'value_hash' => $this->hash($type, $value)],
            ['blocked_by' => $blockedBy, 'reason' => $reason],
        );
    }

    /**
     * Bir kullanıcının kimlik + ödeme parmak izini kara listeye al (dolandırıcı
     * dondurulunca çağrılır). Yalnızca metinsel parmak izi olan ödeme linkleri
     * alınır — sadece-QR linklerin metinsel imzası yoktur, atlanır.
     *
     * @return int kaydedilen parmak izi sayısı
     */
    public function fingerprintUser(User $user, ?int $blockedBy = null, ?string $reason = null): int
    {
        $count = 0;

        if (filled($user->email)) {
            $this->block(self::TYPE_EMAIL, $user->email, $blockedBy, $reason);
            $count++;
        }

        // Statik sorgu kullanılır (typed Collection<PaymentLink>); ilişki
        // magic-property'si üzerinden erişim larastan'da tipsiz kalıyor.
        foreach (PaymentLink::query()->where('user_id', $user->id)->get() as $link) {
            if (! filled($link->detail)) {
                continue;
            }

            $type = $link->method === PaymentMethod::SepaIban ? self::TYPE_IBAN : self::TYPE_HANDLE;
            $this->block($type, $link->detail, $blockedBy, $reason);
            $count++;
        }

        return $count;
    }
}
