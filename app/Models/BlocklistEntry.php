<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dolandırıcılık nedeniyle dondurulan bir hesabın parmak izi (e-posta / IBAN /
 * ödeme handle'ı hash'i). Aynı dolandırıcının yeni bir hesapla ya da aynı
 * ödeme kanalıyla geri dönmesini engeller (bkz. FraudBlocklist servisi).
 * Değerler DÜZ saklanmaz — HMAC ile hash'lenir.
 */
class BlocklistEntry extends Model
{
    protected $table = 'fraud_blocklist';

    protected $fillable = [
        'type',
        'value_hash',
        'reason',
        'blocked_by',
    ];

    /** @return BelongsTo<User, $this> */
    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }
}
