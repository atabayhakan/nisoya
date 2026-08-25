<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Temsilcilik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Dünya geneli temsilcilik İSKELETİ (docs/03-buyume-fikirleri.md, öneri 1,
 * 2026-08-19 — "her ülkeye en azından iskelet kaydı").
 *
 * ---------------------------------------------------------------------------
 * ÖLÇÜLEN BOŞLUK
 *
 * Production'da 57 aktif ülkenin temsilcilik kaydı HİÇ yoktu — bu yüzden
 * Rehber'in ülke sayfası (`/{ulke}`) "hazırlanıyor" diyor ve RehberDogalDilArama
 * (Nisoya AI arama) o ülkelerde önerecek TEK BİR KAYIT bile bulamıyordu.
 * Bu seeder o 57'den 55'ini (Kıbrıs Rum Kesimi ve KKTC BİLEREK dışarıda —
 * ikisi de ayrı, dikkatli bir karar gerektiriyor) gerçek büyükelçilik
 * şehriyle dolduruyor.
 *
 * ---------------------------------------------------------------------------
 * KAPSAM BİLEREK DAR — İŞLEM DETAYI YOK
 *
 * Bu, Almanya/ABD gibi işlem-seviyesi (vekaletname, pasaport...) doldurulmuş
 * bir seeder DEĞİL. Yalnız temsilciliğin VAR OLDUĞUNU ve NEREDE olduğunu
 * kaydediyor — `yonlendirme_notu` ile (Bişkek'teki desenin GENELLEŞTİRİLMİŞ
 * hâli, ama Bişkek'in "bu temsilcilik kendi sitesinde yayınlamıyor" gibi
 * SPESİFİK bir iddiasını TEKRARLAMIYOR — o iddia yalnız Bişkek için
 * doğrulanmıştı, 55 ofisin her biri için doğrulanmadı). Amaç: (a) Rehber
 * ülke sayfası artık "hazırlanıyor" yerine gerçek bir temsilcilik gösterir,
 * (b) Nisoya AI arama artık her ülkede önerecek bir şey bulur (bkz.
 * RehberDogalDilArama::temsilciklerleBul), (c) Google'da en azından
 * "[ülke] Türk büyükelçiliği" türü temel aramalar için gerçek bir sayfa
 * vardır.
 *
 * ---------------------------------------------------------------------------
 * KAYNAK (2026-08-19, WebSearch ile doğrulandı — özet/tahmin değil)
 *
 * Türkiye Cumhuriyeti Dışişleri Bakanlığı'nın resmî temsilcilik listesi
 * (mfa.gov.tr) + Türkçe Vikipedi'nin "Türkiye'nin diplomatik temsilcilikleri
 * listesi" sayfası, birkaç ülke için mfa.gov.tr alt alan adlarıyla (ör.
 * kanberra-be.mfa.gov.tr, wellington.be.mfa.gov.tr, atina-be.mfa.gov.tr,
 * ljubljana-be.mfa.gov.tr) çapraz doğrulandı.
 *
 * ÜÇ MİNİK DEVLET AYRI: San Marino, Andorra, Lihtenştayn'ın KENDİ
 * büyükelçiliği yok — komşu ülkedeki büyükelçilik oradan akredite (Roma,
 * Madrid, Bern). `sehir`/`ad` bunu AÇIKÇA yazıyor, gerçek olmayan bir
 * "kendi büyükelçiliği var" izlenimi vermiyor.
 *
 * ---------------------------------------------------------------------------
 * BİLEREK DIŞARIDA: KIBRIS (RUM KESİMİ) VE KKTC
 *
 * İkisi de bu partiden ayrı tutuldu — siyasi çerçeve bu oturumda daha önce
 * (ülke listesi eklenirken) sahiple birlikte tek tek karara bağlanmıştı;
 * aynı özen burada da gerekiyor, tek taraflı yazılmadı.
 */
class RehberDunyaTemsilcilikIskeletiSeeder extends Seeder
{
    private const YONLENDIRME_NOTU = 'Bu ülkedeki konsolosluk işlemleri (vekaletname, pasaport, nüfus kayıtları ve diğerleri) için güncel evrak listesi ve randevu bilgisi, Dışişleri Bakanlığı\'nın merkezî konsolosluk portalında yer alır.';

