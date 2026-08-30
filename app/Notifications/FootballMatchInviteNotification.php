<?php

namespace App\Notifications;

use App\Models\FootballMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FootballMatchInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public FootballMatch $match,
        public string $challengerTeamName,
        public string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $date = $this->match->match_date->translatedFormat('d F H:i');

        return [
            'icon' => '⚽',
            'title' => "Maç Teklifi: {$this->challengerTeamName}",
            'body' => "{$this->challengerTeamName}, {$date} tarihinde takımınla maç yapmak istiyor.",
            'url' => $this->url,
        ];
    }
}
