<?php

namespace App\Jobs;

use App\Services\Growth\DiscoveryRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Tek bir şehir × meslek kombinasyonu için keşfi arka planda çalıştırır. Panel
 * "Keşfet" düğmesi bunu kombo başına kuyruklar — böylece HTTP isteği anında
 * döner (gerçek kaynakla senkron keşif nginx'te 504 veriyordu). Her iş kısadır
 * (birkaç ağ çağrısı), 'database' bağlantısına zorlanır ve scheduler'ın her
 * dakika çalıştırdığı queue:work ile işlenir (ayrı worker/supervisor gerekmez).
 */
class RunDiscoveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /** retry_after (90s) altında kalmalı — aksi halde iş bitmeden tekrar denenir. */
    public int $timeout = 80;

    /**
     * @param  array{key: string, tr: string, en: string, osm?: string, local?: string}  $trade
     */
    public function __construct(
        public string $country,
        public string $city,
        public array $trade,
        public bool $useLlm = false,
    ) {}

    public function handle(DiscoveryRunner $runner): void
    {
        $runner->runForCityTrade($this->country, $this->city, $this->trade, 20, $this->useLlm);
    }
}
