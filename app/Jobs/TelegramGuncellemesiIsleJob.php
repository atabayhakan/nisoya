<?php

namespace App\Jobs;

use App\Services\Kahya\Dis\TelegramDinleyici;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Telegram webhook'undan gelen TEK güncellemeyi arka planda işler — kontrolcü
 * Telegram'a ANINDA 200 dönmeli (aksi halde Telegram isteği tekrar dener ve
 * AI cevabı saniyeler sürebileceğinden webhook zaman aşımına düşer; aynı
 * gerekçe RunDiscoveryJob'da da var: senkron ağ çağrısı nginx'te 504 verir).
 */
class TelegramGuncellemesiIsleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    /** @param  array<string, mixed>  $update */
    public function __construct(public array $update) {}

    public function handle(TelegramDinleyici $dinleyici): void
    {
        $dinleyici->gelenGuncelleme($this->update);
    }
}
