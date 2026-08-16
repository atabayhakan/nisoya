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
            // Gelişmişlik seviyesine göre eklenen ülkeler (2026-08-16).
            // Tek şehirli kayıtlar mikro-devletler: ikinci "en büyük şehir"
            // ayrımı gerçek/anlamlı değilse uydurulmadı (Singapur, Vaduz,
            // Lüksemburg, San Marino, Andorra la Vella).
            'IS' => ['Reykjavik', 'Akureyri'],
            'IE' => ['Dublin', 'Cork'],
            'FI' => ['Helsinki', 'Tampere'],
            'SG' => ['Singapur'],
            'NZ' => ['Auckland', 'Wellington'],
            'LI' => ['Vaduz'],
            'KR' => ['Seul', 'Busan'],
            'SI' => ['Ljubljana', 'Maribor'],
            'JP' => ['Tokyo', 'Osaka'],
            'MT' => ['Valletta', 'Sliema'],
            'LU' => ['Lüksemburg'],
            'IL' => ['Kudüs', 'Tel Aviv'],
            'SM' => ['San Marino'],
            'CZ' => ['Prag', 'Brno'],
            'AD' => ['Andorra la Vella'],
            'GR' => ['Atina', 'Selanik'],
            'EE' => ['Tallinn', 'Tartu'],
            'BH' => ['Manama', 'Muharraq'],
            'LT' => ['Vilnius', 'Kaunas'],
            'PT' => ['Lizbon', 'Porto'],
            'LV' => ['Riga', 'Daugavpils'],
            'HR' => ['Zagreb', 'Split'],
            'SK' => ['Bratislava', 'Kosice'],
            'CL' => ['Santiago', 'Valparaiso'],
            'HU' => ['Budapeşte', 'Debrecen'],
            'AR' => ['Buenos Aires', 'Cordoba'],
            'ME' => ['Podgorica', 'Niksic'],
            'UY' => ['Montevideo', 'Salto'],
            'OM' => ['Maskat', 'Salalah'],
            'KW' => ['Kuveyt', 'Hawalli'],
            'CY' => ['Lefkoşa', 'Limasol'],
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
