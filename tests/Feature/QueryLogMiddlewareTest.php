<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * QUERY_LOG teşhis aracının bekçisi (2026-07-29).
 *
 * BULUNAN HATA: `QUERY_LOG_ENABLED=true` yapan kişi siteyi kırıyordu.
 * `DB::listen(function ($query) use ($slowThreshold) { ... })` closure'ı
 * yalnız $slowThreshold'u yakalıyordu ama gövdesinde `$request->path()`
 * kullanıyordu. Eşiği aşan İLK sorguda closure çalışıyor ve
 * "Call to a member function path() on null" ile ölümcül hata veriyordu.
 *
 * NEDEN HİÇ FARK EDİLMEDİ: aracı kimse açmamıştı. Varsayılan kapalı
 * olduğu için tüm test paketi bu kod yolunu HİÇ çalıştırmıyordu; PHPStan
 * hatayı görüyordu ama phpstan-baseline.neon'da bastırılmıştı. Yani
 * "performans sorununu teşhis edeyim" diyen kişi üretimi 500'e düşürecekti.
 *
 * Bu testler o kod yolunu bilerek çalıştırır: eşik 0'a çekilir, böylece
 * HER sorgu "yavaş" sayılır ve closure kesinlikle tetiklenir.
 */
class QueryLogMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_log_acikken_sayfa_hala_acilir(): void
    {
        // Eşik 0 → her sorgu yavaş sayılır → closure kesin tetiklenir.
        config(['app.query_log_enabled' => true, 'app.query_log_slow_ms' => 0]);

        $this->get('/')->assertSuccessful();
    }

    /**
     * Yalnız "patlamıyor" yetmez: yavaş sorgu kaydı gerçekten yazılmalı ve
     * içinde istek yolu bulunmalı. Hatanın kaynağı tam olarak o alandı
     * ($request closure'a girmiyordu), dolayısıyla asıl iddia budur.
     */
    public function test_yavas_sorgu_kaydi_istek_yolunu_icerir(): void
    {
        config(['app.query_log_enabled' => true, 'app.query_log_slow_ms' => 0]);

        $yakalanan = [];

        Log::listen(function ($mesaj) use (&$yakalanan) {
            if ($mesaj->message === 'QueryLog: yavaş sorgu') {
                $yakalanan[] = $mesaj->context;
            }
        });

        $this->get('/ilanlar')->assertSuccessful();

        $this->assertNotEmpty($yakalanan, 'Eşik 0 iken en az bir yavaş sorgu kaydı beklenirdi — closure hiç çalışmamış olabilir.');
        $this->assertArrayHasKey('path', $yakalanan[0]);
        $this->assertSame('ilanlar', $yakalanan[0]['path'], 'İstek yolu closure içinde çözülemiyor — $request use listesinden düşmüş olabilir.');
    }

    /**
     * Kapalıyken hiçbir şey yapmamalı (varsayılan üretim davranışı).
     */
    public function test_kapaliyken_kayit_yazmaz(): void
    {
        config(['app.query_log_enabled' => false]);

        $yakalanan = 0;

        Log::listen(function ($mesaj) use (&$yakalanan) {
            if (str_starts_with((string) $mesaj->message, 'QueryLog:')) {
                $yakalanan++;
            }
        });

        $this->get('/')->assertSuccessful();

        $this->assertSame(0, $yakalanan);
    }
}
