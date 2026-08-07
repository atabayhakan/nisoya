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
     * -----------------------------------------------------------------------
     * SIRA = VERİM SIRASI, ALFABE YA DA TEMA DEĞİL (2026-08-08)
     *
     * `DiscoveryRunner` bu diziyi baştan keser (`--trades=N`, varsayılan 3).
     * Yani BAŞTAKİ meslekler taranan meslekler demek — sıra kozmetik değil,
     * varsayılan çalıştırmanın sonuç verip vermeyeceğini belirliyor.
     *
     * Eskiden lokanta 6. sıradaydı ve varsayılan `--trades=3` ona hiç
     * ulaşmıyordu: berber + kuaför + mobilyacı taranıyordu. Oysa Overpass
     * sorgusuna gömülü isim deseninin GÜÇLÜ TERİMLERİNİN HEPSİ YEMEK
     * (kebap, döner, lahmacun, baklava, lokanta, künefe...) — bkz.
     * {@see TurkishLexicon::overpassIsimDeseni}. Yemek dışı meslekler yalnız
     * zayıf yer adlarına ("Istanbul Barber") ve doğrudan "Turkish" ibaresine
     * kalıyor; çok daha ince bir ağ. Yurtdışındaki Türk berberi genelde kişi
     * adı taşıyor ("Mehmet's Barber Shop") ve kişi adları desene BİLEREK
     * konmadı ("ali" → "It-ali-an" getirirdi).
     *
     * Sonuç: varsayılan komutu deneyen kişi sıfır sonuç alıp aracı bozuk
     * sanıyordu. Bugüne kadarki bütün gerçek sayılar lokantadan geldi
     * (PR #118: 0 → 37 aday · PR #122 Clifton ölçümü: 18 aday, 16'sı Türk).
     *
     * DÜRÜSTLÜK NOTU: yalnız lokantanın verimi ÖLÇÜLDÜ. Berber taraması 0
     * verdi ama o turda 5 sorgunun 3'ü Overpass tarafında düştü (504/429),
     * yani zayıf kanıt. Kalan mesleklerin kendi aralarındaki sıra ölçüm değil,
     * eski sıranın korunmasıdır — ölçüldükçe düzeltilmeli.
     *
     * -----------------------------------------------------------------------
     * FIRIN NEDEN EKLENDİ, KASAP/MARKET NEDEN EKLENMEDİ (2026-08-08)
     *
     * Desen yemek odaklıysa katalogda yemek mesleğinin tek olması darboğazdı.
     * Aday üç meslek Berlin'de ölçüldü (Overpass, gerçek sorgu):
     *
     *   shop=bakery      → 12 aday, 9'u KESİN TÜRK   (Ağam Baklava, Gaziantep
     *                      Kılıçoğlu Baklavacı, Sönmez Baklava, Elit Simit...)
     *   shop=supermarket →  6 aday, 0'ı kesin Türk, 5'i sınırda
     *   shop=butcher     →  ölçülemedi (Overpass iki denemede de HTTP 504)
     *   shop=convenience →  ölçülemedi (HTTP 429, kendi sorgu hızımdan)
     *
     * MEKANİZMA farkı sonucu açıklıyor ve tahmin edilebilir kılıyor: desen
     * işletme ADINDA kültürel terim arıyor, meslek adını değil. Türk fırını
     * ÜRÜN adıyla anılır — baklava, simit, börek, künefe — ve bunların hepsi
     * zaten {@see TurkishLexicon::CULTURAL_STRONG} içinde. Kasap ve market ise
     * "Öz Kasap", "Anadolu Market" gibi adlanır; `kasap`/`market`/`bakkal`
     * sözlükte YOK, o yüzden yalnız yer adı taşıyanlar (anadolu, istanbul...)
     * yakalanıyor — supermarket ölçümünün 0 kesin/5 sınırda çıkması tam bu.
     *
     * Yani kasap ve marketin önündeki engel MESLEK EKSİKLİĞİ DEĞİL, SÖZLÜK
     * EKSİKLİĞİ. Onları açmanın yolu `kasap`, `bakkal`, `firin`, `sarkuteri`
     * terimlerini sözlüğe eklemek — ama o değişiklik hem Overpass desenini hem
     * dedektör puanlamasını birden etkiler ve ölçülmeden yapılmamalı. Ölçüm
     * mümkün olduğunda sıradaki iş budur.
     *
     * @var list<array{key: string, tr: string, en: string, osm: string, local?: string}>
     */
    public const TRADES = [
        // İlk iki sıra ÖLÇÜLMÜŞ verimli mesleklerdir — sıra korunmalı (yukarıdaki not).
        ['key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant', 'osm' => 'amenity=restaurant'],
        ['key' => 'firin', 'tr' => 'fırın / pastane', 'en' => 'bakery', 'osm' => 'shop=bakery'],
        ['key' => 'berber', 'tr' => 'berber', 'en' => 'barber', 'osm' => 'shop=hairdresser'],
        ['key' => 'kuafor', 'tr' => 'kuaför', 'en' => 'hair salon', 'osm' => 'shop=beauty'],
        ['key' => 'mobilyaci', 'tr' => 'mobilyacı', 'en' => 'furniture maker', 'osm' => 'shop=furniture'],
        ['key' => 'elektrikci', 'tr' => 'elektrikçi', 'en' => 'electrician', 'osm' => 'craft=electrician'],
        ['key' => 'cilingir', 'tr' => 'çilingir', 'en' => 'locksmith', 'osm' => 'craft=locksmith'],
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
