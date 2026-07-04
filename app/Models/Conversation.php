<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'listing_id',
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /** İki kullanıcı (+ilan) arasındaki konuşmayı bul veya oluştur. */
    public static function findOrCreateBetween(int $userId, int $otherId, ?int $listingId): self
    {
        $existing = static::query()
            ->where('listing_id', $listingId)
            ->where(function ($q) use ($userId, $otherId) {
                $q->where(['user_one_id' => $userId, 'user_two_id' => $otherId])
                    ->orWhere(['user_one_id' => $otherId, 'user_two_id' => $userId]);
            })
            ->first();

        return $existing ?? static::create([
            'listing_id' => $listingId,
            'user_one_id' => $userId,
            'user_two_id' => $otherId,
            'last_message_at' => now(),
        ]);
    }

    /** İki kullanıcı arasında (hangi ilan için olursa olsun) daha önce mesajlaşma olmuş mu? */
    public static function existsBetween(int $userId, int $otherId): bool
    {
        return static::query()
            ->where(function ($q) use ($userId, $otherId) {
                $q->where(['user_one_id' => $userId, 'user_two_id' => $otherId])
                    ->orWhere(['user_one_id' => $otherId, 'user_two_id' => $userId]);
            })
            ->exists();
    }

    public function isParticipant(User $user): bool
    {
        return in_array($user->id, [$this->user_one_id, $this->user_two_id], true);
    }

    /** Konuşmadaki karşı taraf. */
    public function other(User $user): ?User
    {
        $otherId = $this->user_one_id === $user->id ? $this->user_two_id : $this->user_one_id;

        return User::find($otherId);
    }
}
