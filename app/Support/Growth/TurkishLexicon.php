<?php

namespace App\Support\Growth;

use App\Services\Growth\TurkishBusinessDetector;

/**
 * Türk işletme/kişi tespiti için sözlükler ve metin normalizasyonu.
 *
 * Tespit motorunun (App\Services\Growth\TurkishBusinessDetector) deterministik,
 * API'siz katmanı bu verilere dayanır. Tüm sözlük girdileri ASCII'ye katlanmış
 * (küçük harf, diakritiksiz) tutulur; eşleştirme öncesi metin de fold() ile
 * aynı biçime getirilir.
 */
final class TurkishLexicon
{
    /**
     * Güçlü kültürel işaretler — çoğu Türk mutfağına özgü, tek başına yüksek
     * olasılık taşır (döner/lahmacun satan bir yer neredeyse kesin Türk'tür).
     *
     * @var list<string>
     */
    public const CULTURAL_STRONG = [
        'kebap', 'kebab', 'doner', 'donair', 'lahmacun', 'pide', 'baklava', 'lokanta',
        'lokantasi', 'ocakbasi', 'borek', 'simit', 'kunefe', 'gozleme', 'mangal',
        'meze', 'kofte', 'durum', 'iskender', 'ayran', 'manti', 'sofrasi', 'ottoman',
        'osmanli',
    ];

    /**
     * Zayıf kültürel işaretler — yer/miras adları; Türk temalı ama tesadüfi de
     * olabilir (bir "Istanbul Cafe" başkası tarafından da açılmış olabilir).
     *
     * @var list<string>
     */
    public const CULTURAL_WEAK = [
        'anadolu', 'anatolia', 'istanbul', 'ankara', 'izmir', 'antalya', 'marmara',
        'efes', 'bosphorus', 'bogazici', 'sultan', 'saray', 'pasa', 'bereket',
        'yildiz', 'hurrem', 'gurme',
    ];

    /**
     * Yaygın Türkçe ön adlar (ASCII katlanmış).
     *
     * @var list<string>
     */
    public const GIVEN_NAMES = [
        'mehmet', 'mustafa', 'ahmet', 'ali', 'huseyin', 'hasan', 'ibrahim', 'ismail',
        'osman', 'yusuf', 'murat', 'emre', 'burak', 'kemal', 'suleyman', 'fatih',
        'serkan', 'omer', 'kaan', 'cem', 'tolga', 'baris', 'can', 'onur', 'ozan',
        'volkan', 'yigit', 'arda', 'ugur', 'hakan', 'tarik', 'sinan', 'ayse', 'fatma',
        'emine', 'hatice', 'zeynep', 'elif', 'meryem', 'ozlem', 'sevgi', 'esra',
        'busra', 'merve', 'dilek', 'pinar', 'selin', 'ebru', 'aylin', 'cansu',
    ];

    /**
     * Yaygın Türkçe soyadları (ASCII katlanmış).
     *
     * @var list<string>
     */
    public const SURNAMES = [
        'yilmaz', 'kaya', 'demir', 'sahin', 'celik', 'yildiz', 'yildirim', 'ozturk',
        'aydin', 'arslan', 'dogan', 'kilic', 'aslan', 'cetin', 'kara', 'koc', 'kurt',
        'ozdemir', 'simsek', 'polat', 'korkmaz', 'gunes', 'yavuz', 'ozkan', 'tas',
        'avci', 'bulut', 'keskin', 'duman', 'yuksel', 'turan', 'acar', 'bozkurt',
        'tekin', 'sari', 'ates', 'atabay',
    ];

    /**
     * Metni eşleştirme için normalize eder: küçük harf + Türkçe diakritikleri
     * ASCII karşılıklarına katlar. Türkçe'nin noktalı/noktasız i ikilemi
     * (İ/I/ı) eşleştirme amacıyla tek 'i'ye indirilir.
     */
    public static function fold(string $text): string
    {
        $pre = strtr($text, ['İ' => 'i', 'I' => 'i', 'ı' => 'i']);
        $lower = mb_strtolower($pre, 'UTF-8');

        return strtr($lower, [
            'ç' => 'c', 'ş' => 's', 'ğ' => 'g', 'ö' => 'o', 'ü' => 'u',
            'â' => 'a', 'î' => 'i', 'û' => 'u',
        ]);
    }

