<?php

namespace App\Notifications;

use App\Models\FootballPlayerRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FootballPlayerAppliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public FootballPlayerRequest $request,
        public string $applicantName,
        public string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'icon' => '⚽',
            'title' => 'İlanınıza Başvuru Geldi',
            'body' => "{$this->applicantName}, '{$this->request->city}' futbol ilanınıza katılmak istediğini belirtti.",
            'url' => $this->url,
        ];
    }
}
