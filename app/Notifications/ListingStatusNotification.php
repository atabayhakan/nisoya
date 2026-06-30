<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ListingStatusNotification extends Notification
{
    public function __construct(
        public string $listingTitle,
        public string $statusLabel,
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
        return [
            'icon' => '📋',
            'title' => 'İlanının durumu güncellendi',
            'body' => '"'.Str::limit($this->listingTitle, 50).'" → '.$this->statusLabel,
            'url' => $this->url,
        ];
    }
}
