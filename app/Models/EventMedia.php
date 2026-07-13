<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Anı akışı medyası. Limitler 50GB'lık VPS diskini korur (bkz. tasarım
 * belgesi Bölüm 5) — dolduğunda tek config değişikliğiyle nesne
 * depolamaya (R2) geçilebilsin diye tüm dosya işlemleri DISK sabiti
 * üzerinden yapılır.
 */
class EventMedia extends Model
{
    /** Medya dosyalarının yaşadığı Laravel disk'i (ileride R2'ye geçiş noktası). */
    public const DISK = 'public';

    public const MAX_PHOTOS_PER_EVENT = 300;

    public const MAX_VIDEOS_PER_EVENT = 20;

    public const MAX_TOTAL_BYTES_PER_EVENT = 2 * 1024 * 1024 * 1024; // 2 GB

    public const MAX_VIDEO_BYTES = 100 * 1024 * 1024; // 100 MB

    public const MAX_IMAGE_UPLOAD_KB = 8192; // 8 MB (işlenince WebP olarak küçülür)

    /** Tarayıcıda doğrudan oynayan konteynerler — sunucuda dönüştürme YOK. */
    public const VIDEO_MIMES = ['video/mp4', 'video/webm'];

    protected $table = 'event_media';

    protected $fillable = [
        'event_id',
        'event_guest_id',
        'type',
        'status',
        'path',
        'path_thumb',
        'path_medium',
        'path_large',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<EventGuest, $this> */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(EventGuest::class, 'event_guest_id');
    }

    /** Yükleyen adı (misafir yoksa ev sahibi). */
    public function uploaderName(): string
    {
        return $this->guest->name ?? 'Ev sahibi';
    }

    /** Medya dosyalarını diskten sil (kayıt silinmeden önce çağrılır). */
    public function deleteFiles(): void
    {
        foreach ([$this->path, $this->path_thumb, $this->path_medium, $this->path_large] as $path) {
            if ($path) {
                Storage::disk(self::DISK)->delete($path);
            }
        }
    }

    protected static function booted(): void
    {
        static::deleting(fn (self $media) => $media->deleteFiles());
    }
}
