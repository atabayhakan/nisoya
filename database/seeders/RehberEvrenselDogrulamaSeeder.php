<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Ülke Rehberi'ni Almanya+ABD DIŞINDAKİ TÜM ülkelere genişletir (2026-08-26,
 * elle çalıştırılır, deploy zincirinde DEĞİL):
 *
 *     php artisan db:seed --class=RehberEvrenselDogrulamaSeeder --force
 *
 * NEDEN AYRI BİR SEEDER: RehberAlmanyaDogrulamaSeeder'daki 10 kategorinin
 * araştırması gerçekte %80-90 ÜLKE-BAĞIMSIZ (Türk hukuku — 5490/5901/5352/
 * 298 sayılı kanunlar, e-Devlet süreçleri — nerede yaşadığınızdan bağımsız
 * aynı) — yalnız birkaç yerde Almanya'ya özgü kurum adı (Standesamt,
 * Führerscheinstelle, Alman StAG reformu) geçiyordu. Bu dosya o kısımları
 * GENELLEŞTİRİR ("ikamet edilen ülkenin yetkili makamı" gibi) ve TÜM diğer
 * ülkelere (her biri TEK temsilcilik) uygular — böylece 56 ülke için sıfırdan
 * 56 ayrı araştırma turu gerekmeden HER ülkeye gerçek, hukuken doğru bir
 * TASLAK başlangıç sağlanır. Almanya'daki gibi ülkeye özel derinleştirme
 * (yerel kurum adı, Apostil Sözleşmesi üyeliği vb.) SONRAKİ, ayrı bir tur.
 *
 * DE ve US kasıtlı olarak DIŞLANIR: DE zaten daha iyi/özel içeriğe sahip
 * (bu genel şablon onu GERİLETİRDİ), US'nin zaten 15 yayında kaydı var
 * (dokunulursa yayındaki içerik sessizce taslağa düşer — bkz. K7).
 *
 * K7 sözleşmesi aynen geçerli: hepsi TASLAK doğar. `firstOrCreate` deseni:
 * ikinci koşu zararsız, panelden yapılmış elle düzenlemeleri EZMEZ (yalnız
 * hiç kaydı olmayan (temsilcilik, tür) çiftlerine yeni satır ekler —
 * RehberAlmanyaDogrulamaSeeder'ın `updateOrCreate`'inden BİLEREK farklı,
 * çünkü burada "var olanı düzelt" değil "hiç olmayanı doldur" amacı var).
 */
class RehberEvrenselDogrulamaSeeder extends Seeder
{
    private const HARIC_TUTULAN_ULKELER = ['DE', 'US'];

    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()
            ->whereNotIn('country_code', self::HARIC_TUTULAN_ULKELER)
            ->get();

        if ($temsilcilikler->isEmpty()) {
            $this->command?->warn('DE/US dışında temsilcilik bulunamadı.');

            return;
        }

        $turler = IslemTuru::query()->get()->keyBy('slug');
        if ($turler->isEmpty()) {
            $this->command?->warn('Hiç İşlem Türü yok — önce RehberAlmanyaSeeder ve RehberAlmanyaDogrulamaSeeder çalıştırılmalı.');

            return;
        }

        $veriler = array_merge($this->orijinalSekizKategori(), $this->evrenselOnKategori());

        $eklenen = 0;
        foreach ($temsilcilikler as $temsilcilik) {
            foreach ($turler as $slug => $tur) {
                $veri = $veriler[$slug] ?? null;

                $olusturuldu = TemsilcilikIslemi::query()->firstOrCreate(
                    ['temsilcilik_id' => $temsilcilik->id, 'islem_turu_id' => $tur->id],
                    $veri !== null
                        ? [...$veri, 'status' => TemsilcilikIslemi::STATUS_TASLAK]
                        : [
                            'evraklar' => [['ad' => 'T.C. kimlik kartı veya pasaport']],
                            'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
                            'notlar' => 'Genel şablon henüz yazılmadı — bu kategori için resmî kaynaktan araştırma gerekiyor.',
                            'status' => TemsilcilikIslemi::STATUS_TASLAK,
                        ],
                );

                if ($olusturuldu->wasRecentlyCreated) {
                    $eklenen++;
                }
            }
        }