    /**
     * Adres/koordinat (2026-08-25 EKLENDİ, docs/plans/2026-08-25-…).
     *
     * Her adres, ilgili büyükelçiliğin KENDİ mfa.gov.tr /Mission/Contact
     * sayfasından okundu (WebFetch, ülke başına ayrı araştırma). Koordinat
     * Nominatim (OSM) ile geocode edilip dönen ülke kodunun eştiği doğrulandı
     * — LLM tahmini DEĞİL, gerçek API çağrısı. Bazı adresler (zon/blok/kat
     * numaralı ticari-tipte, Katar/BAE/Kuveyt/Rusya/Endonezya/İsrail/
     * Arjantin/Tayland/Uruguay/Özbekistan) Nominatim'de tam sokak düzeyinde
     * çözülmedi; koordinat o kayıtlarda ŞEHİR MERKEZİNE düşer (adres metni
     * yine de tam ve gerçek, yalnız harita PİNİ yaklaşık). İzlanda (IS)
     * için güvenilir bir adres bulunamadı — bilerek BOŞ bırakıldı, uydurma
     * yapılmadı.
     *
     * `resmi_url` bu turda EKLENMEDİ: iki bağımsız araştırma AYNI şehir için
     * (Madrid) çelişen alt alan adı iddia etti (`madrid-emb` vs `madrid-be`)
     * — mevcut seeder'ın kendi belgelediği "desen tutarsız, WAF 403 ile
     * doğrulanmalı" kuralı burada da geçerli; doğrulanmamış bir resmî site
     * linki, linksiz olmaktan daha kötü bir güven kaybı olurdu.
     *
     * @var array<string, array{0: string, 1: float, 2: float}>
     */
    private const ADRESLER = [
        'AD' => ["Calle Rafael Calvo 18, 2º A-B, 28010 Madrid, İspanya (Andorra'ya akredite büyükelçilik)", 40.4338796, -3.6941337],
        'AE' => ['Embassy Area, Plot W59-02/1, No:34, Abu Dabi, Birleşik Arap Emirlikleri', 24.4538352, 54.3774014],
        'AR' => ['11 de Septiembre 1382, Buenos Aires, Arjantin', -34.6161231, -58.4356212],
        'AT' => ['Prinz-Eugen-Straße 40, 1040 Viyana, Avusturya', 48.1930383, 16.3781323],
        'AU' => ['6 Moonah Place, Yarralumla ACT 2600, Avustralya', -35.3077630, 149.1184377],
        'AZ' => ['Samed Vurgun küçəsi 134, Bakü 1022, Azerbaycan', 40.3888172, 49.8380496],
        'BE' => ['Rue Montoyer 4 / Montoyerstraat 4, 1000 Brüksel, Belçika', 50.8418892, 4.3675260],
        'BH' => ['Villa No:924, Road No:3219, Bu Ashira, Block 332, Manama, Bahreyn', 26.2120521, 50.5937768],
        'CA' => ['197 Wurtemburg Street, Ottawa, ON K1N 8L9, Kanada', 45.4348359, -75.6753974],
        'CH' => ['Lombachweg 33, 3006 Bern, İsviçre', 46.9382347, 7.4648776],
        'CL' => ['Monseñor Sotero Sanz 55/71, Providencia, Santiago, Şili', -33.4232508, -70.6153521],
        'CZ' => ['Sibiřské náměstí 730/1, 160 00 Bubeneč, Prag 6, Çekya', 50.1044355, 14.4053681],
        'DK' => ['Rosbaeksvej 15, 2100 Kobenhavn Ø, Danimarka', 55.7224710, 12.5731860],
        'EE' => ['Narva mnt 30/1, 10120 Tallinn, Estonya', 59.4378119, 24.7704449],
        'ES' => ['Calle Rafael Calvo 18, 2ºA-B, 28010 Madrid, İspanya', 40.4338796, -3.6941337],
        'FI' => ['Puistokatu 1B A3, 00140 Helsinki, Finlandiya', 60.1585599, 24.9539940],
        'FR' => ['16, Avenue de Lamballe, 75016 Paris, Fransa', 48.8545621, 2.2805059],
        'GB' => ['43 Belgrave Square, London SW1X 8PA, Birleşik Krallık', 51.4999381, -0.1522424],
        'GR' => ["Vasileos Georgiou B' No:11, 10674 Atina, Yunanistan", 37.9731412, 23.7420860],
        'HR' => ['Andrije Hebranga 32-34, 10000 Zagreb, Hırvatistan', 45.8091895, 15.9730101],
        'HU' => ['Andrássy út 123, 1062 Budapeşte, Macaristan', 47.5133891, 19.0763005],
        'ID' => ['Jl. H.R. Rasuna Said Kav. 1, Kuningan, Jakarta 12950, Endonezya', -6.2838694, 106.8048297],
        'IE' => ['8 Raglan Road, Ballsbridge, Dublin 4, D04 EA36, İrlanda', 53.3301156, -6.2376994],
        'IL' => ['Mapu Sokak No:16, 6357713 Tel Aviv-Yafo, İsrail', 32.0852997, 34.7818064],
        'IT' => ['Via Palestro 28, 00185 Roma, İtalya', 41.9064565, 12.5027635],
        'JP' => ['2-33-6 Jingumae, Shibuya-ku, Tokyo, 150-0001, Japonya', 35.6731903, 139.7084267],
        'KH' => ['No:1&3, Street 254, Sangkat Chaktomuk, Khan Daun Penh, Phnom Penh, 12207, Kamboçya', 11.5586532, 104.9276771],
        'KR' => ['40 Dongho-ro 20 Na-gil, Jung-gu (Jangchoong-dong 63-2), Seul, 04606, Güney Kore', 37.5598335, 127.0094013],
        'KW' => ['Elçilik Bölgesi, Plot 16, Istiqlal Caddesi, Daiyah Blok 5, Kuveyt, 35301', 29.3796532, 47.9734174],
        'KZ' => ['Taşenov Caddesi No: 3, Astana, 010000, Kazakistan', 51.1517348, 71.4349373],
        'LI' => ["Lombachweg 33, 3006 Bern, İsviçre (Lihtenştayn'a akredite büyükelçilik)", 46.9382347, 7.4648776],
        'LT' => ['Didžioji g. 37, LT-01128 Vilnius, Litvanya', 54.6771511, 25.2875252],
        'LU' => ['49, rue Siggy vu Lëtzebuerg, L-1933 Lüksemburg', 49.6248477, 6.1077601],
        'LV' => ['A. Pumpura iela 2, LV-1010 Riga, Letonya', 56.9562923, 24.1115095],
        'ME' => ['Radosava Burića b.b., Zabjelo, 81000 Podgorica, Karadağ', 42.4234447, 19.2515956],
        'MT' => ['35, Sir Luigi Preziosi Square, FRN-1154 Floriana, Malta', 35.8897705, 14.5064764],
        'NL' => ['Jan Evertstraat 15, 2514 BS Den Haag (Lahey), Hollanda', 52.0846181, 4.3151993],
        'NO' => ['Halvdan Svartes gate 5, 0268 Oslo, Norveç', 59.9208040, 10.6953730],
        'NZ' => ['Level 12, 15-17 Murphy Street, Thorndon, Wellington 6011, Yeni Zelanda', -41.2749231, 174.7792163],
        'OM' => ['Way No: 3042, Building No: 3270, Shati Al Qurum, Maskat, Umman', 23.6219315, 58.4733214],
        'PL' => ['Ul. Rakowiecka 19, 02-517 Warszawa, Polonya', 52.2088629, 21.0167785],
        'PT' => ['Avenida das Descobertas, 22, Restelo, 1400-092 Lizbon, Portekiz', 38.7071741, -9.2154920],
        'QA' => ['Zone 66, Street 905, Building 149, Doha, Katar', 25.2882114, 51.5432062],
        'RU' => ['7. Rostovskiy pereulok No:12, 115127 Moskova, Rusya Federasyonu', 55.6255780, 37.6063916],
        'SA' => ['Diplomatic Quarter, Abdullah Ibn Hudhafah As Sahmi St. No:8606, Riyad, 12523, Suudi Arabistan', 24.6771034, 46.6251452],
        'SE' => ['Dag Hammarskjölds Väg 20, 115 27 Stockholm, İsveç', 59.3329687, 18.1037283],
        'SG' => ['2 Shenton Way, SGX Centre Tower 1, No: 10-03, Singapur 068804', 1.2796389, 103.8500381],
        'SI' => ['Livarska ulica 4, 1000 Ljubljana, Slovenya', 46.0605743, 14.5062993],
        'SK' => ['Holubyho 11, 811 03 Bratislava, Slovakya', 48.1497897, 17.0952503],
        'SM' => ["Via Palestro 28, 00185 Roma, İtalya (San Marino'ya akredite büyükelçilik)", 41.9064565, 12.5027635],
        'TH' => ['1 South Sathorn Road, Empire Tower, Tower I, 16th Floor, Bangkok 10120, Tayland', 13.7524938, 100.4935089],
        'TM' => ['Göroglu Köçesi 59, 744000 Aşkabat, Türkmenistan', 38.0419474, 58.1884890],
        'UY' => ['Sanlúcar 1478, 11500 Montevideo, Uruguay', -34.9058916, -56.1913095],
        'UZ' => ['Akademik Yahya Gulyamov Caddesi No: 87, 100047 Taşkent, Özbekistan', 41.3123363, 69.2787079],
        // IS (Reykjavik): güvenilir kaynaktan adres bulunamadı — bilerek boş.
    ];

