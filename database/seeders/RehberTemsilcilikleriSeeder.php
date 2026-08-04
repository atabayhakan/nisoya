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
 * ADRES (sokak) ALANI NEDEN BOŞ
 *
 * Adresleri ikincil kaynaklardan (haber/rehber siteleri) alıp yazmak,
 * doğrulanmamış veri yayınlamak olurdu. Keşif fazında her temsilciliğin kendi
 * /Mission/Contact sayfasından okunup doldurulacak.
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
     *     onarılır. Diğer hiçbir alana dokunulmaz.
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
        $bk = [
            'berlin' => ['Berlin', 'berlin-bk', 1],
            'duesseldorf' => ['Düsseldorf', 'dusseldorf-bk', 2],
            'essen' => ['Essen', 'essen-bk', 3],
            'frankfurt' => ['Frankfurt', 'frankfurt-bk', 4],
            'hamburg' => ['Hamburg', 'hamburg-bk', 5],
            'hannover' => ['Hannover', 'hannover-bk', 6],
            'karlsruhe' => ['Karlsruhe', 'karlsruhe-bk', 7],
            'koeln' => ['Köln', 'koln-bk', 8],
            'mainz' => ['Mainz', 'mainz-bk', 9],
            'muenchen' => ['Münih', 'munih-bk', 10],
            'muenster' => ['Münster', 'munster-bk', 11],
            'nuernberg' => ['Nürnberg', 'nurnberg-bk', 12],
            'stuttgart' => ['Stuttgart', 'stuttgart-bk', 13],
        ];

        $kayitlar = [[
            'country_code' => 'DE', 'ad' => 'Berlin Büyükelçiliği', 'slug' => 'berlin-buyukelciligi',
            'tur' => Temsilcilik::TUR_BUYUKELCILIK, 'sehir' => 'Berlin',
            'resmi_url' => 'https://berlin-be.mfa.gov.tr', 'sort_order' => 0,
        ]];

        foreach ($bk as $slug => [$sehir, $host, $sira]) {
            $kayitlar[] = [
                'country_code' => 'DE', 'ad' => $sehir.' Başkonsolosluğu', 'slug' => $slug,
                'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => $sehir,
                'resmi_url' => 'https://'.$host.'.mfa.gov.tr', 'sort_order' => $sira,
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
        ]];

        $bk = [
            'new-york' => ['New York', 'newyork-bk', 1],
            'los-angeles' => ['Los Angeles', 'losangeles-bk', 2],
            'chicago' => ['Chicago', 'sikago-bk', 3],
            'houston' => ['Houston', 'houston-bk', 4],
            'boston' => ['Boston', 'boston-bk', 5],
            'miami' => ['Miami', 'miami-bk', 6],
        ];

        foreach ($bk as $slug => [$sehir, $host, $sira]) {
            $kayitlar[] = [
                'country_code' => 'US', 'ad' => $sehir.' Başkonsolosluğu', 'slug' => $slug,
                'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => $sehir,
                'resmi_url' => 'https://'.$host.'.mfa.gov.tr', 'sort_order' => $sira,
            ];
        }

        return $kayitlar;
    }
}
