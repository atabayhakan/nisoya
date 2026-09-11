<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `nabiz:hedef-guncelle` — Nisoya Nabzı hedefini otomatik modda günceller.
 *
 * NE KORUYOR: varsayılan KAPALI olmalı — sahip belirli bir sayı yazmış
 * olabilir (ör. bir kampanya için "100"), otomasyon onu sessizce ezmemeli.
 */
class NabizHedefiGuncelleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_otomatik_kapaliyken_hedefe_dokunulmaz(): void
    {
        Settings::setMany(['nabiz.hedef_otomatik' => '0', 'nabiz.hedef_sayi' => '77']);

        $this->artisan('nabiz:hedef-guncelle')->assertExitCode(0);

        $this->assertSame('77', Settings::get('nabiz.hedef_sayi'));
    }

    public function test_otomatik_aciksa_hedef_gecmis_ortalamasina_gore_guncellenir(): void
    {
        Settings::setMany(['nabiz.hedef_otomatik' => '1', 'nabiz.hedef_sayi' => '999', 'nabiz.hedef_metrik' => 'yeni_uye']);

        User::factory()->count(20)->create(['created_at' => now()->subMonths(1)->startOfMonth()->addDays(2)]);

        $this->artisan('nabiz:hedef-guncelle')->assertExitCode(0);

        $this->assertNotSame('999', Settings::get('nabiz.hedef_sayi'));
        $this->assertGreaterThanOrEqual(10, (int) Settings::get('nabiz.hedef_sayi'));
    }
}
