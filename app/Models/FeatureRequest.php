<?php

namespace App\Models;

use App\Enums\FeatureRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureRequest extends Model
{
    protected $fillable = [
        'listing_id',
        'user_id',
        'days',
        'status',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => FeatureRequestStatus::class,
            'processed_at' => 'datetime',
            'days' => 'integer',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        // Admin durumu değiştirince ilanı öne çıkar / işle.
        static::updated(function (FeatureRequest $req) {
            if (! $req->wasChanged('status') || $req->processed_at !== null) {
                return;
            }

            if ($req->status === FeatureRequestStatus::Onaylandi) {
                $req->listing?->update([
                    'is_featured' => true,
                    'featured_until' => now()->addDays($req->days),
                ]);
                $req->forceFill(['processed_at' => now()])->saveQuietly();
            } elseif ($req->status === FeatureRequestStatus::Reddedildi) {
                $req->forceFill(['processed_at' => now()])->saveQuietly();
            }
        });
    }
}
