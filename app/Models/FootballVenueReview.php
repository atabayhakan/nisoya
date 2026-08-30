<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ReviewStatus $status
 * @property-read FootballVenue|null $venue
 * @property-read User|null $user
 */
class FootballVenueReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'user_id',
        'rating',
        'sub_ratings',
        'comment',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'sub_ratings' => 'array',
            'status' => ReviewStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $review) {
            $review->venue?->recalculateRating();
        });

        static::deleted(function (self $review) {
            $review->venue?->recalculateRating();
        });
    }

    /** @return BelongsTo<FootballVenue, $this> */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(FootballVenue::class, 'venue_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
