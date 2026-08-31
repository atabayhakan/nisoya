<?php

namespace Tests\Feature\Football;

use App\Support\Settings;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FootballModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_football_hub_resolves_visitor_country_city(): void
    {
        CitySeeder::class;
        $this->seed(CitySeeder::class);
        Settings::setMany(['modul.hali_saha' => '1']);

        // Kırgızistan (KG) için Bişkek
        $responseKg = $this->withSession(['visitor_country_code' => 'KG'])->get(route('football.index'));
        $responseKg->assertOk();
        $responseKg->assertSee('Bişkek');

        // Azerbaycan (AZ) için Bakü
        $responseAz = $this->withSession(['visitor_country_code' => 'AZ'])->get(route('football.index'));
        $responseAz->assertOk();
        $responseAz->assertSee('Bakü');
    }

    public function test_football_routes_return_404_when_module_is_disabled(): void
    {
        Settings::setMany(['modul.hali_saha' => '0']);

        $response = $this->get(route('football.index'));
        $response->assertNotFound();

        $cityResponse = $this->get(route('football.city', 'berlin'));
        $cityResponse->assertNotFound();
    }
}
