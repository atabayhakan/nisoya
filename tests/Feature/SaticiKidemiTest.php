<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Satıcı kıdemi rozeti (docs/03-buyume-fikirleri.md, 2026-08-19 · KARAR: YAP).
 *
 * users.created_at'ten kıdem metni üretir — ilan kartlarında (iki temada,
 * kısa biçim) ve ilan detayında (iki tema + mobil şerit, tam cümle).
 * TrustTier::Yeni'nin "Yeni üye" rozet etiketiyle KARIŞTIRILMAMALI: ayrı
 * ölçüt, bkz. User::kidemMetni() docblock'u.
 */
class SaticiKidemiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function satici(int $ayOnce): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'country_code' => 'DE',
            'created_at' => now()->subMonths($ayOnce),
        ]);
    }

    private function vitrineGec(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);
        Cache::flush();
    }

    // ------------------------------------------------------- User modeli

    public function test_bir_aydan_taze_hesap_yakin_zamanda_katildi_der(): void
    {
        $u = $this->satici(0);

        $this->assertSame("Nisoya'da yakın zamanda katıldı", $u->kidemMetni());
        $this->assertNull($u->kidemKisa());
    }

    public function test_ay_cinsinden_kidem_dogru_hesaplanir(): void
    {
        $u = $this->satici(5);

        $this->assertSame("Nisoya'da 5 aydır üye", $u->kidemMetni());
        $this->assertSame('5 aydır üye', $u->kidemKisa());
    }

    public function test_oniki_ay_ve_uzeri_yil_olarak_gosterilir(): void
    {
        $u = $this->satici(25);

        $this->assertSame("Nisoya'da 2 yıldır üye", $u->kidemMetni());
        $this->assertSame('2 yıldır üye', $u->kidemKisa());
    }

    // ------------------------------------------------------------- Kart

    public function test_klasik_kartta_kidem_rozeti_gorunur(): void
    {
        $satici = $this->satici(3);
        Listing::factory()->for($satici)->create(['status' => 'aktif']);

        $this->get('/ilanlar')->assertOk()->assertSee('3 aydır üye');
    }

    public function test_klasik_kartta_taze_hesapta_rozet_basilmaz(): void
    {
        $satici = $this->satici(0);
        Listing::factory()->for($satici)->create(['status' => 'aktif']);

        $this->get('/ilanlar')->assertOk()
            ->assertDontSee('aydır üye')
            ->assertDontSee('yıldır üye');
    }

    public function test_vitrin_kartta_kidem_rozeti_gorunur(): void
    {
        $this->vitrineGec();
        $satici = $this->satici(14);
        Listing::factory()->for($satici)->create(['status' => 'aktif']);

        $this->get('/ilanlar')->assertOk()->assertSee('1 yıldır üye');
    }

    public function test_vitrin_kartta_taze_hesapta_rozet_basilmaz(): void
    {
        $this->vitrineGec();
        $satici = $this->satici(0);
        Listing::factory()->for($satici)->create(['status' => 'aktif']);

        $this->get('/ilanlar')->assertOk()
            ->assertDontSee('aydır üye')
            ->assertDontSee('yıldır üye');
    }

    // ------------------------------------------------------------ Detay

    public function test_klasik_detayda_tam_kidem_cumlesi_gorunur(): void
    {
        $satici = $this->satici(3);
        $listing = Listing::factory()->for($satici)->create(['status' => 'aktif']);

        $this->get(route('listings.show', [$listing, $listing->slug]))
            ->assertOk()
            ->assertSee("Nisoya'da 3 aydır üye")
            ->assertDontSee('Üyelik:');
    }

    public function test_vitrin_detayda_tam_kidem_cumlesi_gorunur(): void
    {
        $this->vitrineGec();
        $satici = $this->satici(14);
        $listing = Listing::factory()->for($satici)->create(['status' => 'aktif']);

        $this->get(route('listings.show', [$listing, $listing->slug]))
            ->assertOk()
            ->assertSee("Nisoya'da 1 yıldır üye");
    }

    public function test_taze_hesapta_detay_sayfasi_hala_bir_sey_gosterir(): void
    {
        // Eski "Üyelik: ay yıl" metni HİÇBİR hesap için boş kalmıyordu —
        // yenisi de öyle olmalı (regresyon: kısa biçim null ama tam cümle
        // asla null olmamalı).
        $satici = $this->satici(0);
        $listing = Listing::factory()->for($satici)->create(['status' => 'aktif']);

        $this->get(route('listings.show', [$listing, $listing->slug]))
            ->assertOk()
            ->assertSee("Nisoya'da yakın zamanda katıldı");
    }
}
