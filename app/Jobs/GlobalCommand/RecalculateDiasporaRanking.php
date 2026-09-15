<?php

namespace App\Jobs\GlobalCommand;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Diaspora\DiasporaRankingEngine;
use App\Support\GlobalCommand\GeoContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class RecalculateDiasporaRanking implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public int $actorId, public array $selection) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping('diaspora-ranking'))->releaseAfter(30)->expireAfter(150)];
    }

    public function handle(DiasporaRankingEngine $engine): void
    {
        $actor = User::find($this->actorId);
        if (! $actor?->isAdmin() || $actor->status !== UserStatus::Aktif) {
            return;
        }
        $engine->recalculateAndRank(GeoContext::fromSelection($this->selection));
    }
}
