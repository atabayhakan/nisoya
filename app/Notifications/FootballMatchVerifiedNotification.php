<?php

namespace App\Notifications;

use App\Models\FootballMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FootballMatchVerifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public FootballMatch $match,
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
        $hScore = (int) $this->match->home_score;
        $aScore = (int) $this->match->away_score;

        return [
            'icon' => '🏆',
            'title' => 'Maç Sonucu Doğrulandı!',
            'body' => "{$homeName} {$hScore} - {$aScore} {$awayName} maçı onaylandı ve şehir puan tablosuna işlendi. Spor haberini inceleyin.",
            'url' => $this->url,
        ];
    }
}
