<?php

namespace App\Support\Growth;

/**
 * Bir keşif kaydının GERÇEK ülkesini adres ve alan adı uzantısından çıkarır.
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * `outreach_targets.country` uzun süre SORGUNUN ülkesini taşıdı: "KZ'de ara"
 * denince bulunan her işletme KZ yazıldı — Kartal/İstanbul'daki terzi de,
 * Olpe/Almanya'daki oto servisi de. Google Places tarafında kaynak düzeltildi
 * (`countryFromComponents()`, 2026-08-07) ama havuzdaki ESKİ kayıtlar
 * düzeltilmeden kaldı.
 *
 * Bu kozmetik bir hata değil: `RegionPolicy` ticari e-postayı AB-27 ve
 * Türkiye'ye KAPATIYOR ve kararı bu alana bakarak veriyor. Alan yanlışsa kapı
 * yanlış yerde durur — Türkiye'deki bir işletme "KZ" yazdığı için
 * "gönderilebilir" sayılır. Ölçüldü (2026-08-08): adresi `NNNNN İlçe/İl`
 * kalıbındaki 25 kaydın 18'i KG/KZ/UZ olarak kayıtlıydı.
 *
 * ---------------------------------------------------------------------------
 * KAPALI DÜŞER (fail-closed)
 *
 * Kanıt yoksa `null` döner ve ÇAĞIRAN TARAF KAYDA DOKUNMAZ. "Bilmiyorum" ile
 * "yanlış tahmin" arasındaki fark burada hayati: yanlış tahmin, kapalı olması
 * gereken bir kapıyı AÇABİLİR.
 *
 * Bu yorum bir kez "ya da sinyaller çelişiyorsa null döner" diyordu. Kod
 * çelişkiyi HİÇ hesaplamıyor — ilk konuşan sinyali alıp döner. Yapılmayan bir
 * korumayı anlatan yorum, okuyanı olmayan bir güvenceye yaslandırır; cümle
 * kaldırıldı. Çelişki sıralamayla yönetiliyor: adres > alan adı > e-posta.
 *
 * Aynı sebeple sıralama da tesadüf değil — ADRES her zaman alan adından önce
 * gelir. GDPR'ın sorduğu şey işletmenin fiziksel olarak nerede kurulu olduğu;
 * `.com` alan adı bunu söylemez, "34876 Kartal/İstanbul" söyler. Türkiye'de
 * kurulu bir şirket `.kz` alan adı alabilir, ya da tersi.
 */
final class UlkeTespiti
{
    /**
     * ABD eyalet kısaltmaları — `NJ 07110` kalıbı için.
     *
     * Kalıp ŞART: çıplak iki harf çok tehlikeli ("Ni̇lüfer" içinde de "NI"
     * geçer). Yalnız "eyalet + 5 haneli ZIP" birlikte sayılır.
     */
    private const US_EYALETLERI = 'A[LKZR]|C[AOT]|D[CE]|FL|GA|HI|I[ADLN]|K[SY]|LA|M[ADEINOST]|N[CDEHJMVY]|O[HKR]|PA|RI|S[CD]|T[NX]|UT|V[AT]|W[AIVY]';

