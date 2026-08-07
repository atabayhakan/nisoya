<?php

namespace App\Support\Growth;

/**
 * Keşif için hazır hedef verisi: ülke → Türk-yoğun şehirler ve meslek presetleri
 * (Türkçe/İngilizce/yerel dil terimleriyle). QueryPermutationEngine bunu tüketir.
 *
 * Gönderim hedefi ülkeler (bkz. docs/06-tanitim-agenti-plani.md): ABD + Orta Asya
 * + GD Asya. AB/TR keşifte olabilir ama gönderimde bloklanır — o ayrım burada
 * değil, gönderim katmanında yapılır.
 */
final class GrowthCatalog
{
    /**
     * Ülke kodu → örnek Türk-yoğun şehirler.
     *
     * -----------------------------------------------------------------------
     * BURAYA EYALET/BÖLGE YAZILMAZ — YALNIZ ŞEHİR (2026-08-07, ölçüldü)
     *
     * Liste "New Jersey" içeriyordu. Keşif zinciri şehir adını Nominatim'e
     * verip dönen NOKTA etrafında 15 km'lik bir kutu tarıyor; bir EYALET
     * adı verildiğinde dönen nokta eyaletin coğrafi merkezi oluyor.
     * ÖLÇÜLDÜ: "New Jersey" → 40.076,-74.404 (eyaletin ortası, Pine Barrens
     * kırsalı) → 40 sonuçluk sorgudan **0 aday**. Yani bu satır aylardır
     * hiçbir şey getirmiyordu ve hata da vermiyordu.
     *
     * Yerine "Clifton, New Jersey" kondu: 40.858,-74.164 → **18 aday, 16'sı
     * Türk** (Istanbul Kebab House, Antepli Baklava, Turkish Cuisine...).
     * 15 km'lik kutu Paterson'ı — NJ'deki en yoğun Türk hattını — kapsıyor,
     * o yüzden ayrıca Paterson satırı eklenmedi.
     *
     * -----------------------------------------------------------------------
     * ABD ŞEHİRLERİ EYALETLE NİTELENİR
     *
     * Aynı ölçümde ikinci tuzak çıktı: sade "Union City" → 37.587,-122.022,
     * yani KALİFORNİYA'daki Union City. Hedeflenen New Jersey'deki adaşıydı.
     * Nominatim'e eyaletsiz giden ad sessizce yanlış kıtaya düşebiliyor;
     * arama "şehir, ülke" biçiminde kurulduğu için eyalet ancak buraya
     * yazılırsa sorguya girer. Adaşı riski olan her ABD şehri eyaletiyle
     * yazılmalı. (Adayın `city` alanı OSM'in kendi `addr:city` etiketinden
     * gelir — burada eyalet yazması kayıtları kirletmez.)
     *
     * @var array<string, list<string>>
     */
    public const CITIES = [
        'US' => ['New York', 'Clifton, New Jersey', 'Los Angeles', 'Chicago', 'Houston'],
        'KZ' => ['Almaty', 'Astana', 'Shymkent'],
        'KG' => ['Bishkek', 'Osh'],
        'UZ' => ['Tashkent', 'Samarkand'],
        'TH' => ['Bangkok', 'Phuket', 'Pattaya'],
        'KH' => ['Phnom Penh', 'Siem Reap'],
        // AB — keşifte var (pazar zekâsı) ama gönderim RegionPolicy ile engelli.
        'DE' => ['Berlin', 'Köln'],
    ];

    /**
     * Meslek presetleri — Nisoya hizmet kategorileriyle örtüşür. Her biri Türkçe
     * (tr), İngilizce (en), gerektiğinde yerel (local) terim ve OpenStreetMap
     * etiketi (osm, "key=value") taşır. osm alanı Overpass keşif kaynağı içindir.
     *
     * @var list<array{key: string, tr: string, en: string, osm: string, local?: string}>
     */
    public const TRADES = [
        ['key' => 'berber', 'tr' => 'berber', 'en' => 'barber', 'osm' => 'shop=hairdresser'],
        ['key' => 'kuafor', 'tr' => 'kuaför', 'en' => 'hair salon', 'osm' => 'shop=beauty'],
        ['key' => 'mobilyaci', 'tr' => 'mobilyacı', 'en' => 'furniture maker', 'osm' => 'shop=furniture'],
        ['key' => 'elektrikci', 'tr' => 'elektrikçi', 'en' => 'electrician', 'osm' => 'craft=electrician'],
        ['key' => 'cilingir', 'tr' => 'çilingir', 'en' => 'locksmith', 'osm' => 'craft=locksmith'],
        ['key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant', 'osm' => 'amenity=restaurant'],
        ['key' => 'insaat', 'tr' => 'inşaat ustası', 'en' => 'contractor', 'osm' => 'craft=builder'],
        ['key' => 'oto', 'tr' => 'oto tamir', 'en' => 'auto repair', 'osm' => 'shop=car_repair'],
        ['key' => 'terzi', 'tr' => 'terzi', 'en' => 'tailor', 'osm' => 'craft=tailor'],
        ['key' => 'nakliyat', 'tr' => 'nakliyat', 'en' => 'moving company', 'osm' => 'office=moving_company'],
    ];

    /**
     * Belirli mesleklerin yerel (Rusça/Tayca) karşılıkları — Orta Asya ve GD
     * Asya keşfinde yerel dilde arama isabeti artırır.
     *
     * @var array<string, array<string, string>>
     */
    public const LOCAL_TERMS = [
        'KZ' => ['elektrikci' => 'электрик', 'mobilyaci' => 'мебель', 'berber' => 'барбершоп'],
        'KG' => ['elektrikci' => 'электрик', 'mobilyaci' => 'мебель', 'berber' => 'барбершоп'],
        'UZ' => ['elektrikci' => 'электрик', 'mobilyaci' => 'мебель'],
        'TH' => ['berber' => 'ช่างตัดผม', 'lokanta' => 'ร้านอาหาร'],
    ];

    /**
     * Bir ülke için meslekleri yerel-dil terimiyle zenginleştirilmiş döndürür.
     *
     * @return list<array{key: string, tr: string, en: string, osm: string, local?: string}>
     */
    public static function tradesForCountry(string $country): array
    {
        $local = self::LOCAL_TERMS[$country] ?? [];

        return array_map(static function (array $trade) use ($local): array {
            if (isset($local[$trade['key']])) {
                $trade['local'] = $local[$trade['key']];
            }

            return $trade;
        }, self::TRADES);
    }
}
