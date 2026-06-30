<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        // Türk dünyası ülkelerinin en büyük 2 şehri. [country_code, [şehirler]]
        $data = [
            'AZ' => ['Bakü', 'Gence'],
            'KZ' => ['Almatı', 'Astana'],
            'KG' => ['Bişkek', 'Oş'],
            'UZ' => ['Taşkent', 'Semerkant'],
            'TM' => ['Aşkabat', 'Türkmenabat'],
            'RU' => ['Moskova', 'Sankt-Peterburg'],
        ];

        foreach ($data as $countryCode => $cities) {
            foreach ($cities as $i => $name) {
                City::updateOrCreate(
                    ['country_code' => $countryCode, 'name' => $name],
                    ['sort_order' => $i, 'is_active' => true],
                );
            }
        }
    }
}