    /**
     * Türkiye il adları — `NNNNN İlçe/İl` kalıbının İL tarafı.
     *
     * İl listesi olmadan çıplak "5 hane + eğik çizgi" kalıbı yetmez; başka
     * ülkelerde de benzer yazımlar olabilir. Küçük harfe çevrilmiş, Türkçe
     * karakterler olduğu gibi (mb_strtolower ile karşılaştırılır).
     */
    private const TR_ILLERI = [
        'adana', 'adıyaman', 'afyonkarahisar', 'ağrı', 'aksaray', 'amasya', 'ankara',
        'antalya', 'ardahan', 'artvin', 'aydın', 'balıkesir', 'bartın', 'batman',
        'bayburt', 'bilecik', 'bingöl', 'bitlis', 'bolu', 'burdur', 'bursa',
        'çanakkale', 'çankırı', 'çorum', 'denizli', 'diyarbakır', 'düzce', 'edirne',
        'elazığ', 'erzincan', 'erzurum', 'eskişehir', 'gaziantep', 'giresun',
        'gümüşhane', 'hakkari', 'hatay', 'ığdır', 'isparta', 'istanbul', 'i̇stanbul',
        'izmir', 'i̇zmir', 'kahramanmaraş', 'karabük', 'karaman', 'kars', 'kastamonu',
        'kayseri', 'kilis', 'kırıkkale', 'kırklareli', 'kırşehir', 'kocaeli', 'konya',
        'kütahya', 'malatya', 'manisa', 'mardin', 'mersin', 'muğla', 'muş', 'nevşehir',
        'niğde', 'ordu', 'osmaniye', 'rize', 'sakarya', 'samsun', 'şanlıurfa', 'siirt',
        'sinop', 'sivas', 'şırnak', 'tekirdağ', 'tokat', 'trabzon', 'tunceli', 'uşak',
        'van', 'yalova', 'yozgat', 'zonguldak',
    ];

    /**
     * Yerel şehir yazımları → ülke.
     *
     * Katalog ({@see GrowthCatalog::CITIES}) tek kaynaktır ve şehir listesi
     * oradan gelir; burada YALNIZCA katalogdaki adın veride başka türlü yazılan
     * biçimleri var. Hepsi havuzdan ÖLÇÜLDÜ, tahmin edilmedi: "Toshkent" 27
     * kayıtta, "Samarqand viloyati" 14 kayıtta geçiyor — katalogda ise
     * "Tashkent"/"Samarkand" yazıyor. Eşleme olmasaydı bu kayıtların adresi
     * tanınmaz, kural alan adı uzantısına düşerdi ve `.com.tr` taşıyan bir
     * Taşkent işletmesi "Türkiye" sanılırdı (ölçüldü: #217).
     */
    private const SEHIR_YAZIMLARI = [
        // Yerel/kiril yazımlar — katalogdaki ad başka türlü yazılmış.
        'toshkent' => 'UZ',
        'samarqand' => 'UZ',
        'bişkek' => 'KG',
        'бишкек' => 'KG',
        'алматы' => 'KZ',
        'шымкент' => 'KZ',
        'sayram' => 'KZ',
        'bakı' => 'AZ',
        'baki' => 'AZ',
        'doha' => 'QA',
        'mathaf' => 'QA',
        'karachi' => 'PK',

        /*
         * AB ŞEHİRLERİ — HUKUKİ KAPININ ARKASINDA OLMASI GEREKENLER.
         *
         * Bunlar keşif kataloğunda YOK; Overpass taraması yarıçap dışına
         * taşarak getirmiş. Posta kodları da yok, yani hiçbir kalıba uymuyor
         * ve kayıt "KG" olarak duruyordu — yani AB'deki bir işletme
         * gönderilebilir görünüyordu. Ölçüldü (2026-08-08): Vir/Zadar (#11,
         * #12) Hırvatistan, Kaunas/Prienai (#14, #90) Litvanya.
         *
         * Liste tahminle değil havuzdan ÇIKARILDI; yeni bir AB şehri
         * sızarsa aynı yöntemle buraya eklenir.
         */
        'vir' => 'HR',
        'zadar' => 'HR',
        'kaunas' => 'LT',
        'prienai' => 'LT',

        /*
         * ABD ŞEHİRLERİ — katalogda olmayan ama havuzda geçenler.
         *
         * Katalog ABD için yalnız 5 şehir tarıyor (New York, Clifton NJ,
         * Los Angeles, Chicago, Houston) ama Overpass'ın `around` yarıçapı
         * komşu şehirleri de getiriyor: Brooklyn, Paterson, Pasadena…
         * Bu kayıtlarda posta kodu YOK, yani "eyalet + ZIP" kalıbı tutmuyor
         * ve kural hiçbir şey döndüremiyordu.
         *
         * NEDEN ÖNEMLİ: bunlar "ülkesi bilinmiyor" kovasına düşüyordu ve
         * aşağıdaki kapalı-düşen kural onları kapatacaktı — oysa dokuzu da
         * apaçık ABD'de (ölçüldü 2026-08-08: #477 Brooklyn, #505 Paterson,
         * #512 Beverly Hills…). Bilinmeyeni kapatmak doğru; BİLİNEBİLİRİ
         * bilinmiyor sayıp kapatmak yalnızca hedef kaybı.
         */
        'brooklyn' => 'US',
        'paterson' => 'US',
        'pasadena' => 'US',
        'beverly hills' => 'US',
        'cedar grove' => 'US',
        'west caldwell' => 'US',
        'east rutherford' => 'US',
    ];

