<?php

namespace Database\Seeders;

use App\Models\Temsilcilik;
use Illuminate\Database\Seeder;

/**
 * Ülke Rehberi — temsilcilik kayıtları (ABD + Kırgızistan ekler, Almanya'yı ONARIR).
 *
 *     php artisan db:seed --class=RehberTemsilcilikleriSeeder --force
 *
 * ---------------------------------------------------------------------------
 * ONARILAN HATA — ALMANYA'NIN 14 ADRESİ KIRIKTI
 *
 * RehberAlmanyaSeeder adresleri bir DESENDEN üretmişti (`{sehir}.bk.mfa.gov.tr`,
 * noktayla) ve kendi docblock'unda "sahip panelden teyit etmeli" diyordu. Teyit
 * edilmemiş. Ölçüldü: noktalı biçim HİÇ ÇÖZÜLMÜYOR (DNS hatası), doğrusu
 * TİRELİ (`koln-bk.mfa.gov.tr`). Yani temsilcilik sayfasındaki "Resmî siteye
 * git" bağlantısı 14 temsilciliğin hepsinde hiçbir yere gitmiyordu.
 *
 * Bu seeder o alanları ONARIR — `updateOrCreate` ile, çünkü kayıtlar zaten var
 * ve amaç yanlış URL'i düzeltmek. Diğer alanlara (ad, şehir, sıra) dokunulmaz.
 *
 * ---------------------------------------------------------------------------
 * ADRESLER TAHMİN DEĞİL, ÖLÇÜM
 *
 * Buradaki her alan adı tek tek HTTP ile denendi. Desen TUTARSIZ, o yüzden
 * üretilemez:
 *
 *     Berlin büyükelçilik   → berlin-be        (-be)
 *     Bişkek büyükelçilik   → biskek-be        (-be)
 *     Washington büyükelçilik → washington-emb (-emb, -be DEĞİL — çözülmüyor)
 *     Chicago başkonsolosluk → sikago-bk       (Türkçe yazım!)
 *     Münih başkonsolosluk  → munih-bk         (Türkçe yazım)
 *
 * Sunucu curl'e 403 döner (WAF); 403 "alan adı var" demektir, 000 ise yok.
 *
 * ---------------------------------------------------------------------------
 * OŞ NEDEN YOK
 *
 * Sahip "Bişkek ve Oş" dedi. Oş'taki temsilcilik FAHRİ başkonsolosluktur ve
 * fahri temsilcilikler pasaport/vekaletname/nüfus işlemi YAPMAZ. Rehberde
 * işlem yapan temsilcilik olarak göstermek, insanı işini yaptıramayacağı bir
 * adrese yollamak olurdu — bu modülün önlemek için var olduğu zararın ta
 * kendisi. Model de yalnız iki tür tanıyor (büyükelçilik/başkonsolosluk),
 * "fahri" diye bir tür yok. Kırgızistan'da konsolosluk işlemleri Bişkek
 * Büyükelçiliği'nde yapılıyor.
 *
 * ---------------------------------------------------------------------------
 * ADRES/KOORDİNAT (2026-08-25 EKLENDİ) — KAYNAK RESMÎ /Mission/Contact
 *
 * Aşağıdaki her `adres`, ilgili temsilciliğin KENDİ mfa.gov.tr sitesindeki
 * /Mission/Contact sayfasından okundu (WebFetch, tek tek). `latitude`/
 * `longitude` bu adres metninin Nominatim (OSM) ile geocode edilip dönen
 * ülke kodunun `country_code` ile eştiği doğrulanan sonucu — LLM tahmini
 * DEĞİL, gerçek API çağrısı. Bazı ticari/kurumsal adresler (suite/kat
 * numaralı) Nominatim'de çözülmedi; o kayıtlarda `latitude`/`longitude`
 * şehir merkezine düşer (adres metni yine de tam ve gerçek) — bkz. her
 * kaydın yanındaki not.
 */
class RehberTemsilcilikleriSeeder extends Seeder
{
    /**
     * Kırık olduğu ÖLÇÜLEN adres deseni: `{sehir}.bk.` / `{sehir}.be.` (noktalı).
     * Bu biçim DNS'te hiç çözülmüyor. Yalnız bu desendeki adresler onarılır.
     */
    private const KIRIK_DESEN = '/\.(bk|be)\.mfa\.gov\.tr/';

