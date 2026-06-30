<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        // Türk diasporasının yoğun olduğu ülkeler. Koordinatlar: diaspora yoğun büyük şehir/merkez.
        // [code, name_tr, emoji, default_currency, lat, lng]
        $countries = [
            ['DE', 'Almanya', '🇩🇪', 'EUR', 51.1657, 10.4515],
            ['NL', 'Hollanda', '🇳🇱', 'EUR', 52.1326, 5.2913],
            ['GB', 'Birleşik Krallık', '🇬🇧', 'GBP', 51.5074, -0.1278],
            ['FR', 'Fransa', '🇫🇷', 'EUR', 48.8566, 2.3522],
            ['AT', 'Avusturya', '🇦🇹', 'EUR', 48.2082, 16.3738],
            ['BE', 'Belçika', '🇧🇪', 'EUR', 50.8503, 4.3517],
            ['CH', 'İsviçre', '🇨🇭', 'CHF', 47.3769, 8.5417],
            ['SE', 'İsveç', '🇸🇪', 'SEK', 59.3293, 18.0686],
            ['NO', 'Norveç', '🇳🇴', 'NOK', 59.9139, 10.7522],
            ['DK', 'Danimarka', '🇩🇰', 'DKK', 55.6761, 12.5683],
            ['US', 'Amerika Birleşik Devletleri', '🇺🇸', 'USD', 40.7128, -74.0060],
            ['CA', 'Kanada', '🇨🇦', 'CAD', 43.6532, -79.3832],
            ['AU', 'Avustralya', '🇦🇺', 'AUD', -33.8688, 151.2093],
            ['IT', 'İtalya', '🇮🇹', 'EUR', 41.9028, 12.4964],
            ['ES', 'İspanya', '🇪🇸', 'EUR', 40.4168, -3.7038],
            ['PL', 'Polonya', '🇵🇱', 'PLN', 52.2297, 21.0122],
            ['AZ', 'Azerbaycan', '🇦🇿', 'AZN', 40.4093, 49.8671],
            ['KZ', 'Kazakistan', '🇰🇿', 'KZT', 43.2389, 76.8897],
            ['KG', 'Kırgızistan', '🇰🇬', 'KGS', 42.8746, 74.5698],
            ['UZ', 'Özbekistan', '🇺🇿', 'UZS', 41.2995, 69.2401],
            ['TM', 'Türkmenistan', '🇹🇲', 'TMT', 37.9601, 58.3261],
            ['RU', 'Rusya', '🇷🇺', 'RUB', 55.7558, 37.6173],
        ];

        foreach ($countries as $i => $c) {
            Country::updateOrCreate(
                ['code' => $c[0]],
                [
                    'name_tr' => $c[1],
                    'emoji' => $c[2],
                    'default_currency' => $c[3],
                    'latitude' => $c[4],
                    'longitude' => $c[5],
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );
        }
    }
}
