<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\EventMedia;
use App\Services\ImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Anı akışı fotoğrafını arka planda işler — ProcessListingImage ile aynı
 * gizlilik sözleşmesi: ham (EXIF/GPS'i temizlenmemiş) dosya ASLA public
 * diske yazılmaz; işleme başarısız olursa kayıt oluşturulmaz.
 */
class ProcessEventImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public int $eventId,
        public ?int $eventGuestId,
        public string $rawPath,
        public string $rawDisk,
        public string $status, // yayinda | beklemede
    ) {}

    public function handle(ImageService $imageService): void
    {
        $event = Event::find($this->eventId);

        if (! $event) {
            Storage::disk($this->rawDisk)->delete($this->rawPath);

            return;
        }

        try {
            $realPath = Storage::disk($this->rawDisk)->path($this->rawPath);
            $result = $imageService->storeOptimizedFromPath($realPath, 'event-media/'.$event->id);
        } catch (\Throwable $e) {
            Log::error('Etkinlik fotoğrafı kuyrukta işlenemedi', [
                'event_id' => $this->eventId,
                'exception' => $e->getMessage(),
            ]);
            Storage::disk($this->rawDisk)->delete($this->rawPath);

            return;
        }

        $size = 0;
        foreach (['thumb', 'medium', 'large'] as $variant) {
            try {
                $size += Storage::disk(EventMedia::DISK)->size($result[$variant]);
            } catch (\Throwable) {
                // boyut okunamazsa 0 say — kota muhasebesi yaklaşık kalır
            }
        }

        $event->media()->create([
            'event_guest_id' => $this->eventGuestId,
            'type' => 'image',
            'status' => $this->status,
            'path_thumb' => $result['thumb'],
            'path_medium' => $result['medium'],
            'path_large' => $result['large'],
            'size_bytes' => $size,
        ]);

        Storage::disk($this->rawDisk)->delete($this->rawPath);
    }

    /** Tüm denemeler tükendiğinde ham dosyayı diskte bırakma. */
    public function failed(\Throwable $e): void
    {
        Storage::disk($this->rawDisk)->delete($this->rawPath);
    }
}
