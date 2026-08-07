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

    /** Terim metinde hiç geçmiyor ya da yalnız KELİME ORTASINDA geçiyor. */
    public const ESLESME_YOK = 0;

    /** Terim kelimenin başında/sonunda ama tanımadığımız bir bileşiğin parçası. */
    public const ESLESME_ZAYIF = 1;

    /** Terim tam kelime ya da bilinen bir bileşik ("Dönerhaus", "Pideci"). */
    public const ESLESME_TAM = 2;

    /**
     * Kültürel terimin ardına gelebilen BİLİNEN ekler — işletme adlarında sık.
     *
     * Türkçe ekleri (ci/cu/evi/si...) ve Almanca/İngilizce bileşik sonlarını
     * (haus/laden/house/palace...) kapsar. ASCII-katlanmış yazılır, çünkü
     * karşılaştırma fold()'lanmış metinde yapılır.
     *
     * EKSİK OLMASI TEHLİKELİ DEĞİL: listede olmayan bir ek "eşleşme yok"
     * demek değil, "ZAYIF eşleşme" demek — aday elenmez, insan onayına düşer.
     * Bu yüzden liste ihtiyatlı tutulabilir; yanılma maliyeti tek yönlü.
     *
     * @var list<string>
     */
    public const BILESIK_EKLERI = [
        // Türkçe yapım/iyelik ekleri
        'ci', 'cisi', 'cim', 'cilik', 'cu', 'cusu', 'si', 'su', 'im', 'imiz',
        'evi', 'evim', 'ler', 'lar', 'ligi', 'lik',
        // Almanca bileşik sonları
        'haus', 'hause', 'laden', 'stube', 'palast', 'treff', 'eck', 'imbiss',
        // İngilizce bileşik sonları
        'house', 'land', 'world', 'place', 'town', 'time', 'palace', 'express',
        'king', 'grill', 'hut', 'point', 'city',
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
     * Bu desen BİLEREK gevşek bırakıldı: alt-dize eşlediği için "Romantic"
     * (·manti·) gibi adları da getirir. Zararsız, çünkü karar mercii artık
     * kelime farkında ({@see terimEslesmesi}) ve onları eliyor. Deseni
     * Overpass tarafında sıkılaştırmak, servisin regex lehçesine bağımlılık
     * yaratırdı — ucuz ağ süzgeci ile pahalı doğru karar ayrı kalsın.
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

    /**
     * Bir kültürel terimin metinde NASIL geçtiğini söyler.
     *
     * -----------------------------------------------------------------------
     * NEDEN VAR (2026-08-07, canlı ölçümden sonra)
     *
     * Tespit motoru bu terimleri `str_contains` ile arıyordu — yani ALT-DİZE.
     * Alt-dize eşlemesi kelime tanımaz ve sözlükteki kısa terimler İngilizce
     * kelimelerin İÇİNDE geçiyor:
     *
     *   "Ro·manti·c Restaurant"  → `manti`  → 0.6 puan → KESİN TÜRK (yanlış)
     *   "The Lon·doner"          → `doner`  → 0.6 puan → KESİN TÜRK (yanlış)
     *   "Pizza Ra·pide"          → `pide`   → 0.6 puan → KESİN TÜRK (yanlış)
     *
     * Üçü de tek bir alt-dizeyle eşiği (0.6) tam olarak geçiyordu; yani insan
     * onayına bile düşmeden "kesin Türk" havuzuna giriyorlardı.
     *
     * -----------------------------------------------------------------------
     * NEDEN DÜZ "TAM KELİME" YETMEZ
     *
     * En büyük hedef pazar Almanya ve orada adlar BİTİŞİK yazılır:
     * "Dönerhaus", "Kebaphaus", "Baklavaland". Türkçe de eklidir: "Pideci",
     * "Baklavacı", "Kebapçım". Tam-kelime kuralı bunların hepsini kaybederdi —
     * arzın darboğaz olduğu bir üründe aday kaybı pahalı.
     *
     * Bu yüzden üç kademe var:
     *   TAM   — tam kelime, ya da kelime başı + BİLİNEN ek ("Dönerhaus")
     *   ZAYIF — kelime başı/sonu ama ek tanınmıyor ("Pasadena", "Sultana",
     *           "Stadtdöner"): kanıt sayılır ama tek başına yetmez → onaya
     *   YOK   — harfler İKİ YANDA da var, terim kelimenin tam ortasında
     *           ("Romantic"): bu bir kanıt değil, tesadüf
     *
     * @param  string  $folded  fold() geçmiş metin
     */
    public static function terimEslesmesi(string $folded, string $terim): int
    {
        $enIyi = self::ESLESME_YOK;
        $uzunluk = strlen($terim);
        $konum = 0;

        while (($konum = strpos($folded, $terim, $konum)) !== false) {
            $oncesiHarf = $konum > 0 && ctype_alpha($folded[$konum - 1]);

            preg_match('/^[a-z]*/', substr($folded, $konum + $uzunluk), $m);
            $devam = $m[0];

            if (! $oncesiHarf && ($devam === '' || in_array($devam, self::BILESIK_EKLERI, true))) {
                return self::ESLESME_TAM; // daha iyisi olamaz, erken çık
            }

            // Kelime başı (tanınmayan devam) VEYA kelime sonu (bileşiğin ikinci
            // parçası) → zayıf kanıt. İkisi de değilse terim kelime ortasında.
            if (! $oncesiHarf || $devam === '') {
                $enIyi = self::ESLESME_ZAYIF;
            }

            $konum++;
        }

        return $enIyi;
    }

    /**
     * Terim listesinde ilk anlamlı eşleşmeyi bulur — TAM olanı ZAYIF'a tercih eder.
     *
     * @param  list<string>  $terimler
     * @return array{0: string|null, 1: int} [eşleşen terim, eşleşme kademesi]
     */
    public static function ilkEslesme(string $folded, array $terimler): array
    {
        $zayifTerim = null;

        foreach ($terimler as $terim) {
            $kademe = self::terimEslesmesi($folded, $terim);

            if ($kademe === self::ESLESME_TAM) {
                return [$terim, self::ESLESME_TAM];
            }

            if ($kademe === self::ESLESME_ZAYIF && $zayifTerim === null) {
                $zayifTerim = $terim;
            }
        }

        return $zayifTerim !== null
            ? [$zayifTerim, self::ESLESME_ZAYIF]
            : [null, self::ESLESME_YOK];
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
