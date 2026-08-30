<?php

namespace App\Notifications;

use App\Models\FootballTeam;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FootballTeamInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public FootballTeam $team,
        public string $inviterName,
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
            'title' => "{$this->team->name} takımına davet edildin!",
            'body' => "{$this->inviterName}, seni {$this->team->city} takımının kadrosuna davet etti.",
            'url' => $this->url,
        ];
    }
}
