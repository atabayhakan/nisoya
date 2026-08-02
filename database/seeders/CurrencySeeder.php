<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        // Türk Lirası bilinçli olarak YOK — platform yurtdışı odaklı.
        $currencies = [
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            ['code' => 'USD', 'name' => 'Amerikan Doları', 'symbol' => '$'],
            ['code' => 'GBP', 'name' => 'İngiliz Sterlini', 'symbol' => '£'],
            ['code' => 'CHF', 'name' => 'İsviçre Frangı', 'symbol' => 'CHF'],
            ['code' => 'SEK', 'name' => 'İsveç Kronu', 'symbol' => 'kr'],
            ['code' => 'NOK', 'name' => 'Norveç Kronu', 'symbol' => 'kr'],
            ['code' => 'DKK', 'name' => 'Danimarka Kronu', 'symbol' => 'kr'],
            ['code' => 'CAD', 'name' => 'Kanada Doları', 'symbol' => 'C$'],
            ['code' => 'AUD', 'name' => 'Avustralya Doları', 'symbol' => 'A$'],
            ['code' => 'PLN', 'name' => 'Polonya Zlotisi', 'symbol' => 'zł'],
            ['code' => 'RUB', 'name' => 'Rus Rublesi', 'symbol' => '₽'],
            ['code' => 'KZT', 'name' => 'Kazak Tengesi', 'symbol' => '₸'],
            ['code' => 'AZN', 'name' => 'Azerbaycan Manatı', 'symbol' => '₼'],
            ['code' => 'UZS', 'name' => 'Özbek Somu', 'symbol' => 'soʻm'],
            ['code' => 'KGS', 'name' => 'Kırgız Somu', 'symbol' => 'сом'],
            ['code' => 'TMT', 'name' => 'Türkmen Manatı', 'symbol' => 'm'],
            // Körfez (büyüme kararı 2026-07-29, öneri 3). Simge olarak Latin
            // kod: Arapça simgeler (د.إ) soldan-sağa fiyat metninde karakter
            // sırasını görsel olarak bozabiliyor — CHF örneğiyle aynı tercih.
            ['code' => 'AED', 'name' => 'BAE Dirhemi', 'symbol' => 'AED'],
            ['code' => 'QAR', 'name' => 'Katar Riyali', 'symbol' => 'QAR'],
            ['code' => 'SAR', 'name' => 'Suudi Riyali', 'symbol' => 'SAR'],
        ];

        foreach ($currencies as $i => $c) {
            Currency::updateOrCreate(
                ['code' => $c['code']],
                ['name' => $c['name'], 'symbol' => $c['symbol'], 'is_active' => true, 'sort_order' => $i],
            );
        }
    }
}