    /**
     * Overpass (OpenStreetMap) sorgusuna gömülecek İSİM DESENİ.
     *
     * -----------------------------------------------------------------------
     * NEDEN VAR (2026-08-06, ölçümle bulundu)
     *
     * Overpass metin araması yapamaz; bir alandaki TÜM işletmeleri getirir ve
     * süzmeyi bize bırakır. Ama New York'ta binlerce lokanta var: 40 tanesini
     * rastgele çekip Türk olanına denk gelmeyi ummak işe yaramıyordu.
     * ÖLÇÜLDÜ: New York + New Jersey'de 80 lokanta çekildi, Türk çıkan SIFIR.
     * Aynı bölgede bu desenle 30 aday geldi (ABA Turkish Restaurant, Istanbul
     * Kebab House, Rumi Turkish Grill, Efes...).
     *
     * -----------------------------------------------------------------------
     * NEDEN YALNIZ KÜLTÜREL İŞARETLER (ad/soyad YOK)
     *
     * Overpass regex'i ALT-DİZE eşler, kelime değil. Sözlükteki ön adları
     * eklemek "ali" yüzünden "It-ali-an Restaurant"ı, "can" yüzünden
     * "Ameri-can Diner"ı getirirdi — samanlığı daraltmak yerine geri
     * genişletirdi.
     *
     * İş bölümü: SORGU ucuza daraltır, KARARI {@see TurkishBusinessDetector}
     * verir. Dedektör ad/soyadları ve diğer sinyalleri zaten kullanıyor ve
     * "Afghan Kebab House" gibi yanlış pozitifleri o eliyor.
     *
     * -----------------------------------------------------------------------
     * DİAKRİTİK TOLERANSI
     *
     * Sözlük ASCII-katlanmış tutulur ama OSM adları katlanmamıştır ("Döner",
     * "Türk"). Overpass tarafında fold() çalıştıramayız, o yüzden desen
     * üretilirken katlanabilen harfler karakter sınıfına açılır:
     * `doner` → `d[oö]ner`. Aksi hâlde "Kotti Berliner Döner Kebab" kaçardı.
     */
    public static function overpassIsimDeseni(): string
    {
        $terimler = array_unique(array_merge(
            self::CULTURAL_STRONG,
            self::CULTURAL_WEAK,
            // Sözlükte yok ama OSM adlarında sık: doğrudan "Turkish" ibaresi.
            ['turk', 'turkish', 'turkiye', 'turkiyem'],
        ));

        // Uzundan kısaya: alt-dize eşlemede kısa terim uzunu zaten kapsar,
        // ama sıralama deseni okunur ve kararlı tutar (test edilebilirlik).
        usort($terimler, fn (string $a, string $b) => strlen($b) <=> strlen($a) ?: strcmp($a, $b));

        return implode('|', array_map(self::diakritikSinifi(...), $terimler));
    }

    /** ASCII-katlanmış bir terimi diakritik toleranslı desene çevirir. */
    private static function diakritikSinifi(string $terim): string
    {
        $sinif = [
            'c' => '[cç]', 's' => '[sş]', 'g' => '[gğ]',
            'o' => '[oö]', 'u' => '[uü]', 'i' => '[iı]',
        ];

        return implode('', array_map(
            fn (string $harf): string => $sinif[$harf] ?? $harf,
            mb_str_split($terim),
        ));
    }

    /** Metinde Türkçe'ye özgü diakritik harf (ç/ş/ğ/ı/ö/ü ve büyükleri) var mı? */
    public static function hasDiacritics(string $text): bool
    {
        return (bool) preg_match('/[çşğıöüÇŞĞİÖÜ]/u', $text);
    }

    /**
     * Metni ASCII-katlanmış kelime dizisine ayırır (harf dışı her şey ayraç).
     *
     * @return list<string>
     */
    public static function words(string $text): array
    {
        return preg_split('/[^a-z]+/', self::fold($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
