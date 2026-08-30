<?php

namespace App\Notifications;

use App\Models\FootballMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FootballMatchResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public FootballMatch $match,
        public string $submittedByTeamName,
        public int $homeScore,
        public int $awayScore,
        public string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $homeName = $this->match->homeTeam?->name ?: 'Ev Sahibi';
        $awayName = $this->match->awayTeam?->name ?: 'Deplasman';

        return [
            'icon' => '⚽',
            'title' => 'Maç Skoru Onayınızı Bekliyor',
            'body' => "{$this->submittedByTeamName} maç sonucunu bildirdi: {$homeName} {$this->homeScore} - {$this->awayScore} {$awayName}. Onaylayabilir veya itiraz edebilirsiniz.",
            'url' => $this->url,
        ];
    }
}