        $this->command?->info("Eklendi: {$eklenen} yeni taslak kayıt ({$temsilcilikler->count()} ülke × ".count($turler).' tür).');

        $this->apostilOlmayanUlkeleriDuzelt($turler->get('apostil'));
    }

    /**
     * "Apostil" kategorisi TÜM ülkelere aynı genel şablonla gitti, ama apostil
     * kavramı YALNIZ Lahey Apostil Sözleşmesi'ne (1961) taraf ülkeler arasında
     * anlamlı — üye olmayan bir ülke için "apostil alın" demek kullanıcıyı var
     * olmayan bir işleme yönlendirir. HCCH'nin resmî güncel listesinden
     * (hcch.net, 2026-08-26'da çekildi) doğrulanan, Nisoya'nın 60 ülkesi
     * içindeki 6 ÜYE OLMAYAN ülke için içerik "konsolosluk tasdik zinciri"ne
     * çevrilir. KKTC (XN) uluslararası tanınmazlığı nedeniyle Lahey listesinde
     * zaten yer alamaz — güvenli varsayılan olarak aynı gruba alındı.
     */
    private function apostilOlmayanUlkeleriDuzelt(?IslemTuru $apostilTuru): void
    {
        if ($apostilTuru === null) {
            $this->command?->warn('"apostil" İşlem Türü bulunamadı, düzeltme atlandı.');

            return;
        }

        $uyeOlmayanUlkeler = ['TM', 'AE', 'QA', 'KW', 'KH', 'XN'];

        $veri = [
            'evraklar' => [
                ['ad' => 'Bu ülke Lahey Apostil Sözleşmesi\'ne TARAF DEĞİL', 'not' => 'Apostil yerine "konsolosluk tasdik zinciri" uygulanır'],
                ['ad' => 'Tasdiklenecek belgenin aslı'],
                ['ad' => 'Belgeyi düzenleyen yerel makamın üst onayı', 'not' => 'Genelde o ülkenin Dışişleri Bakanlığınca ön onaylanmış olmalı'],
            ],
            'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
            'notlar' => 'Bu ülke Lahey Apostil Sözleşmesi\'ne (1961) TARAF DEĞİL (hcch.net resmî liste, 2026-08-26 doğrulandı) — bu yüzden "apostil" yerine KONSOLOSLUK TASDİK ZİNCİRİ uygulanır: belge önce düzenleyen ülkenin kendi yetkili makamınca (genelde Dışişleri Bakanlığı) onaylanır, sonra ilgili T.C. temsilciliği tarafından tasdiklenir. Süreç ve gereken ek onaylar ülkeye göre değişir, mutlaka temsilcilikten güncel bilgi alın.',
            'status' => TemsilcilikIslemi::STATUS_TASLAK,
        ];

        $duzeltilen = Temsilcilik::query()
            ->whereIn('country_code', $uyeOlmayanUlkeler)
            ->get()
            ->each(fn (Temsilcilik $t) => TemsilcilikIslemi::query()->updateOrCreate(
                ['temsilcilik_id' => $t->id, 'islem_turu_id' => $apostilTuru->id],
                $veri,
            ))
            ->count();

        $this->command?->info("Apostil-dışı düzeltme: {$duzeltilen} ülke (TM/AE/QA/KW/KH/XN).");
    }

    /**
     * Orijinal RehberAlmanyaSeeder'ın 8 kategorisi (vekaletname, pasaport,
     * kimlik-karti, dogum-tescili, olum-ve-cenaze, askerlik, mavi-kart,
     * apostil) zaten YAZILDIĞI ANDAN İTİBAREN ülke-bağımsızdı — o seeder'ın
     * KENDİ genelEvraklar()/genelNot() metotlarını (artık public static)
     * aynen yeniden kullanıyoruz, kopyalamıyoruz.
     *
     * @return array<string, array{evraklar: list<array{ad: string, not?: string}>, resmi_kaynak_url: string, notlar: string}>
     */
    private function orijinalSekizKategori(): array
    {
        $slugler = ['vekaletname', 'pasaport', 'kimlik-karti', 'dogum-tescili', 'olum-ve-cenaze', 'askerlik', 'mavi-kart', 'apostil'];

        $sonuc = [];
        foreach ($slugler as $slug) {
            $sonuc[$slug] = [
                'evraklar' => RehberAlmanyaSeeder::genelEvraklar($slug),
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
                'notlar' => RehberAlmanyaSeeder::genelNot($slug),
            ];
        }

        return $sonuc;
    }

    /**
     * 2026-08-26'da 7 paralel araştırmayla doğrulanan 10 kategori
     * (RehberAlmanyaDogrulamaSeeder'daki Almanya-özel hâlinin GENELLEŞTİRİLMİŞ
     * versiyonu — Standesamt/Führerscheinstelle/StAG gibi Almanya'ya özgü
     * kurum adları "ikamet edilen ülkenin yetkili makamı" gibi ülke-bağımsız
     * ifadelere çevrildi). Türk hukuku kısımları (5490/5901/5352/298 sayılı
     * kanunlar) AYNEN kaldı — bu kısım zaten ülkeden bağımsızdı.
     *
     * @return array<string, array{evraklar: list<array{ad: string, not?: string}>, resmi_kaynak_url: string, notlar: string}>
     */
    private function evrenselOnKategori(): array
    {
        return [
            'evlilik-tescili' => [
                'evraklar' => [
                    ['ad' => 'Formül B (Uluslararası Evlenme Kayıt Örneği)', 'not' => 'Evliliğin yapıldığı yerel nüfus/medeni hal dairesinden, genelde son 6 ay içinde alınmış'],
                    ['ad' => 'Her iki eşin T.C. kimlik kartı/pasaportu'],
                    ['ad' => 'Yabancı eşin uluslararası doğum belgesi (Formül A)', 'not' => 'Yalnız eşlerden biri yabancıysa gerekir'],
                    ['ad' => 'Konsolosluk randevusu', 'not' => 'www.konsolosluk.gov.tr üzerinden — 30 gün içinde başvurulmalı (5490 sK. md.24)'],
                ],
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr/Faq/Index',
                'notlar' => '5490 sayılı Nüfus Hizmetleri Kanunu md.22 ve md.24: yurt dışında yapılan evlilik, evlendiren yabancı makamdan alınan belgenin dış temsilciliğe EN GEÇ 30 GÜN İÇİNDE verilmesi şartıyla geçerli — süresinde bildirilmezse idari para cezası uygulanır (md.68/1-b, güncel tutar teyit edilmeli). Formül A/B uluslararası (CIEC) standart formlardır, ikamet edilen ülkeden bağımsız kullanılır. Güncel harç tutarı ve fotoğraf gereksinimi temsilcilikten temsilciliğe değişebilir.',
            ],
            'adres-kaydi' => [
                'evraklar' => [
                    ['ad' => 'T.C. kimlik kartı, Türk pasaportu veya ehliyet'],
                    ['ad' => 'Adres Beyan Formu (80-02)', 'not' => 'Şahsen başvuruda görevli doldurur; posta ile başvuruda ıslak imzalı gönderilir'],
                ],
                'resmi_kaynak_url' => 'https://www.nvi.gov.tr/sss-adres-hizmetleri',
                'notlar' => '5490 sayılı Kanun md.50 (bildirim) ve md.68/c (ceza): süresinde (20 iş günü) bildirilmeyen adrese 2026 itibarıyla 814 TL, gerçeğe aykırı beyana 17.051 TL idari para cezası uygulanıyor (yıllık güncellenir). ANA YÖNTEM ŞAHSEN KONSOLOSLUK ziyaretidir; e-Devlet üzerinden uzaktan bildirim yalnız MOBİL YA DA E-İMZA ile mümkün, düz e-Devlet şifresi YETMEZ. Bazı temsilcilikler posta/e-Devlet yolunu hiç kabul etmeyebilir — ziyaretçi kendi temsilciliğinin güncel sayfasından teyit etmeli.',
            ],
            'nufus-kayit-ornegi' => [
                'evraklar' => [
                    ['ad' => 'Resmî kimlik belgesi', 'not' => 'Şahsen başvuruda; e-Devlet\'te bile gerekmez'],
                    ['ad' => 'Yazılı talep (dilekçe)', 'not' => 'Yalnız şahsen gelinmeyen/vekille yapılan başvurularda gerekir'],
                ],
                'resmi_kaynak_url' => 'https://www.turkiye.gov.tr/nvi-nufus-kayit-ornegi-belgesi-sorgulama',
                'notlar' => 'Çoğu durumda e-Devlet üzerinden ANINDA VE ÜCRETSİZ alınabilir — barkodlu belge resmî nüfus müdürlüğü belgesiyle eşdeğer geçerlilikte, giriş için düz e-Devlet şifresi YETERLİ. Konsolosluğa gitmek şu durumlarda gerekir: (1) 18 yaş altı için e-Devlet sorgusu engelleniyor, (2) yabancı makam ıslak imza/orijinal belge istiyorsa, (3) yurt dışındaki resmî kullanım için genelde apostil+yeminli tercüme gerekir — TEK İSTİSNA: CIEC Sözleşmesi\'ne taraf ülkeler arasında ÇOK DİLLİ doğum/evlenme/ölüm örnekleri tercümesiz/apostilsiz geçerlidir (ülkenizin bu sözleşmeye taraf olup olmadığını kontrol edin).',
            ],
            'ehliyet' => [
                'evraklar' => [
                    ['ad' => 'T.C. kimlik kartı'],
                    ['ad' => 'Mevcut/eski Türk sürücü belgesi', 'not' => 'Kayıp/çalıntıysa durumu özetleyen dilekçe/beyan yerine geçer'],
                    ['ad' => 'Sağlık raporu', 'not' => 'İkamet edilen ülkeden, "sürücülüğe engel yok" ibaresiyle + yeminli tercüme'],
                    ['ad' => 'Adli sicil raporu', 'not' => 'e-Devlet\'ten veya konsolosluktan'],
                    ['ad' => '1 adet biyometrik fotoğraf', 'not' => '50x60mm, son 6 ay'],
                    ['ad' => 'Vakıf payı ödeme makbuzu', 'not' => 'Türkiye\'deki anlaşmalı bankadan ÖNCEDEN ödenir'],
                ],
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
                'notlar' => 'KRİTİK UYARI: Konsolosluk SADECE mevcut Türk sürücü belgesinin YENİLENMESİNİ yapar (Karayolları Trafik Yönetmeliği Geçici md.10, 2020). Türk ehliyetinin İKAMET EDİLEN ÜLKENİN ehliyetine ÇEVRİLMESİ KONSOLOSLUĞUN İŞİ DEĞİLDİR — bu tamamen o ülkenin kendi trafik/ehliyet makamının işidir, çevirme kuralları (sınav gerekip gerekmediği dahil) ülkeden ülkeye değişir. Bu ayrımı yanlış vermek ziyaretçiyi yanlış kapıya gönderir. Sıfırdan/sınavla ehliyet de burada verilmez. Başvuru şartı: en az 6 aydır o temsilciliğin bölgesinde adres kaydı olmalı.',
            ],
            'noter-tasdik' => [
                'evraklar' => [
                    ['ad' => 'Belgenin aslı', 'not' => 'Yabancı resmî belgeyse ÖNCE apostil şerhi gerekebilir — apostili konsolosluk değil, o ülkenin yetkili makamı verir'],
                    ['ad' => 'Belgenin fotokopisi', 'not' => 'Konsolosluk arşivi için'],
                    ['ad' => 'Kimlik/pasaport'],
                    ['ad' => 'Randevu', 'not' => 'Bazı temsilciliklerde gerekmez, bazılarında şart — kendi temsilciliğine bak'],
                ],
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedure/6',
                'notlar' => '"İmza ve Suret Tasdiki" TEK işlem değil, en az 3 alt türü var: (A) suret/fotokopi tasdiki ("aslı gibidir"), (B) yabancı makamdan alınan belgenin imza-mühür tasdiki (önce apostil şart), (C) kişinin kendi imzasının tasdiki. Hukuki dayanak: Viyana Konsolosluk Sözleşmesi (1963) md.5(f) + 1512 sayılı Noterlik Kanunu md.191. Randevu zorunluluğu ve harç tutarı temsilcilikten temsilciliğe değişiyor.',
            ],
            'tercume-tasdiki' => [
                'evraklar' => [
                    ['ad' => 'Kaynak belgenin aslı', 'not' => 'Yabancı resmî belgeyse apostil şerhi taşımalı'],
                    ['ad' => 'Yeminli tercüme', 'not' => 'MUTLAKA o başkonsolosluğun KENDİ listesine kayıtlı bir tercümandan olmalı (konsolosluk.gov.tr/TranslatorSearch/Index) — kayıtlı olmayanın çevirisi KABUL EDİLMEZ'],
                    ['ad' => 'En az 2 nüsha', 'not' => 'Tam sayı temsilciliğe göre değişebilir'],
                    ['ad' => 'Kimlik/pasaport'],
                ],
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr/TranslatorSearch/Index',
                'notlar' => 'KRİTİK: Konsolosluk çeviriyi KENDİSİ YAPMAZ — yalnız önceden yapılmış bir çevirideki tercümanın imza/mührünün gerçekliğini kendi sicil kaydıyla eşleştirip onaylar. Ziyaretçi önce, konsolosluk DIŞINDA, o BAŞKONSOLOSLUĞUN KENDİ yeminli tercüman listesinden birine çeviri yaptırmalı — ikamet edilen ülkenin genel yeminli tercüman sisteminden biri o listede değilse çevirisi reddedilebilir. Hukuki dayanak: Noterlik Kanunu md.75.',
            ],
            'vatandaslik' => [
                'evraklar' => [
                    ['ad' => 'VAT-5 Başvuru Formu', 'not' => 'Yeniden kazanma için, online doldurulur'],
                    ['ad' => 'Yeniden Vatandaşlık Kazanma Ön Başvuru Formu', 'not' => 'Online, konsolosluk.gov.tr'],
                    ['ad' => 'Pasaport fotokopisi', 'not' => 'Online yüklenir'],
                    ['ad' => 'Hizmet bedeli ödeme makbuzu'],
                    ['ad' => 'Medeni hal belgesi'],
                    ['ad' => 'Kaybettikten sonra medeni halde değişiklik varsa (evlenme/boşanma) belgeler', 'not' => 'Türkçe tercümeli ve onaylı'],
                ],
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr/YenidenVatandaslikKazanmaKitapcik.pdf',
                'notlar' => 'ÜÇ AYRI SÜREÇ var: (a) İZİNLE ÇIKMA (5901 sK. md.25-28), (b) SONRADAN YENİDEN KAZANMA (5901 sK. md.13, ikamet şartı ARANMAZ — izinle çıkanların çoğu buraya girer): KRİTİK UYARI — daha önce askerlik yapmadan çıkmış erkeklerde yeniden kazanınca askerlik yükümlülüğü YENİDEN DOĞAR (7179 sK. md.43). (c) EVLİLİK YOLUYLA (yabancı eş için, 5901 sK. md.16): en az 3 yıl evli olma + fiilen birlikte yaşama şartı. İkamet edilen ülkenin KENDİ çifte vatandaşlık kuralları farklı olabilir (bazı ülkeler T.C.\'den çıkmayı şart koşar, bazıları koşmaz) — ülkenizin kendi makamlarından teyit edin. Güncel Euro/döviz harç tutarları ve süreler resmî kaynaktan teyit edilmeli.',
            ],
            'bosanma-tescili' => [
                'evraklar' => [
                    ['ad' => 'Başvuru formu', 'not' => 'Konsoloslukta ya da nvi.gov.tr\'den temin edilir'],
                    ['ad' => 'Kesinleşmiş boşanma kararı aslı', 'not' => 'Apostil şerhi + yeminli tercüme ile'],
                    ['ad' => 'Tarafların T.C. kimlik kartı/pasaportu'],
                    ['ad' => 'Vekille başvuruda noter onaylı, fotoğraflı vekâletname', 'not' => 'Yurt dışında düzenlendiyse + apostil + tercüme'],
                ],
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr/Faq/Index',
                'notlar' => 'Hukuki dayanak: 5490 sayılı Kanun md.27/A (7039 sayılı Kanunla 2017\'de eklendi) + 2018 tarihli özel yönetmelik. Taraflar birlikte ya da ayrı zamanlarda (en fazla 90 gün ara ile) BAŞVURUNUN YAPILDIĞI YERDEKİ (yani kararı veren mahkemenin/makamın bölgesindeki) yetkili T.C. temsilciliğine başvurur. Boşanma sisteminin idari mi (bazı ülkelerde belediye/nüfus dairesi yetkili) yoksa yalnız mahkeme kararıyla mı olduğu ÜLKEDEN ÜLKEYE DEĞİŞİR — ikamet edilen ülkenin kendi sistemini öğrenin. Yalnız boşanmanın kendisi tescil edilir; velayet/nafaka/mal rejimi ayrı bir MÖHUK tanıma-tenfiz davası gerektirir.',
            ],
            'adli-sicil' => [
                'evraklar' => [
                    ['ad' => 'T.C. kimlik kartı veya pasaport'],
                    ['ad' => 'Vekille başvuruda vekâletnamede bu yetkinin açıkça yazılı olması', 'not' => '5352 sayılı Kanun md.7'],
                ],
                'resmi_kaynak_url' => 'https://adlisicil.adalet.gov.tr/Home/SayfaDetay/yurtdisindan-basvuru',
                'notlar' => '5352 sayılı Adli Sicil Kanunu md.8 (2006\'da 5560 sayılı Kanunla eklendi): konsolosluklar bu belgeyi DOĞRUDAN düzenleme yetkisine sahip — bu, e-Devlet şifresi almanın bir ön adımı DEĞİL, tamamen BAĞIMSIZ bir kanal. e-Devlet\'ten alınan belge 2019\'dan beri otomatik e-Apostil ile geliyor (120+ ülkede geçerli); konsoloslukta bizzat alınan belgenin apostil durumu netleşmedi. Konsoloslukta düzenlenen belge POSTA/E-POSTA İLE GÖNDERİLEMEZ, elden teslim edilir. Kanunun kendisi (md.17) küçük bir harç öngörüyor ama güncel konsolosluk sayfaları "ücretsiz" diyor — bu çelişki net değil.',
            ],
            'secmen-kaydi' => [
                'evraklar' => [
                    ['ad' => 'Adres Beyan Formu (Form B)', 'not' => 'Adres kaydı eksik/hatalıysa; bkz. Yurtdışı Adres Kaydı kategorisi'],
                    ['ad' => 'T.C. kimlik kartı veya pasaport'],
                    ['ad' => 'Oy kullanırken: T.C. kimlik numaralı bir kimlik belgesi', 'not' => 'Yabancı ülke belgeleri (ehliyet vb.) kimlik tespitinde GEÇERLİ DEĞİL'],
                ],
                'resmi_kaynak_url' => 'https://secmen.ysk.gov.tr/secmen-yurtdisi',
                'notlar' => 'İKİ KATMANLI sistem (298 sK.): (1) Yurt Dışı Seçmen Kütüğü, nüfus kaydındaki "yerleşim yeri" (adres kaydı/ADNKS) bilgisine göre YSK tarafından OTOMATİK oluşturulur — ayrı bir "seçmen kaydı başvurusu" yoktur. (2) Adres kaydının doğru/yurt dışı görünmesi için Adres Beyan Formu (Form B) ile konsolosluğa/nüfus müdürlüğüne başvurmak gerekir. KRİTİK: kayıt olduğuna dair HİÇBİR bildirim/mektup gönderilmez — vatandaş kendi kaydını secmen.ysk.gov.tr/secmen-yurtdisi, e-Devlet ya da YSK çağrı merkezinden (444 9 975) KENDİSİ kontrol etmelidir. Yurt dışı seçmenler yalnız Cumhurbaşkanlığı/milletvekili genel seçimi/referandumda oy kullanabilir — YEREL SEÇİMLERDE OY KULLANAMAZ. Mektupla oy 2008\'de Anayasa Mahkemesi kararıyla iptal edildi, elektronik oy yasada var ama hiç kullanılmıyor.',
            ],
        ];
    }
}