    /**
     * [ülke kodu, şehir, akredite eden şehir (varsa)].
     *
     * Üçüncü eleman yalnız San Marino/Andorra/Lihtenştayn için dolu —
     * "kendi büyükelçiliği yok, X şehrindeki büyükelçilikten akredite"
     * gerçeğini `ad` alanına taşımak için.
     *
     * @var list<array{0: string, 1: string, 2: ?string}>
     */
    private const ULKELER = [
        ['NL', 'Lahey', null],
        ['GB', 'Londra', null],
        ['FR', 'Paris', null],
        ['AT', 'Viyana', null],
        ['BE', 'Brüksel', null],
        ['CH', 'Bern', null],
        ['SE', 'Stockholm', null],
        ['NO', 'Oslo', null],
        ['DK', 'Kopenhag', null],
        ['CA', 'Ottawa', null],
        ['AU', 'Canberra', null],
        ['IT', 'Roma', null],
        ['ES', 'Madrid', null],
        ['PL', 'Varşova', null],
        ['AZ', 'Bakü', null],
        ['KZ', 'Astana', null],
        ['UZ', 'Taşkent', null],
        ['TM', 'Aşkabat', null],
        ['RU', 'Moskova', null],
        ['AE', 'Abu Dabi', null],
        ['QA', 'Doha', null],
        ['SA', 'Riyad', null],
        ['IS', 'Reykjavik', null],
        ['TH', 'Bangkok', null],
        ['IE', 'Dublin', null],
        ['FI', 'Helsinki', null],
        ['SG', 'Singapur', null],
        ['NZ', 'Wellington', null],
        ['KR', 'Seul', null],
        ['SI', 'Ljubljana', null],
        ['JP', 'Tokyo', null],
        ['MT', 'Valletta', null],
        ['LU', 'Lüksemburg', null],
        ['IL', 'Tel Aviv', null],
        ['CZ', 'Prag', null],
        ['GR', 'Atina', null],
        ['EE', 'Tallinn', null],
        ['BH', 'Manama', null],
        ['LT', 'Vilnius', null],
        ['PT', 'Lizbon', null],
        ['LV', 'Riga', null],
        ['HR', 'Zagreb', null],
        ['SK', 'Bratislava', null],
        ['CL', 'Santiago', null],
        ['HU', 'Budapeşte', null],
        ['AR', 'Buenos Aires', null],
        ['ME', 'Podgorica', null],
        ['UY', 'Montevideo', null],
        ['OM', 'Maskat', null],
        ['KW', 'Kuveyt', null],
        ['KH', 'Phnom Penh', null],
        ['ID', 'Cakarta', null],
        // Kendi büyükelçiliği yok — komşu ülkeden akredite (2026-08-19 doğrulandı).
        ['SM', 'Roma', 'San Marino\'ya akredite'],
        ['AD', 'Madrid', 'Andorra\'ya akredite'],
        ['LI', 'Bern', 'Lihtenştayn\'a akredite'],
    ];

