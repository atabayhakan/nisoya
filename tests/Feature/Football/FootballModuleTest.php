<?php

namespace Tests\Feature\Football;

use App\Support\Settings;
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

    public function test_football_hub_is_accessible_when_module_is_enabled(): void
    {
        Settings::setMany(['modul.hali_saha' => '1']);

        $response = $this->get(route('football.index'));
        $response->assertOk();
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
