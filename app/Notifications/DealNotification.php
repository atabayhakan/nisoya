<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Bir anlaşmada karşı tarafa gönderilen site-içi bildirim (teklif geldi /
 * sorun bildirildi). Kabul/tamamlama sohbet açılınca zaten görülür, o yüzden
 * yalnızca dikkat gerektiren olaylar bildirilir.
 */
class DealNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $event, // 'proposed' | 'disputed'
        public string $actorName,
        public string $url,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        [$icon, $title] = match ($this->event) {
            'disputed' => ['⚠️', $this->actorName.' bir anlaşmada sorun bildirdi'],
            default => ['🤝', $this->actorName.' sana bir anlaşma teklif etti'],
        };

        return [
            'icon' => $icon,
            'title' => $title,
            'body' => 'Sohbeti açıp yanıtlayabilirsin.',
            'url' => $this->url,
        ];
    }
}
