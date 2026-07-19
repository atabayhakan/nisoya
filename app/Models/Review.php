<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'listing_id',
        'deal_id',
        'reviewee_id',
        'reviewer_id',
        'rating',
        'comment',
        'status',
    ];

    /** Tamamlanmış anlaşmaya dayanıyorsa "Doğrulanmış işlem" (K-C). */
    public function isVerifiedTransaction(): bool
    {
        return $this->deal_id !== null;
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
