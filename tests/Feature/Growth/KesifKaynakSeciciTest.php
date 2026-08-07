<?php

namespace Tests\Feature\Growth;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `growth:discover --source=` — tek koşu için kaynak seçimi (2026-08-08).
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR — CANLI TARAMADA BULUNDU
 *
 * `GROWTH_SOURCE=overpass php artisan growth:discover DE` çalıştırıldı ve
 * çıktı "Kaynak: google_places" dedi. Ortam değişkeni İKİ KEZ kaybediyor:
 *
 *   1. `config/growth.php` kaynağı `env()` ile okuyor; üretimde config
 *      CACHE'Lİ, yani `env()` çalışma anını değil cache anını yansıtır.
 *   2. `AppServiceProvider::mergeGrowthConfig()` panel ayarını (DB) config'in
 *      üzerine yazıyor — DB > env, bu bilinçli ve doğru.
 *
 * Sonuç: ortam değişkeniyle tek seferlik kaynak seçmenin yolu YOKTU. Komut
 * sessizce panelde seçili kaynağı kullanıyor, çalıştıran ise başka bir şey
 * yaptığını sanıyordu — ücretsiz Overpass yerine kotalı Places harcandı.
 *
 * Açık bayrak doğru katman: uygulama için DB > env kalır, TEK KOŞU için komut
 * satırı ikisini de geçer.
 */
class KesifKaynakSeciciTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_bayragi_panel_ayarini_gecer(): void
    {
        /*
         * EN ÖNEMLİ TEST. Panelde 'google' seçili olsa bile `--source=fixture`
         * o koşuda kazanmalı; kazanmazsa bayrak sessiz bir süstür.
         */
        Settings::setMany(['growth.source' => 'google']);
        config(['growth.source' => 'google']);

        $this->artisan('growth:discover', ['country' => 'US', '--trades' => 1, '--source' => 'fixture'])
            ->expectsOutputToContain('fixture')
            ->assertSuccessful();
    }

    public function test_bayrak_verilmezse_yapilandirilmis_kaynak_kullanilir(): void
    {
        // Geriye dönük uyum: bayrak yoksa hiçbir şey değişmemeli.
        config(['growth.source' => 'fixture']);

        $this->artisan('growth:discover', ['country' => 'US', '--trades' => 1])
            ->expectsOutputToContain('fixture')
            ->assertSuccessful();
    }

    public function test_bilinmeyen_kaynak_reddedilir(): void
    {
        /*
         * Sessizce yok saymak en kötüsü olurdu: yazım hatası yapan kişi
         * ("--source=overpas") istediğinden başka bir kaynakla tarama yapıp
         * sonucu doğru sanardı. Tam olarak bu hatanın büyük hâli bu dosyanın
         * var olma sebebi.
         */
        config(['growth.source' => 'fixture']);

        $this->artisan('growth:discover', ['country' => 'US', '--source' => 'overpas'])
            ->expectsOutputToContain('Bilinmeyen kaynak')
            ->assertFailed();
    }

    public function test_overpass_secilince_gercekten_overpass_kosar(): void
    {
        // Ağ kapalı: Overpass'a çıkmaya çalışması bile kaynağın değiştiğinin
        // kanıtı (fixture ağ kullanmaz).
        Http::fake(['overpass-api.de/*' => Http::response('{"elements":[]}')]);
        config(['growth.source' => 'fixture']);

        $this->artisan('growth:discover', ['country' => 'DE', '--trades' => 1, '--source' => 'overpass'])
            ->expectsOutputToContain('openstreetmap')
            ->assertSuccessful();
    }
}
