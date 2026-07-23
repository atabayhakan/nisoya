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
     * @var array<string, list<string>>
     */
    public const CITIES = [
        'US' => ['New York', 'New Jersey', 'Los Angeles', 'Chicago', 'Houston'],
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
     * (tr), İngilizce (en) ve gerektiğinde yerel (local) terim taşır.
     *
     * @var list<array{key: string, tr: string, en: string, local?: string}>
     */
    public const TRADES = [
        ['key' => 'berber', 'tr' => 'berber', 'en' => 'barber'],
        ['key' => 'kuafor', 'tr' => 'kuaför', 'en' => 'hair salon'],
        ['key' => 'mobilyaci', 'tr' => 'mobilyacı', 'en' => 'furniture maker'],
        ['key' => 'elektrikci', 'tr' => 'elektrikçi', 'en' => 'electrician'],
        ['key' => 'cilingir', 'tr' => 'çilingir', 'en' => 'locksmith'],
        ['key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant'],
        ['key' => 'insaat', 'tr' => 'inşaat ustası', 'en' => 'contractor'],
        ['key' => 'oto', 'tr' => 'oto tamir', 'en' => 'auto repair'],
        ['key' => 'terzi', 'tr' => 'terzi', 'en' => 'tailor'],
        ['key' => 'nakliyat', 'tr' => 'nakliyat', 'en' => 'moving company'],
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
     * @return list<array{key: string, tr: string, en: string, local?: string}>
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
