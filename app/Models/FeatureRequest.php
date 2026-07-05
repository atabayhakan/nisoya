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

    /** @return BelongsTo<Listing, $this> */
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
                $newUntil = now()->addDays($req->days);
                $currentUntil = $req->listing?->featured_until;

                $req->listing?->update([
                    'is_featured' => true,
                    // Zaten öne çıkan bir ilan için onaylanan daha kısa bir talep
                    // mevcut süreyi kısaltmasın — ikisinin daha uzun olanı geçerli olur.
                    'featured_until' => ($currentUntil && $currentUntil->isAfter($newUntil)) ? $currentUntil : $newUntil,
                ]);
                $req->forceFill(['processed_at' => now()])->saveQuietly();
            } elseif ($req->status === FeatureRequestStatus::Reddedildi) {
                $req->forceFill(['processed_at' => now()])->saveQuietly();
            }
        });
    }
}
