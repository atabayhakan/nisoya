<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Support\Settings;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\SahteAiSaglayici;
use Tests\TestCase;

/**
 * Anasayfa "Nisoya AI ile ara" — HTTP uç + anasayfa yüzeyi.
 * docs/plans/2026-08-19-…, madde C · docs/03-buyume-fikirleri.md KARAR: YAP.
 */
class NisoyaAiAramaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
        config(['ai.features.nisoya_ai_arama' => true]);
    }

    private function sahteBagla(?array $yanit): void
    {
        $this->app->instance(AiProvider::class, new SahteAiSaglayici($yanit));
    }

    public function test_kisa_sorgu_ai_cagirmadan_belirsiz_doner(): void
    {
        $this->sahteBagla(['niyet' => 'rehber', 'ulke_kodu' => null, 'islem_turu_slug' => null, 'anahtar_kelimeler' => []]);

        $this->getJson('/arama/ai?q=pasaport')
            ->assertOk()
            ->assertJson(['niyet' => 'belirsiz', 'sonuclar' => [], 'ilanBaglantisi' => null, 'aktif' => true]);
    }

    public function test_rehber_sonucu_json_olarak_doner(): void
    {
        $tur = IslemTuru::query()->create(['ad' => 'Vekaletname', 'slug' => 'vekaletname', 'is_active' => true]);
        $temsilcilik = Temsilcilik::query()->create([
            'country_code' => 'DE', 'ad' => 'Köln Başkonsolosluğu', 'slug' => 'koeln',
            'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Köln', 'is_active' => true,
        ]);
        TemsilcilikIslemi::query()->create([
            'temsilcilik_id' => $temsilcilik->id, 'islem_turu_id' => $tur->id,
            'evraklar' => [], 'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
            'status' => TemsilcilikIslemi::STATUS_YAYIN,
        ]);

        $this->sahteBagla(['niyet' => 'rehber', 'ulke_kodu' => 'DE', 'islem_turu_slug' => 'vekaletname', 'anahtar_kelimeler' => ['vekaletname']]);

        $yanit = $this->getJson('/arama/ai?q=Almanyada+vekaletname+nasıl+alınır')->assertOk()->json();

        $this->assertSame('rehber', $yanit['niyet']);
        $this->assertCount(1, $yanit['sonuclar']);
        $this->assertSame('Vekaletname', $yanit['sonuclar'][0]['baslik']);
    }

    public function test_ozellik_kapaliyken_aktif_false_doner(): void
    {
        config(['ai.features.nisoya_ai_arama' => false]);
        $this->sahteBagla(['niyet' => 'rehber', 'ulke_kodu' => null, 'islem_turu_slug' => null, 'anahtar_kelimeler' => []]);

        $this->getJson('/arama/ai?q=pasaportum+kayboldu')
            ->assertOk()
            ->assertJson(['aktif' => false]);
    }

    public function test_throttle_limiter_tanimli(): void
    {
        $this->assertNotNull(app(RateLimiter::class)->limiter('nisoya-ai-arama'));
    }

    // ------------------------------------------------------- Anasayfa yüzeyi

    public function test_anasayfada_cubuk_gorunur_saglayici_yapilandirilmisken(): void
    {
        Cache::flush();
        $this->sahteBagla(['niyet' => 'rehber', 'ulke_kodu' => null, 'islem_turu_slug' => null, 'anahtar_kelimeler' => []]);

        $this->get('/')->assertOk()->assertSee('Nisoya AI', false);
    }

    public function test_ozellik_kapaliyken_cubuk_hic_basilmaz(): void
    {
        Cache::flush();
        config(['ai.features.nisoya_ai_arama' => false]);
        $this->sahteBagla(['niyet' => 'rehber', 'ulke_kodu' => null, 'islem_turu_slug' => null, 'anahtar_kelimeler' => []]);

        $this->get('/')->assertOk()->assertDontSee("Nisoya AI'ya sor", false);
    }

    public function test_vitrin_temasinda_da_cubuk_gorunur(): void
    {
        Cache::flush();
        Settings::setMany(['gorunum.tema' => 'vitrin']);
        $this->sahteBagla(['niyet' => 'rehber', 'ulke_kodu' => null, 'islem_turu_slug' => null, 'anahtar_kelimeler' => []]);

        $this->get('/')->assertOk()->assertSee("Nisoya AI'ya sor", false);
    }

    public function test_acik_ulke_parametresi_onceliklidir(): void
    {
        $biskek = Temsilcilik::query()->create([
            'country_code' => 'KG', 'ad' => 'Bişkek Büyükelçiliği', 'slug' => 'biskek',
            'tur' => Temsilcilik::TUR_BUYUKELCILIK, 'sehir' => 'Bişkek', 'is_active' => true,
        ]);

        $this->sahteBagla(['niyet' => 'rehber', 'ulke_kodu' => null, 'islem_turu_slug' => 'pasaport', 'anahtar_kelimeler' => ['pasaport']]);

        $yanit = $this->getJson('/arama/ai?q=pasaportum+kayboldu&ulke=KG')->assertOk()->json();

        $this->assertSame('rehber', $yanit['niyet']);
        $this->assertCount(1, $yanit['sonuclar']);
        $this->assertSame('Bişkek Büyükelçiliği', $yanit['sonuclar'][0]['baslik']);
    }
}
