<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCountryResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([
            CurrencySeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
        ]);
    }

    public function test_member_from_kyrgyzstan_sees_kyrgyzstan_and_bishkek_on_homepage(): void
    {
        $kgCountry = Country::query()->firstOrCreate(
            ['code' => 'KG'],
            ['name_tr' => 'Kırgızistan', 'name_en' => 'Kyrgyzstan', 'emoji' => '🇰🇬', 'is_active' => true]
        );

        $temsilcilik = Temsilcilik::query()->create([
            'country_code' => 'KG',
            'ad' => 'Bişkek Büyükelçiliği',
            'slug' => 'biskek',
            'tur' => Temsilcilik::TUR_BUYUKELCILIK,
            'sehir' => 'Bişkek',
            'is_active' => true,
        ]);

        $islemTuru = IslemTuru::query()->firstOrCreate(
            ['slug' => 'pasaport'],
            ['ad' => 'Pasaport Başvurusu', 'is_active' => true]
        );

        TemsilcilikIslemi::query()->create([
            'temsilcilik_id' => $temsilcilik->id,
            'islem_turu_id' => $islemTuru->id,
            'evraklar' => [['ad' => 'T.C. Kimlik Kartı']],
            'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
            'status' => TemsilcilikIslemi::STATUS_YAYIN,
        ]);

        City::query()->firstOrCreate(
            ['country_code' => 'KG', 'name' => 'Bişkek'],
            ['is_active' => true]
        );

        $uye = User::factory()->create([
            'country_code' => 'KG',
            'city' => 'Bişkek',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($uye)->get('/');

        $response->assertOk();
        $response->assertSee('Kırgızistan için konsolosluk rehberi');
        $response->assertSee('Bişkek Türk Futbol Ağı');
        $response->assertSee('Bişkek Futbol Hub');
        $response->assertDontSee('Berlin Türk Futbol Ağı');
    }

    public function test_country_select_route_switches_country_for_guest_and_member(): void
    {
        Country::query()->firstOrCreate(
            ['code' => 'KG'],
            ['name_tr' => 'Kırgızistan', 'name_en' => 'Kyrgyzstan', 'emoji' => '🇰🇬', 'is_active' => true]
        );
        City::query()->firstOrCreate(
            ['country_code' => 'KG', 'name' => 'Bişkek'],
            ['is_active' => true]
        );

        // 1. Guest test
        $response = $this->get(route('country.select', 'KG'));
        $response->assertRedirect();
        $this->assertEquals('KG', session('visitor_country_code'));

        // 2. Member test
        $uye = User::factory()->create([
            'country_code' => 'DE',
            'city' => 'Berlin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($uye)->get(route('country.select', 'KG'));
        $response->assertRedirect();

        $uye->refresh();
        $this->assertEquals('KG', $uye->country_code);
        $this->assertEquals('Bişkek', $uye->city);
    }
}
