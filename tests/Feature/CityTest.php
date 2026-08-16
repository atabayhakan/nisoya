<?php

namespace Tests\Feature;

use App\Models\City;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cities_are_seeded(): void
    {
        $this->seed([CountrySeeder::class, CitySeeder::class]);

        $this->assertDatabaseHas('cities', ['country_code' => 'AZ', 'name' => 'Bakü']);
        $this->assertDatabaseHas('cities', ['country_code' => 'RU', 'name' => 'Moskova']);
        $this->assertDatabaseHas('cities', ['country_code' => 'KZ', 'name' => 'Almatı']);
        $this->assertDatabaseHas('cities', ['country_code' => 'DE', 'name' => 'Berlin']);
        $this->assertDatabaseHas('cities', ['country_code' => 'GB', 'name' => 'Londra']);
        $this->assertDatabaseHas('cities', ['country_code' => 'AE', 'name' => 'Dubai']);
        // Ülke başına 2 tohum şehir (mikro-devletlerde 1) — 25 ülke × 2 (50)
        // + gelişmişlik-seviyesi genişlemesi: 25 ülke × 2 + 5 mikro-devlet
        // × 1 (55) + Güney Kıbrıs × 2 + KKTC × 2 (2026-08-16).
        $this->assertSame(109, City::count());
    }

    public function test_registration_page_includes_city_suggestions(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CitySeeder::class]);

        $this->get('/kayit')
            ->assertOk()
            ->assertSee('city-options', false)
            ->assertSee('Bakü', false)
            ->assertSee('Moskova', false);
    }
}
