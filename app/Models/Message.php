<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    public const TYPE_TEXT = 'text';

    public const TYPE_IMAGE = 'image';

    public const TYPE_LOCATION = 'location';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'body',
        'attachment_path',
        'read_at',
        'recalled_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'recalled_at' => 'datetime',
        ];
    }

    /** Mesaj gönderen tarafından geri alınmış mı? */
    public function isRecalled(): bool
    {
        return $this->recalled_at !== null;
    }

    public function isImage(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    public function isLocation(): bool
    {
        return $this->type === self::TYPE_LOCATION;
    }

    /** Arayüzde gösterilecek gövde — geri alınmışsa/konumsa gerçek içerik gizlenir. */
    public function displayBody(): string
    {
        if ($this->isRecalled() || $this->isLocation()) {
            return '';
        }

        return (string) $this->body;
    }

    /** İşlenmiş görsel eklinin public URL'i (yoksa null). */
    public function imageUrl(): ?string
    {
        if (! $this->isImage() || $this->isRecalled() || ! $this->attachment_path) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }

    /**
     * Konum mesajının koordinatları (body="lat,lng"). Geçersizse null.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function coords(): ?array
    {
        if (! $this->isLocation() || $this->isRecalled()) {
            return null;
        }

        [$lat, $lng] = array_pad(explode(',', (string) $this->body, 2), 2, null);

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return ['lat' => (float) $lat, 'lng' => (float) $lng];
    }

    /**
     * Sohbet arayüzü (Blade + JS poll) için tek biçim. store() ve stream()
     * aynı çıktıyı üretsin diye tek yerde toplandı.
     *
     * @return array<string, mixed>
     */
    public function toChatArray(int $me): array
    {
        $coords = $this->coords();

        return [
            'id' => $this->id,
            'type' => $this->type,
            'mine' => $this->sender_id === $me,
            'recalled' => $this->isRecalled(),
            'time' => $this->created_at->format('d.m H:i'),
            'body' => $this->displayBody(),
            'image' => $this->imageUrl(),
            'lat' => $coords['lat'] ?? null,
            'lng' => $coords['lng'] ?? null,
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
