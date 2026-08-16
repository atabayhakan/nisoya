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
            // Körfez (büyüme kararı 2026-07-29, öneri 3): sona eklendi —
            // sort_order dizin sırası olduğu için mevcut ülkelerin sırası bozulmaz.
            ['AE', 'Birleşik Arap Emirlikleri', '🇦🇪', 'AED', 25.2048, 55.2708],
            ['QA', 'Katar', '🇶🇦', 'QAR', 25.2854, 51.5310],
            ['SA', 'Suudi Arabistan', '🇸🇦', 'SAR', 24.7136, 46.6753],

            // Gelişmişlik seviyesine göre genişleme (2026-08-16): UNDP İnsan
            // Gelişmişlik Endeksi'nde (HDR 2025, 2023 verisi) hâlâ listede
            // olmayan bir sonraki 30 ülke, sıralama korunarak. Koordinatlar bu
            // partide "diaspora yoğun şehir" değil BAŞKENT — seçim ölçütü
            // diaspora değil gelişmişlik olduğu için elde diaspora verisi yok
            // (İsrail'de İbrani/uluslararası anlaşmazlığa girmemek için Tel
            // Aviv kullanıldı, Kudüs değil).
            //
            // İKİ KASITLI ATLAMA:
            //  · Türkiye (HDI #50) — platform "yurt dışındaki Türkler"
            //    hedefliyor, ikamet ülkesi TR olamaz; mevcut 25 ülkede de yok.
            //  · Kıbrıs (CY) — ISO kodu yalnız Rum Yönetimi'ni karşılıyor;
            //    adlandırması (KKTC/Kıbrıs/GKRY) sahiple konuşulmadan
            //    eklenmedi, bkz. konuşma özeti.
            ['IS', 'İzlanda', '🇮🇸', 'ISK', 64.1466, -21.9426],
            ['IE', 'İrlanda', '🇮🇪', 'EUR', 53.3498, -6.2603],
            ['FI', 'Finlandiya', '🇫🇮', 'EUR', 60.1699, 24.9384],
            ['SG', 'Singapur', '🇸🇬', 'SGD', 1.3521, 103.8198],
            ['NZ', 'Yeni Zelanda', '🇳🇿', 'NZD', -41.2865, 174.7762],
            ['LI', 'Lihtenştayn', '🇱🇮', 'CHF', 47.1410, 9.5209],
            ['KR', 'Güney Kore', '🇰🇷', 'KRW', 37.5665, 126.9780],
            ['SI', 'Slovenya', '🇸🇮', 'EUR', 46.0569, 14.5058],
            ['JP', 'Japonya', '🇯🇵', 'JPY', 35.6762, 139.6503],
            ['MT', 'Malta', '🇲🇹', 'EUR', 35.8989, 14.5146],
            ['LU', 'Lüksemburg', '🇱🇺', 'EUR', 49.6116, 6.1319],
            ['IL', 'İsrail', '🇮🇱', 'ILS', 32.0853, 34.7818],
            ['SM', 'San Marino', '🇸🇲', 'EUR', 43.9424, 12.4578],
            ['CZ', 'Çekya', '🇨🇿', 'CZK', 50.0755, 14.4378],
            ['AD', 'Andorra', '🇦🇩', 'EUR', 42.5063, 1.5218],
            ['GR', 'Yunanistan', '🇬🇷', 'EUR', 37.9838, 23.7275],
            ['EE', 'Estonya', '🇪🇪', 'EUR', 59.4370, 24.7536],
            ['BH', 'Bahreyn', '🇧🇭', 'BHD', 26.2285, 50.5860],
            ['LT', 'Litvanya', '🇱🇹', 'EUR', 54.6872, 25.2797],
            ['PT', 'Portekiz', '🇵🇹', 'EUR', 38.7223, -9.1393],
            ['LV', 'Letonya', '🇱🇻', 'EUR', 56.9496, 24.1052],
            ['HR', 'Hırvatistan', '🇭🇷', 'EUR', 45.8150, 15.9819],
            ['SK', 'Slovakya', '🇸🇰', 'EUR', 48.1486, 17.1077],
            ['CL', 'Şili', '🇨🇱', 'CLP', -33.4489, -70.6693],
            ['HU', 'Macaristan', '🇭🇺', 'HUF', 47.4979, 19.0402],
            ['AR', 'Arjantin', '🇦🇷', 'ARS', -34.6037, -58.3816],
            ['ME', 'Karadağ', '🇲🇪', 'EUR', 42.4304, 19.2594],
            ['UY', 'Uruguay', '🇺🇾', 'UYU', -34.9011, -56.1645],
            ['OM', 'Umman', '🇴🇲', 'OMR', 23.5859, 58.4059],
            ['KW', 'Kuveyt', '🇰🇼', 'KWD', 29.3759, 47.9774],
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