    public function run(): void
    {
        $mevcutUlkeler = Country::query()->where('is_active', true)->pluck('code')->flip();
        $sirasi = (int) Temsilcilik::query()->max('sort_order');
        $atlanan = [];

        foreach (self::ULKELER as [$ulkeKodu, $sehir, $akrediteNotu]) {
            if (! isset($mevcutUlkeler[$ulkeKodu])) {
                // Ülke tablosunda yoksa (ileride kaldırılmışsa) sessizce atla —
                // var olmayan bir country_code'a kayıt bağlamak yanlış olur.
                $atlanan[] = $ulkeKodu;

                continue;
            }

            $ad = $akrediteNotu !== null
                ? "{$sehir} Büyükelçiliği ({$akrediteNotu})"
                : "{$sehir} Büyükelçiliği";

            $anahtar = ['country_code' => $ulkeKodu, 'slug' => Str::slug($sehir)];

            $veri = [
                'ad' => $ad,
                'tur' => Temsilcilik::TUR_BUYUKELCILIK,
                'sehir' => $sehir,
                'is_active' => true,
                'sort_order' => ++$sirasi,
                'yonlendirme_notu' => self::YONLENDIRME_NOTU,
            ];

            // adres/koordinat YALNIZ hâlâ boşsa doldurulur — bu seeder'ın
            // diğer alanları koşulsuz ezmesinden BAĞIMSIZ olarak korunur
            // (bkz. RehberTemsilcilikleriSeeder'daki aynı desen/gerekçe).
            $mevcut = Temsilcilik::query()->where($anahtar)->first();
            if (($mevcut === null || $mevcut->adres === null) && isset(self::ADRESLER[$ulkeKodu])) {
                [$adres, $lat, $lng] = self::ADRESLER[$ulkeKodu];
                $veri['adres'] = $adres;
                $veri['latitude'] = $lat;
                $veri['longitude'] = $lng;
            }

            Temsilcilik::query()->updateOrCreate($anahtar, $veri);
        }

        if ($atlanan !== []) {
            $this->command?->warn('Ülke tablosunda bulunamayan kodlar atlandı: '.implode(', ', $atlanan));
        }

        $this->command?->info(count(self::ULKELER) - count($atlanan).' temsilcilik iskeleti oluşturuldu/güncellendi.');
    }
}
