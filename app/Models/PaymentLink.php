<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bir kullanıcının beyan ettiği ödeme yöntemi + isteğe bağlı kendi ödeme
 * linki (Stripe Payment Link, PayPal.me, Venmo profili vb.) veya QR kodu.
 * Nisoya bu linkler/kodlar üzerinden hiçbir para akışını görmez, işlemez
 * veya aracılık etmez — sadece satıcının kendi ödeme sayfasına yönlendirir.
 */
class PaymentLink extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'detail',
        'qr_path',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** detail alanı bir URL mi (tıklanabilir link) yoksa düz bilgi mi (IBAN/telefon)? */
    public function detailIsLink(): bool
    {
        return (bool) $this->detail && str_starts_with($this->detail, 'http');
    }
}