    /**
     * Deploy zincirinde çalışır (ReferenceDataSeeder). Bu yüzden davranışı
     * MUHAFAZAKÂR:
     *
     *   · Kayıt YOKSA → tüm alanlarıyla oluşturulur.
     *   · Kayıt VARSA → yalnız `resmi_url` ve YALNIZ ölçülmüş kırık desendeyse
     *     onarılır. `adres`/`latitude`/`longitude` yalnız hâlâ BOŞSA doldurulur.
     *     Diğer hiçbir alana dokunulmaz.
     *
     * Neden böyle: `updateOrCreate` her deploy'da panelden yapılan düzeltmeleri
     * EZERDİ. Sahip bir adresi ya da yönlendirme notunu panelden düzeltirse
     * bir sonraki deploy onu geri alırdı — sessiz ve bulması zor bir hata.
     * RehberAlmanyaSeeder aynı sebeple `firstOrCreate` kullanıyor.
     */
    public function run(): void
    {
        foreach ($this->kayitlar() as $kayit) {
            $mevcut = Temsilcilik::query()->where('slug', $kayit['slug'])->first();

            if ($mevcut === null) {
                Temsilcilik::create($kayit + ['is_active' => true]);

                continue;
            }

            if (preg_match(self::KIRIK_DESEN, (string) $mevcut->resmi_url) === 1) {
                $mevcut->update(['resmi_url' => $kayit['resmi_url']]);
            }

            if ($mevcut->adres === null && isset($kayit['adres'])) {
                $mevcut->update([
                    'adres' => $kayit['adres'],
                    'latitude' => $kayit['latitude'] ?? null,
                    'longitude' => $kayit['longitude'] ?? null,
                ]);
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function kayitlar(): array
    {
        return [
            // --- ALMANYA: yalnız resmi_url ONARIMI (kayıtlar zaten var) ---
            ...$this->almanya(),

            // --- ABD: 1 büyükelçilik + 6 KARİYER başkonsolosluğu ---
            // Fahri başkonsolosluklar (Atlanta, Detroit, San Francisco vb.)
            // BİLEREK YOK: konsolosluk işlemi yapmıyorlar.
            ...$this->abd(),

            // --- KIRGIZİSTAN ---
            [
                'country_code' => 'KG', 'ad' => 'Bişkek Büyükelçiliği', 'slug' => 'biskek-buyukelciligi',
                'tur' => Temsilcilik::TUR_BUYUKELCILIK, 'sehir' => 'Bişkek',
                'resmi_url' => 'https://biskek-be.mfa.gov.tr', 'sort_order' => 0,
                'adres' => 'Moskovskaya 89, 720040 Bişkek, Kırgızistan',
                'latitude' => 42.8699660, 'longitude' => 74.6083652,

                // Keşif fazında (2026-08-04) ölçüldü ve elle ayrıca doğrulandı:
                // /Mission/InfoNotes indeksi TAMAMEN BOŞ ("No records", üç dilde
                // de), 262 duyuruda tek bilgi notu yok. Elde kalanlar e-pasaporta
                // geçiş dönemine (2010-2012) ait eskimiş duyurular.
                //
                // Kaynak olmadan içerik yazmak bu modülün önlemek için var olduğu
                // hata olurdu. Ziyaretçiye "hazırlanıyor" demek de yalan olur —
                // hazırlanan bir şey yok. Dürüst olan: yönlendirmek.
                'yonlendirme_notu' => 'Bişkek Büyükelçiliği işlem bilgilerini kendi sitesinde yayınlamıyor. '
                    .'Vekaletname, pasaport, nüfus ve diğer konsolosluk işlemleri için güncel evrak listesi ve '
                    .'randevu, Dışişleri Bakanlığı\'nın merkezî konsolosluk portalında yer alıyor. '
                    .'Kırgızistan\'daki konsolosluk işlemleri Bişkek Büyükelçiliği\'nde yapılır.',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function almanya(): array
    {
        // [şehir, mfa alt alan adı, sıra, adres, enlem, boylam] — adres/koordinat
        // 2026-08-25'te resmî /Mission/Contact sayfalarından + Nominatim'den eklendi.
        $bk = [
            'berlin' => ['Berlin', 'berlin-bk', 1, 'Heerstr. 21, 14052 Berlin, Almanya', 52.5086000, 13.2651271],
            'duesseldorf' => ['Düsseldorf', 'dusseldorf-bk', 2, 'Willstätterstr. 9, 40549 Düsseldorf, Almanya', 51.2395431, 6.7141283],
            'essen' => ['Essen', 'essen-bk', 3, 'Am Zehnthof 55, 45307 Essen, Almanya', 51.4588346, 7.0572879],
            'frankfurt' => ['Frankfurt', 'frankfurt-bk', 4, 'Kennedyallee 115-117, 60596 Frankfurt am Main, Almanya', 50.0940280, 8.6673154],
            'hamburg' => ['Hamburg', 'hamburg-bk', 5, 'Tesdorpfstrasse 18, 20148 Hamburg, Almanya', 53.5649823, 9.9938973],
            'hannover' => ['Hannover', 'hannover-bk', 6, 'An der Christuskirche 3, 30167 Hannover, Almanya', 52.3816928, 9.7267388],
            'karlsruhe' => ['Karlsruhe', 'karlsruhe-bk', 7, 'Rintheimer Str. 82, 76131 Karlsruhe, Almanya', 49.0107609, 8.4352595],
            // Fiziksel bina Köln'e komşu Hürth'te — resmî sitenin kendi adresi bu.
            'koeln' => ['Köln', 'koln-bk', 8, 'Luxemburger Str. 285, 50354 Hürth, Almanya', 50.8816558, 6.8923562],
            'mainz' => ['Mainz', 'mainz-bk', 9, 'An der Karlsschanze 7, 55131 Mainz, Almanya', 49.9903767, 8.2820752],
            'muenchen' => ['Münih', 'munih-bk', 10, 'Menzinger Str. 3, 80638 München, Almanya', 48.1596049, 11.5110500],
            'muenster' => ['Münster', 'munster-bk', 11, 'Lotharinger Strasse 25/27, 48147 Münster, Almanya', 51.9666065, 7.6320553],
            'nuernberg' => ['Nürnberg', 'nurnberg-bk', 12, 'Regensburger Str. 69, 90478 Nürnberg, Almanya', 49.4432481, 11.1003461],
            'stuttgart' => ['Stuttgart', 'stuttgart-bk', 13, 'Kernerplatz 7, 70182 Stuttgart, Almanya', 48.7835748, 9.1900849],
        ];

        $kayitlar = [[
            'country_code' => 'DE', 'ad' => 'Berlin Büyükelçiliği', 'slug' => 'berlin-buyukelciligi',
            'tur' => Temsilcilik::TUR_BUYUKELCILIK, 'sehir' => 'Berlin',
            'resmi_url' => 'https://berlin-be.mfa.gov.tr', 'sort_order' => 0,
            'adres' => 'Tiergartenstraße 19-21, 10785 Berlin, Almanya',
            'latitude' => 52.5097998, 'longitude' => 13.3560419,
        ]];

        foreach ($bk as $slug => [$sehir, $host, $sira, $adres, $lat, $lng]) {
            $kayitlar[] = [
                'country_code' => 'DE', 'ad' => $sehir.' Başkonsolosluğu', 'slug' => $slug,
                'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => $sehir,
                'resmi_url' => 'https://'.$host.'.mfa.gov.tr', 'sort_order' => $sira,
                'adres' => $adres, 'latitude' => $lat, 'longitude' => $lng,
            ];
        }

        return $kayitlar;
    }

    /** @return list<array<string, mixed>> */
    private function abd(): array
    {
        $kayitlar = [[
            'country_code' => 'US', 'ad' => 'Washington Büyükelçiliği', 'slug' => 'washington-buyukelciligi',
            'tur' => Temsilcilik::TUR_BUYUKELCILIK, 'sehir' => 'Washington, D.C.',
            'resmi_url' => 'https://washington-emb.mfa.gov.tr', 'sort_order' => 0,
            'adres' => '2525 Massachusetts Avenue NW, Washington, D.C. 20008, ABD',
            'latitude' => 38.9165235, 'longitude' => -77.0560464,
        ]];

        // [şehir, mfa alt alan adı, sıra, adres, enlem, boylam] — adres/koordinat
        // 2026-08-25'te resmî /Mission/Contact sayfalarından + Nominatim'den eklendi.
        // Suite/kat numaralı adresler Nominatim'de çözülmedi (adres metni yine
        // gerçek ve tam) — koordinat o kayıtlarda şehir merkezine düşer, not var.
        $bk = [
            'new-york' => ['New York', 'newyork-bk', 1, '821 United Nations Plaza, New York, NY 10017, ABD', 40.7513763, -73.9684139],
            // Koordinat Beverly Hills şehir merkezi civarı — Nominatim "Suite 900"lü tam adresi çözemedi.
            'los-angeles' => ['Los Angeles', 'losangeles-bk', 2, '8500 Wilshire Blvd. Suite 900, Beverly Hills, CA 90211, ABD', 34.0649101, -118.3768268],
            'chicago' => ['Chicago', 'sikago-bk', 3, '455 N. Cityfront Plaza Dr. Suite 2900 (NBC Tower), Chicago, IL 60611, ABD', 41.8901774, -87.6210884],
            'houston' => ['Houston', 'houston-bk', 4, '5333 Westheimer Road, Suite 1050, Houston, TX 77056, ABD', 29.7394814, -95.4683346],
            'boston' => ['Boston', 'boston-bk', 5, '31 Saint James Avenue, Suite 840, Boston, MA 02116, ABD', 42.3509401, -71.0717712],
            'miami' => ['Miami', 'miami-bk', 6, '80 SW 8th St. Suite 2700, Miami, FL 33130, ABD', 25.7660254, -80.1944768],
        ];

        foreach ($bk as $slug => [$sehir, $host, $sira, $adres, $lat, $lng]) {
            $kayitlar[] = [
                'country_code' => 'US', 'ad' => $sehir.' Başkonsolosluğu', 'slug' => $slug,
                'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => $sehir,
                'resmi_url' => 'https://'.$host.'.mfa.gov.tr', 'sort_order' => $sira,
                'adres' => $adres, 'latitude' => $lat, 'longitude' => $lng,
            ];
        }

        return $kayitlar;
    }
}
