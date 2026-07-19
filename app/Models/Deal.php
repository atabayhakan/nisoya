<?php

namespace App\Models;

use App\Enums\DealStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İki üye arasındaki bir ANLAŞMA kaydı (sohbet içinden başlar). Nisoya para
 * akışını görmez/işlemez — bu yalnızca "anlaştık / tamamlandı / sorun var"
 * niyetini paylaşan bir defterdir. Tamamlanmış anlaşma, değerlendirmeye
 * "Doğrulanmış işlem" rozeti kazandırır ve sahte değerlendirme halkasını
 * zorlaştırır (bkz. [[nisoya-islem-guvenligi]] K-C).
 */
class Deal extends Model
{
    protected $fillable = [
        'conversation_id',
        'listing_id',
        'seller_id',
        'buyer_id',
        'proposed_by',
        'amount',
        'currency',
        'status',
        'dispute_note',
        'accepted_at',
        'completed_at',
        'cancelled_at',
        'disputed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DealStatus::class,
            'amount' => 'decimal:2',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'disputed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** @return BelongsTo<Listing, $this> */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /** @return BelongsTo<User, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function isParticipant(User $user): bool
    {
        return in_array($user->id, [$this->seller_id, $this->buyer_id], true);
    }

    /** Anlaşmadaki karşı taraf. */
    public function counterpartId(User $user): int
    {
        return $this->seller_id === $user->id ? $this->buyer_id : $this->seller_id;
    }

    public function wasProposedBy(User $user): bool
    {
        return $this->proposed_by === $user->id;
    }
}
