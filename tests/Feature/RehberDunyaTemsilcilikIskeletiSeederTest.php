<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Temsilcilik;
use App\Services\RehberDogalDilArama;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RehberDunyaTemsilcilikIskeletiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dünya geneli temsilcilik iskeleti (docs/03-buyume-fikirleri.md, öneri 1).
 * 57 aktif ülkenin temsilcilik kaydı hiç yoktu — Rehber ülke sayfası
 * "hazırlanıyor" diyordu ve Nisoya AI arama önerecek hiçbir şey bulamıyordu.
 */
class RehberDunyaTemsilcilikIskeletiSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_55_ulkeye_temsilcilik_olusturur(): void
    {
        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);

        $this->assertSame(55, Temsilcilik::query()->count());
    }

    public function test_tekrar_kosu_ikinci_kayit_olusturmaz(): void
    {
        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);
        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);

        $this->assertSame(55, Temsilcilik::query()->count());
    }

    public function test_kibris_ve_kktc_bilerek_disarida(): void
    {
        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);

        $this->assertSame(0, Temsilcilik::query()->where('country_code', 'CY')->count());
        $this->assertSame(0, Temsilcilik::query()->where('country_code', 'XN')->count());
    }

    public function test_gercek_sehirler_dogru_atandi(): void
    {
        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);

        $this->assertSame('Lahey', Temsilcilik::query()->where('country_code', 'NL')->value('sehir'));
        $this->assertSame('Canberra', Temsilcilik::query()->where('country_code', 'AU')->value('sehir'));
        $this->assertSame('Wellington', Temsilcilik::query()->where('country_code', 'NZ')->value('sehir'));
    }

    public function test_akredite_ulkeler_kendi_buyukelciligi_yok_der(): void
    {
        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);

        $li = Temsilcilik::query()->where('country_code', 'LI')->first();

        $this->assertSame('Bern', $li->sehir);
        $this->assertStringContainsString('akredite', $li->ad);
    }

    public function test_country_tablosunda_olmayan_kod_sessizce_atlanir(): void
    {
        Country::query()->where('code', 'AU')->delete();

        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);

        $this->assertSame(54, Temsilcilik::query()->count());
    }

    /**
     * Gerçek olayın (Kırgızistan) genelleştirilmiş hâli: artık başka bir
     * ülkede de (burada Hollanda) Nisoya AI arama önerecek bir şey buluyor.
     */
    public function test_yeni_ulkede_nisoya_ai_arama_artik_sonuc_bulur(): void
    {
        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);

        $sonuc = app(RehberDogalDilArama::class)->ara('NL', null, ['büyükelçilik']);

        $this->assertCount(1, $sonuc);
        $this->assertStringContainsString('/nl/', $sonuc->first()['url']);
    }

    public function test_ulke_sayfasi_artik_hazirlaniyor_demez(): void
    {
        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);

        $this->get('/nl')
            ->assertOk()
            ->assertSee('Lahey')
            ->assertDontSee('Bu ülkenin rehberi henüz hazırlanıyor.');
    }

    public function test_adres_ve_koordinat_dolduruluyor(): void
    {
        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);

        $lahey = Temsilcilik::query()->where('country_code', 'NL')->first();

        $this->assertNotEmpty($lahey->adres);
        $this->assertNotNull($lahey->latitude);
        $this->assertNotNull($lahey->longitude);
    }

    /** İzlanda için güvenilir kaynaktan adres bulunamadı — bilerek boş (uydurma yok). */
    public function test_adres_bulunamayan_ulke_bos_kalir(): void
    {
        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);

        $reykjavik = Temsilcilik::query()->where('country_code', 'IS')->first();

        $this->assertNull($reykjavik->adres);
        $this->assertNull($reykjavik->latitude);
    }

    /** Akredite eden ülkenin (Bern/İsviçre) gerçek binası — aynı koordinat. */
    public function test_akredite_ulke_gercek_binanin_koordinatini_tasir(): void
    {
        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);

        $li = Temsilcilik::query()->where('country_code', 'LI')->first();

        $this->assertNotNull($li->latitude);
        $this->assertEquals(46.9382347, (float) $li->latitude);
    }

    public function test_panelden_girilen_adres_ezilmez(): void
    {
        Country::firstOrCreate(['code' => 'NL'], ['name_tr' => 'Hollanda', 'emoji' => '🇳🇱', 'is_active' => true]);

        $lahey = Temsilcilik::create([
            'country_code' => 'NL', 'ad' => 'Lahey Büyükelçiliği', 'slug' => 'lahey',
            'tur' => Temsilcilik::TUR_BUYUKELCILIK, 'sehir' => 'Lahey',
            'adres' => 'Sahibin girdiği adres', 'latitude' => 1.111111, 'longitude' => 2.222222,
            'is_active' => true, 'sort_order' => 0,
        ]);

        $this->seed(RehberDunyaTemsilcilikIskeletiSeeder::class);

        $taze = $lahey->fresh();
        $this->assertSame('Sahibin girdiği adres', $taze->adres);
        $this->assertEquals(1.111111, $taze->latitude);
    }
}
