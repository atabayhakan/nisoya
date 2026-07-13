<?php

namespace App\Models;

use App\Enums\RsvpStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Davetlinin LCV kaydı. Misafir hesap açmaz — kendi kaydını, LCV verirken
 * çereze yazılan misafir token'ıyla günceller. D2'de anı akışı yüklemeleri
 * de bu token'a bağlanacak.
 */
class EventGuest extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'status',
        'party_size',
        'note',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'status' => RsvpStatus::class,
            'party_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $guest) {
            $guest->token = $guest->token ?: Str::lower(Str::random(16));
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
