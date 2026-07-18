<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $reviewerName,
        public int $rating,
        public string $profileUrl,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => '⭐',
            'title' => $this->reviewerName.' seni değerlendirdi',
            'body' => $this->rating.' yıldız verdi',
            'url' => $this->profileUrl,
        ];
    }
}
