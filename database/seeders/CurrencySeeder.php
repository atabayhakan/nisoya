<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        // Türk Lirası uzun süre BİLİNÇLİ OLARAK yoktu — platform yurtdışı
        // odaklı. KKTC eklenmesiyle (2026-08-16) TEK istisna aşağıda: TRY,
        // sırf o satır için. Yeni bir ülkeye "zaten TRY var" diye TL
        // verilmemeli — istisna KKTC'ye özel, kurala değil.
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
            // Gelişmişlik seviyesine göre ülke genişlemesiyle birlikte
            // (2026-08-16) — CountrySeeder'daki 30 yeni ülkenin karşılıkları.
            // EUR ve CHF zaten yukarıda var, o yüzden burada yok. Sembol
            // çakışması riskinde (peso/dinar/riyal ailesi) Gulf akımındaki
            // gibi Latin kod fallback'i kullanıldı.
            ['code' => 'ISK', 'name' => 'İzlanda Kronu', 'symbol' => 'kr'],
            ['code' => 'SGD', 'name' => 'Singapur Doları', 'symbol' => 'S$'],
            ['code' => 'NZD', 'name' => 'Yeni Zelanda Doları', 'symbol' => 'NZ$'],
            ['code' => 'KRW', 'name' => 'Güney Kore Wonu', 'symbol' => '₩'],
            ['code' => 'JPY', 'name' => 'Japon Yeni', 'symbol' => '¥'],
            ['code' => 'ILS', 'name' => 'İsrail Şekeli', 'symbol' => '₪'],
            ['code' => 'CZK', 'name' => 'Çek Korunası', 'symbol' => 'Kč'],
            ['code' => 'BHD', 'name' => 'Bahreyn Dinarı', 'symbol' => 'BHD'],
            ['code' => 'CLP', 'name' => 'Şili Pesosu', 'symbol' => 'CLP'],
            ['code' => 'HUF', 'name' => 'Macar Forinti', 'symbol' => 'Ft'],
            ['code' => 'ARS', 'name' => 'Arjantin Pesosu', 'symbol' => 'ARS'],
            ['code' => 'UYU', 'name' => 'Uruguay Pesosu', 'symbol' => 'UYU'],
            ['code' => 'OMR', 'name' => 'Umman Riyali', 'symbol' => 'OMR'],
            ['code' => 'KWD', 'name' => 'Kuveyt Dinarı', 'symbol' => 'KWD'],
            // KKTC eklenmesiyle birlikte (2026-08-16) — sahiple konuşuldu.
            // Türk Lirası buraya kadar BİLİNÇLİ OLARAK yoktu ("platform
            // yurtdışı odaklı" — bkz. yukarıdaki not). KKTC'de fiilen
            // kullanılan para birimi TL olduğu için TEK istisna burada.
            ['code' => 'TRY', 'name' => 'Türk Lirası', 'symbol' => '₺'],
            // Tayland/Kamboçya/Endonezya CountrySeeder'a eklenmesiyle
            // birlikte (2026-08-19) — bkz. CountrySeeder'daki not.
            ['code' => 'THB', 'name' => 'Tayland Bahtı', 'symbol' => '฿'],
            ['code' => 'KHR', 'name' => 'Kamboçya Rieli', 'symbol' => 'KHR'],
            ['code' => 'IDR', 'name' => 'Endonezya Rupiahı', 'symbol' => 'Rp'],
        ];

        foreach ($currencies as $i => $c) {
            Currency::updateOrCreate(
                ['code' => $c['code']],
                ['name' => $c['name'], 'symbol' => $c['symbol'], 'is_active' => true, 'sort_order' => $i],
            );
        }
    }
}
