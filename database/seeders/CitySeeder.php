<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        // Her ülkenin en büyük 2 şehri. [country_code => [şehirler]]
        $data = [
            // Batı diasporası
            'DE' => ['Berlin', 'Hamburg'],
            'NL' => ['Amsterdam', 'Rotterdam'],
            'GB' => ['Londra', 'Birmingham'],
            'FR' => ['Paris', 'Marsilya'],
            'AT' => ['Viyana', 'Graz'],
            'BE' => ['Brüksel', 'Anvers'],
            'CH' => ['Zürih', 'Cenevre'],
            'SE' => ['Stockholm', 'Göteborg'],
            'NO' => ['Oslo', 'Bergen'],
            'DK' => ['Kopenhag', 'Aarhus'],
            'US' => ['New York', 'Los Angeles'],
            'CA' => ['Toronto', 'Montreal'],
            'AU' => ['Sidney', 'Melbourne'],
            'IT' => ['Roma', 'Milano'],
            'ES' => ['Madrid', 'Barselona'],
            'PL' => ['Varşova', 'Krakov'],
            // Türk dünyası
            'AZ' => ['Bakü', 'Gence'],
            'KZ' => ['Almatı', 'Astana'],
            'KG' => ['Bişkek', 'Oş'],
            'UZ' => ['Taşkent', 'Semerkant'],
            'TM' => ['Aşkabat', 'Türkmenabat'],
            'RU' => ['Moskova', 'Sankt-Peterburg'],
            // Körfez
            'AE' => ['Dubai', 'Abu Dabi'],
            'QA' => ['Doha', 'Al Rayyan'],
            'SA' => ['Riyad', 'Cidde'],
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
