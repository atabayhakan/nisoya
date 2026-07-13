<?php

namespace App\Models;

use App\Enums\EventType;
use App\Enums\RsvpStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Davetiye modülünün çekirdek varlığı: üyenin oluşturduğu etkinlik.
 * Davet linki tokenle paylaşılır (WhatsApp), misafirler hesap açmadan
 * LCV verir. Tema alanları bilinçli olarak burada (ayrı Invitation yok).
 *
 * @property EventType $type
 * @property Carbon $starts_at
 */
class Event extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'token',
        'starts_at',
        'venue_name',
        'venue_address',
        'description',
        'theme',
        'is_active',
        'allow_uploads',
        'require_approval',
        'media_purge_warned_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'starts_at' => 'datetime',
            'is_active' => 'boolean',
            'allow_uploads' => 'boolean',
            'require_approval' => 'boolean',
            'media_purge_warned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            $event->token = $event->token ?: self::generateToken();
            $event->theme = $event->theme ?: $event->type->defaultTheme();
        });
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::lower(Str::random(16));
        } while (self::query()->where('token', $token)->exists());

        return $token;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<EventGuest, $this> */
    public function guests(): HasMany
    {
        return $this->hasMany(EventGuest::class)->orderByDesc('id');
    }

    /** @return HasMany<EventMedia, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(EventMedia::class)->orderByDesc('id');
    }

    /** Anı akışı görünür mü: etkinlik günü geldi ve yüklemeler açık. */
    public function streamIsOpen(): bool
    {
        return $this->allow_uploads && $this->starts_at->copy()->startOfDay()->isPast();
    }

    /** LCV sayaçları: durum bazlı davetli satırı + toplam kişi (parti boyutu toplamı). */
    public function rsvpSummary(): array
    {
        $rows = $this->guests()
            ->selectRaw('status, count(*) as entries, sum(party_size) as people')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $get = fn (RsvpStatus $s, string $col) => (int) ($rows[$s->value]->{$col} ?? 0);

        return [
            'geliyor' => ['entries' => $get(RsvpStatus::Geliyor, 'entries'), 'people' => $get(RsvpStatus::Geliyor, 'people')],
            'belki' => ['entries' => $get(RsvpStatus::Belki, 'entries'), 'people' => $get(RsvpStatus::Belki, 'people')],
            'gelmiyor' => ['entries' => $get(RsvpStatus::Gelmiyor, 'entries'), 'people' => $get(RsvpStatus::Gelmiyor, 'people')],
            // Beklenen toplam: kesin gelenler + belki diyenler
            'expected_people' => $get(RsvpStatus::Geliyor, 'people') + $get(RsvpStatus::Belki, 'people'),
        ];
    }

    /** Tema paleti (bilinmeyen anahtar varsayılan temaya düşer). */
    public function themeConfig(): array
    {
        $themes = config('event_themes');

        return $themes[$this->theme] ?? $themes[$this->type->defaultTheme()];
    }

    public function inviteUrl(): string
    {
        return route('davet.show', $this->token);
    }
}
