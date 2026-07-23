<?php

namespace App\Jobs;

use App\Models\OutreachTarget;
use App\Services\Growth\EnrichmentRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Tek bir adayın web sitesinden iletişim e-postasını arka planda çıkarır. Panel
 * "İletişim bul" düğmesi aday başına bunu kuyruklar — böylece HTTP isteği anında
 * döner (site çekmek ağ-bağımlı ve yavaş olabilir). GDPR korkuluğu EnrichmentRunner
 * ::enrichOne içinde (gönderim engelli bölge atlanır).
 */
class EnrichTargetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public int $targetId) {}

    public function handle(EnrichmentRunner $runner): void
    {
        $target = OutreachTarget::find($this->targetId);

        if ($target !== null) {
            $runner->enrichOne($target);
        }
    }
}