    /**
     * Alan adı uzantısı → ülke. YALNIZCA ülkeye bağlı uzantılar; `.com`/`.net`
     * hiçbir şey söylemez ve listede YOKTUR.
     */
    private const TLD_ULKE = [
        '.com.tr' => 'TR', '.org.tr' => 'TR', '.tr' => 'TR',
        '.com.au' => 'AU', '.co.uk' => 'GB', '.org.uk' => 'GB',
        '.de' => 'DE', '.at' => 'AT', '.nl' => 'NL', '.fr' => 'FR', '.be' => 'BE',
        '.kz' => 'KZ', '.kg' => 'KG', '.uz' => 'UZ', '.az' => 'AZ', '.ru' => 'RU',
        '.qa' => 'QA', '.ae' => 'AE', '.ca' => 'CA', '.pk' => 'PK',
    ];

    /**
     * Adres + site adresinden ülke kodu; çıkaramazsa null.
     *
     * @param  string|null  $adres  `outreach_targets.city` (serbest metin, çoğu
     *                              zaman tam adres parçası taşır)
     */
    public static function tespit(?string $adres, ?string $site = null, ?string $eposta = null): ?string
    {
        $adres = trim((string) $adres);

        // 1) ADRES — en güvenilir sinyal, alan adından ÖNCE gelir.
        if ($u = self::adrestenUlke($adres)) {
            return $u;
        }

        // 2) Alan adı uzantısı — adres bir şey söylemediğinde.
        if ($u = self::tldUlke(self::alanAdi($site))) {
            return $u;
        }

        // 3) E-posta alan adı — sitesi olmayan kayıtlar için son çare.
        if ($eposta && str_contains($eposta, '@')) {
            if ($u = self::tldUlke(mb_strtolower(trim(explode('@', $eposta)[1] ?? '')))) {
                return $u;
            }
        }

        return null;   // KANIT YOK → çağıran taraf kayda dokunmaz
    }

    /** Adres metninden ülke; kalıp tutmazsa null. */
    public static function adrestenUlke(string $adres): ?string
    {
        if ($adres === '') {
            return null;
        }

        // ABD: "NJ 07110", "PA 19053" — eyalet VE ZIP birlikte.
        if (preg_match('/\b('.self::US_EYALETLERI.')\s+\d{5}\b/', $adres)) {
            return 'US';
        }

        // Türkiye: "34876 Kartal/İstanbul" — 5 hane + ilçe/İL, İL listeden.
        if (preg_match('/\b\d{5}\s+[^\/]+\/(.+)$/u', $adres, $m)) {
            $il = mb_strtolower(trim($m[1]), 'UTF-8');
            if (in_array($il, self::TR_ILLERI, true)) {
                return 'TR';
            }
        }

        // Avustralya: "Dapto NSW 2530" — eyalet kısaltması + 4 hane.
        if (preg_match('/\b(NSW|VIC|QLD|WA|SA|TAS|ACT|NT)\s+\d{4}\b/', $adres)) {
            return 'AU';
        }

        /*
         * İRLANDA Eircode: "N91 EC80" — harf + 2 rakam, boşluk, 4 karakter.
         * BK kalıbından ÖNCE denenmeli, aksi halde BK deseni bunları da yutar.
         *
         * İkisi de gönderim beyaz listesinde değil, yani doğru tespit
         * doğrudan kapıyı kapatır. Kalıpsız hâlde bu kayıtlar "KG" olarak
         * duruyor ve gönderilebilir görünüyordu (#79, #80).
         */
        if (preg_match('/\b[A-Z]\d{2}\s?[A-Z0-9]{4}\b/', $adres)) {
            return 'IE';
        }

        // BİRLEŞİK KRALLIK: "BT31 9DW", "DH8 5QG", "CT9 2PN", "YO26 5RU".
        // Boşluk ZORUNLU tutuluyor — boşluksuz hâli ABD/başka biçimlerle
        // karışabilir; havuzdaki BK adreslerinin hepsinde boşluk var.
        if (preg_match('/\b[A-Z]{1,2}\d{1,2}[A-Z]?\s\d[A-Z]{2}\b/', $adres)) {
            return 'GB';
        }

        // ŞEHİR ADI — posta kalıplarından SONRA, alan adından ÖNCE.
        //
        // Sıra kritikti: bu adım olmadan "Astana 020000" hiçbir kalıba
        // uymuyordu ve kural alan adı uzantısına düşüyordu; Yunus Emre
        // Enstitüsü'nün ASTANA şubesi `yee.org.tr` yüzünden "Türkiye" çıkıyordu
        // (ölçüldü: #157). Aynı hata Toshkent (#217) ve Şımkent (#172) için de
        // vardı. İşletmenin fiziksel yeri alan adından değil adresten okunur.
        if ($u = self::sehirdenUlke($adres)) {
            return $u;
        }

        return null;
    }

    /**
     * Adres metninde geçen bilinen bir şehir adından ülke; yoksa null.
     *
     * Şehir listesi {@see GrowthCatalog::CITIES}'ten gelir — keşif hangi
     * şehirleri tarıyorsa tespit de onları tanır, ikisi birlikte büyür.
     * Katalog "Clifton, New Jersey" gibi nitelenmiş adlar taşıyabildiği için
     * virgülden önceki parça da ayrıca eşleştirilir.
     */
    public static function sehirdenUlke(string $adres): ?string
    {
        $metin = mb_strtolower($adres, 'UTF-8');

        $harita = self::SEHIR_YAZIMLARI;

        foreach (GrowthCatalog::CITIES as $ulke => $sehirler) {
            foreach ($sehirler as $sehir) {
                $ad = mb_strtolower(trim(explode(',', $sehir)[0]), 'UTF-8');
                if ($ad !== '') {
                    $harita[$ad] = $ulke;
                }
            }
        }

        foreach ($harita as $ad => $ulke) {
            // Sözcük sınırı ŞART: çıplak alt-dize araması "Osh"u "Oshkosh"
            // içinde bulurdu. \b Türkçe/aksanlı harflerde güvenilir değil, bu
            // yüzden sınır elle tarif ediliyor (harf/rakam olmayan ya da metin ucu).
            if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($ad, '/').'(?![\p{L}\p{N}])/u', $metin)) {
                return $ulke;
            }
        }

        return null;
    }

    /** URL'den ana makine adı (küçük harf, www yok); çıkaramazsa boş dize. */
    public static function alanAdi(?string $site): string
    {
        $host = parse_url((string) $site, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return '';
        }

        return preg_replace('/^www\./', '', mb_strtolower($host)) ?? '';
    }

    /**
     * Alan adının ülke uzantısı; ülkeye bağlı değilse null.
     *
     * PLATFORM ALAN ADLARI SAYILMAZ: instagram.com, tripadvisor.com, wixsite,
     * square.site gibi adresler işletmenin değil platformun; uzantıları da
     * zaten `.com` olduğu için listeye düşmezler — ama `.ru`/`.kz` uzantılı
     * rehber siteleri (restolife.kz) işletmenin ülkesini DOĞRU söylediğinden
     * elenmesine gerek yok.
     */
    public static function tldUlke(string $alanAdi): ?string
    {
        if ($alanAdi === '') {
            return null;
        }

        // Uzun uzantı önce: ".com.tr" ".tr"den önce denenmeli.
        $uzantilar = self::TLD_ULKE;
        uksort($uzantilar, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($uzantilar as $uzanti => $ulke) {
            if (str_ends_with($alanAdi, $uzanti)) {
                return $ulke;
            }
        }

        return null;
    }
}
