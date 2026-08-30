<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Ülke Rehberi doğrulama turu (2026-08-26, elle çalıştırılır, deploy zincirinde
 * DEĞİL): RehberAlmanyaSeeder'ın jenerik taslaklarını resmî kaynaktan
 * araştırılmış gerçek içerikle DÜZELTİR + 3 gerçekten eksik kategoriyi
 * (boşanma tescili, adli sicil, seçmen kaydı) sıfırdan TASLAK olarak ekler.
 *
 *     php artisan db:seed --class=RehberAlmanyaDogrulamaSeeder --force
 *
 * K7 sözleşmesi AYNEN geçerli: hepsi TASLAK doğar (status DEĞİŞTİRİLMEZ).
 * Bu tur "daha iyi taslak" üretir, "yayına alma" ayrı ve sahibe ait bir
 * panel eylemidir. `updateOrCreate` deseni: ikinci koşu zararsız, panelden
 * yapılmış elle düzenlemeleri EZMEZ çünkü yalnız BU kayıtların (temsilcilik,
 * tür) kombinasyonuna dokunur — ama panelden biri zaten bu alanı elle
 * değiştirdiyse üzerine yazar (bilinen ödün, seeder'ların doğası).
 *
 * Her kategori 7 agent'lık paralel araştırmayla (WebSearch/WebFetch, resmî
 * kaynak: mevzuat.gov.tr, konsolosluk.gov.tr, nvi.gov.tr, e-devlet,
 * .bk.mfa.gov.tr temsilcilik sayfaları) doğrulandı. Kaynak URL'leri ve
 * belirsizlik notları `notlar` alanında saklanıyor ki sahip panelden
 * doğrularken neyin kesin, neyin şüpheli olduğunu görsün.
 */
class RehberAlmanyaDogrulamaSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'DE')->get();

        if ($temsilcilikler->isEmpty()) {
            $this->command?->warn('DE temsilcilikleri bulunamadı — önce RehberAlmanyaSeeder çalıştırılmalı.');

            return;
        }

        $this->mevcutKategorileriDuzelt($temsilcilikler);
        $this->yeniKategorileriEkle($temsilcilikler);
    }

    /**
     * Zaten TASLAK olarak var olan ama hiç doğrulanmamış kategorileri
     * (evlilik-tescili, adres-kaydi, nufus-kayit-ornegi) araştırılmış
     * içerikle GÜNCELLER. Status TASLAK kalır — sahip panelden doğrulayıp
     * yayına alır.
     *
     * @param  Collection<int, Temsilcilik>  $temsilcilikler
     */
    protected function mevcutKategorileriDuzelt($temsilcilikler): void
    {
        $guncellemeler = [
            'evlilik-tescili' => [
                'evraklar' => [
                    ['ad' => 'Formül B (Uluslararası Evlenme Kayıt Örneği)', 'not' => 'Evliliğin yapıldığı Alman Standesamt\'tan, genelde son 6 ay içinde alınmış'],
                    ['ad' => 'Her iki eşin T.C. kimlik kartı/pasaportu'],
                    ['ad' => 'Yabancı eşin uluslararası doğum belgesi (Formül A)', 'not' => 'Yalnız eşlerden biri yabancıysa gerekir'],
                    ['ad' => 'Konsolosluk randevusu', 'not' => 'www.konsolosluk.gov.tr üzerinden — 30 gün içinde başvurulmalı (5490 sK. md.24)'],
                ],
                'sure_metni' => 'Randevu + işlem birkaç hafta',
                'ucret_metni' => 'Güncel harç için resmî kaynağa bak',
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr/Faq/Index',
                'notlar' => '5490 sayılı Nüfus Hizmetleri Kanunu md.22 ve md.24: yurt dışında yapılan evlilik, evlendiren yabancı makamdan alınan belgenin dış temsilciliğe EN GEÇ 30 GÜN İÇİNDE verilmesi şartıyla geçerli — süresinde bildirilmezse idari para cezası uygulanır (md.68/1-b, güncel tutar teyit edilmeli). "Sağlık raporu" istendiğine dair bir iddia hiçbir resmî kaynakta doğrulanamadı, listeye alınmadı. Güncel harç tutarı ve fotoğraf gereksinimi temsilcilikten temsilciliğe değişebilir, ilgili temsilciliğin kendi sayfasından teyit edilmeli.',
            ],
            'adres-kaydi' => [
                'evraklar' => [
                    ['ad' => 'T.C. kimlik kartı, Türk pasaportu veya ehliyet'],
                    ['ad' => 'Adres Beyan Formu (80-02)', 'not' => 'Şahsen başvuruda görevli doldurur; posta ile başvuruda ıslak imzalı gönderilir'],
                ],
                'sure_metni' => 'Genelde aynı gün, bazı temsilciliklerde randevusuz',
                'ucret_metni' => 'Bildirimin kendisi ücretsiz',
                'resmi_kaynak_url' => 'https://www.nvi.gov.tr/sss-adres-hizmetleri',
                'notlar' => '5490 sayılı Kanun md.50 (bildirim) ve md.68/c (ceza): süresinde (20 iş günü) bildirilmeyen adrese 2026 itibarıyla 814 TL, gerçeğe aykırı beyana 17.051 TL idari para cezası uygulanıyor (yıllık güncellenir) — https://vezne.konsolosluk.gov.tr üzerinden ödenir, peşin ödemede kanuni indirim olabilir. ANA YÖNTEM ŞAHSEN KONSOLOSLUK ziyaretidir (çoğu temsilcilikte randevusuz kabul var); e-Devlet üzerinden uzaktan bildirim yalnız MOBİL YA DA E-İMZA ile mümkün, düz e-Devlet şifresi YETMEZ. Bazı temsilcilikler (örn. Salzburg örneği) posta/e-Devlet yolunu kabul etmediğini açıkça belirtiyor — ziyaretçi kendi temsilciliğinin güncel sayfasından teyit etmeli. "Almanya\'nın kendi adres kaydı belgesi (Meldebescheinigung) gerekli" iddiası hiçbir resmî Almanya temsilcilik sayfasında doğrulanamadı, evrak listesinden ÇIKARILDI.',
            ],
            'ehliyet' => [
                'evraklar' => [
                    ['ad' => 'T.C. kimlik kartı'],
                    ['ad' => 'Mevcut/eski Türk sürücü belgesi', 'not' => 'Kayıp/çalıntıysa durumu özetleyen dilekçe/beyan yerine geçer'],
                    ['ad' => 'Sağlık raporu', 'not' => 'Almanya\'da bir hekimden, "sürücülüğe engel yok" ibaresiyle + yeminli tercüme'],
                    ['ad' => 'Adli sicil raporu', 'not' => 'e-Devlet\'ten veya konsolosluktan'],
                    ['ad' => '1 adet biyometrik fotoğraf', 'not' => '50x60mm, son 6 ay'],
                    ['ad' => 'Vakıf payı ödeme makbuzu', 'not' => 'Türkiye\'deki anlaşmalı bankadan ÖNCEDEN ödenir'],
                ],
                'sure_metni' => 'Randevu + Türkiye\'den basım/gönderim süresi',
                'ucret_metni' => '~34€ harç + 16€ tasdik + TR\'de vakıf/kağıt bedeli (güncel: randevu.nvi.gov.tr)',
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
                'notlar' => 'KRİTİK UYARI: Konsolosluk SADECE mevcut Türk sürücü belgesinin YENİLENMESİNİ yapar (Karayolları Trafik Yönetmeliği Geçici md.10, 2020). Türk ehliyetinin ALMAN ehliyetine ÇEVRİLMESİ (Umschreibung) KONSOLOSLUĞUN İŞİ DEĞİLDİR — tamamen Alman yetkili makamının (Führerscheinstelle) işidir, Türkiye muafiyet listesinde olmadığı için teorik+direksiyon sınavı gerekir; bu bilgiyi yanlış vermek ziyaretçiyi yanlış kapıya gönderir. Sıfırdan/sınavla ehliyet de burada verilmez. Başvuru şartı: en az 6 aydır o temsilciliğin bölgesinde adres kaydı olmalı (bkz. adres-kaydı kategorisi). G sınıfı (iş makinesi) belgeleri kapsam dışı. Ücretler yıllık güncelleniyor, güncel tutar mutlaka randevu.nvi.gov.tr\'den teyit edilmeli.',
            ],
            'nufus-kayit-ornegi' => [
                'evraklar' => [
                    ['ad' => 'Resmî kimlik belgesi', 'not' => 'Şahsen başvuruda; e-Devlet\'te bile gerekmez'],
                    ['ad' => 'Yazılı talep (dilekçe)', 'not' => 'Yalnız şahsen gelinmeyen/vekille yapılan başvurularda gerekir'],
                ],
                'sure_metni' => 'e-Devlet\'ten anında, konsoloslukta aynı gün',
                'ucret_metni' => 'Ücretsiz',
                'resmi_kaynak_url' => 'https://www.turkiye.gov.tr/nvi-nufus-kayit-ornegi-belgesi-sorgulama',
                'notlar' => 'Çoğu durumda e-Devlet üzerinden ANINDA VE ÜCRETSİZ alınabilir — barkodlu belge resmî nüfus müdürlüğü belgesiyle eşdeğer geçerlilikte, https://www.turkiye.gov.tr/belge-dogrulama üzerinden doğrulanabilir; giriş için düz e-Devlet şifresi YETERLİ (adres beyanının aksine e-imza şartı yok). Konsolosluğa/nüfus müdürlüğüne GİTMEK şu durumlarda gerekir: (1) 18 yaş altı için e-Devlet sorgusu engelleniyor, (2) yabancı makam ıslak imza/orijinal belge istiyorsa, (3) Almanya\'daki resmî kullanım için genelde apostil+yeminli tercüme gerekir — TEK İSTİSNA: Türkiye-Almanya\'nın taraf olduğu CIEC Sözleşmesi kapsamındaki ÇOK DİLLİ doğum/evlenme/ölüm örnekleri tercümesiz/apostilsiz geçerlidir, ama bu e-Devlet\'in ürettiğinden FARKLI bir belge türüdür, ayrıca talep edilmeli.',
            ],
            'noter-tasdik' => [
                'evraklar' => [
                    ['ad' => 'Belgenin aslı', 'not' => 'Yabancı (Alman) resmî belgeyse ÖNCE apostil şerhi gerekir — apostili konsolosluk değil, Alman yetkili makamı verir'],
                    ['ad' => 'Belgenin fotokopisi', 'not' => 'Konsolosluk arşivi için'],
                    ['ad' => 'Kimlik/pasaport'],
                    ['ad' => 'Randevu', 'not' => 'Bazı temsilciliklerde (ör. Köln, Düsseldorf) gerekmez, bazılarında şart — kendi temsilciliğine bak'],
                ],
                'sure_metni' => 'Temsilciliğe göre randevulu/randevusuz değişir',
                'ucret_metni' => 'Yıllık (2 Ocak) güncellenir, temsilcilikten teyit edilmeli',
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedure/6',
                'notlar' => '"İmza ve Suret Tasdiki" TEK işlem değil, en az 3 alt türü var: (A) suret/fotokopi tasdiki ("aslı gibidir"), (B) yabancı makamdan alınan belgenin imza-mühür tasdiki (önce apostil şart), (C) kişinin kendi imzasının tasdiki. Hukuki dayanak: Viyana Konsolosluk Sözleşmesi (1963) md.5(f) + 1512 sayılı Noterlik Kanunu md.191 (madde numarası orta güvenle doğrulandı, birincil metne erişim sorunu yaşandı — yayın öncesi mevzuat.gov.tr\'den teyit edilmeli). Randevu zorunluluğu ve harç tutarı temsilcilikten temsilciliğe değişiyor, TEK bir kural olarak sunulmamalı.',
            ],
            'tercume-tasdiki' => [
                'evraklar' => [
                    ['ad' => 'Kaynak belgenin aslı', 'not' => 'Alman resmî belgeyse apostil şerhi taşımalı'],
                    ['ad' => 'Yeminli tercüme', 'not' => 'MUTLAKA o başkonsolosluğun KENDİ listesine kayıtlı bir tercümandan olmalı (konsolosluk.gov.tr/TranslatorSearch/Index) — kayıtlı olmayanın çevirisi KABUL EDİLMEZ'],
                    ['ad' => 'En az 2 nüsha', 'not' => 'Tam sayı temsilciliğe göre değişebilir'],
                    ['ad' => 'Kimlik/pasaport'],
                ],
                'sure_metni' => 'Çeviri önceden yaptırılır, tasdik genelde aynı gün',
                'ucret_metni' => 'Sayfa/belge başına değişir, güncel tutar teyit edilmeli',
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr/TranslatorSearch/Index',
                'notlar' => 'KRİTİK: Konsolosluk çeviriyi KENDİSİ YAPMAZ — yalnız önceden yapılmış bir çevirideki tercümanın imza/mührünün gerçekliğini kendi sicil kaydıyla eşleştirip onaylar, çevirinin içerik doğruluğunu incelemez. Bu yüzden ziyaretçi önce, konsolosluk DIŞINDA, o BAŞKONSOLOSLUĞUN KENDİ yeminli tercüman listesinden birine çeviri yaptırmalı — başka bir şehrin ya da Almanya\'nın genel yeminli tercüman sisteminden (vereidigter Übersetzer) biri o listede değilse çevirisi reddedilir. Hukuki dayanak: Noterlik Kanunu md.75 (yüksek güvenle doğrulandı, Hannover Başkonsolosluğu\'nun kendi sayfasında birebir atıfla). Güncel Euro tutarı bulunamadı, sayfa başı/belge başı ücretlendirme temsilciliğe göre değişiyor.',
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
                'sure_metni' => 'Resmî bir üst süre belirtilmemiş',
                'ucret_metni' => '2024: 280 TL\'nin günlük Euro karşılığı (teyit edilmeli)',
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr/YenidenVatandaslikKazanmaKitapcik.pdf',
                'notlar' => 'ÜÇ AYRI SÜREÇ var, birbirine karıştırılmamalı — buradaki evrak listesi Nisoya\'nın hedef kitlesine en uygun olan (b)\'ye göredir: (a) İZİNLE ÇIKMA (5901 sK. md.25-28): bugün büyük ölçüde gönüllü — 27 Haziran 2024\'te yürürlüğe giren Alman StAG reformu sonrası Almanya artık genel kural olarak çifte vatandaşlığa izin veriyor, çıkma zorunlu değil. (b) SONRADAN YENİDEN KAZANMA (5901 sK. md.13, ikamet şartı ARANMAZ — izinle çıkanların çoğu buraya girer): KRİTİK UYARI — daha önce askerlik yapmadan çıkmış erkeklerde yeniden kazanınca askerlik yükümlülüğü YENİDEN DOĞAR (7179 sK. md.43). (c) EVLİLİK YOLUYLA (yabancı eş için, 5901 sK. md.16): en az 3 yıl evli olma + fiilen birlikte yaşama şartı, eşler ayrı ayrı VE birlikte mülakata alınır (muvazaa kontrolü). Başvuru e-Devlet\'ten YAPILAMAZ, yalnız takip edilebilir; süreç şahsen konsolosluk üzerinden başlar. Güncel Euro harç tutarları ve süreler resmî kaynaktan teyit edilmeli, kaynaklar arası çelişkili.',
            ],
        ];

        $turler = IslemTuru::query()->whereIn('slug', array_keys($guncellemeler))->get()->keyBy('slug');

        foreach ($temsilcilikler as $temsilcilik) {
            foreach ($guncellemeler as $slug => $veri) {
                $tur = $turler->get($slug);
                if ($tur === null) {
                    continue;
                }

                TemsilcilikIslemi::query()->updateOrCreate(
                    ['temsilcilik_id' => $temsilcilik->id, 'islem_turu_id' => $tur->id],
                    [...$veri, 'status' => TemsilcilikIslemi::STATUS_TASLAK],
                );
            }
        }
    }

    /**
     * Gerçekten hiç taslağı olmayan 3 yeni kategoriyi ekler.
     *
     * @param  Collection<int, Temsilcilik>  $temsilcilikler
     */
    protected function yeniKategorileriEkle($temsilcilikler): void
    {
        $yeniTurler = [
            [
                'slug' => 'bosanma-tescili',
                'ad' => 'Boşanma Tescili',
                'aciklama' => 'Yurt dışında (Alman mahkemesi) verilen boşanma kararının Türkiye nüfusuna işlenmesi.',
                'evraklar' => [
                    ['ad' => 'Başvuru formu', 'not' => 'Konsoloslukta ya da nvi.gov.tr\'den temin edilir'],
                    ['ad' => 'Alman mahkemesinin KESİNLEŞMİŞ (rechtskräftig) boşanma kararı aslı', 'not' => 'Apostil şerhi + yeminli tercüme ile'],
                    ['ad' => 'Tarafların T.C. kimlik kartı/pasaportu'],
                    ['ad' => 'Vekille başvuruda noter onaylı, fotoğraflı vekâletname', 'not' => 'Yurt dışında düzenlendiyse + apostil + tercüme'],
                ],
                'sure_metni' => 'Komisyon incelemesi + 7 gün içinde tescil',
                'ucret_metni' => 'Tescilin kendisi muaf, yan işlemler harca tabi olabilir',
                'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr/Faq/Index',
                'notlar' => 'KAVRAM UYARISI: Almanya\'da boşanma YALNIZ mahkeme (Familiengericht) kararıyla olur, Standesamt\'ın boşanma yetkisi YOKTUR. Hukuki dayanak: 5490 sayılı Kanun md.27/A (7039 sayılı Kanunla 2017\'de eklendi) + 2018 tarihli özel yönetmelik. Taraflar birlikte ya da ayrı zamanlarda (en fazla 90 gün ara ile) BAŞVURUNUN YAPILDIĞI YERDEKİ (yani kararı veren Alman mahkemesinin bölgesindeki) yetkili T.C. temsilciliğine başvurur — Almanya\'da birden fazla temsilcilik olduğu için ikamet/mahkeme bölgesine göre DOĞRU temsilcilik seçilmeli. Yalnız boşanmanın kendisi tescil edilir; VELAYET/NAFAKA/MAL REJİMİ bu yoldan tescil edilemez, ayrıca Türk aile mahkemesinde MÖHUK tanıma-tenfiz davası gerekir. Güncel harç tutarı teyit edilemedi.',
            ],
            [
                'slug' => 'adli-sicil',
                'ad' => 'Adli Sicil Kaydı (Sabıka Kaydı)',
                'aciklama' => 'Yurt dışından adli sicil (sabıka) kaydı: e-Devlet\'ten anında ya da konsoloslukta bizzat alınır.',
                'evraklar' => [
                    ['ad' => 'T.C. kimlik kartı veya pasaport'],
                    ['ad' => 'Vekille başvuruda vekâletnamede bu yetkinin açıkça yazılı olması', 'not' => '5352 sayılı Kanun md.7'],
                ],
                'sure_metni' => 'e-Devlet\'ten anında, konsoloslukta genelde aynı gün',
                'ucret_metni' => 'Çoğu kaynakta ücretsiz (teyit edilmeli)',
                'resmi_kaynak_url' => 'https://adlisicil.adalet.gov.tr/Home/SayfaDetay/yurtdisindan-basvuru',
                'notlar' => '5352 sayılı Adli Sicil Kanunu md.8 (2006\'da 5560 sayılı Kanunla eklendi): konsolosluklar bu belgeyi DOĞRUDAN düzenleme yetkisine sahip — bu, e-Devlet şifresi almanın bir ön adımı DEĞİL, tamamen BAĞIMSIZ bir kanal. e-Devlet\'ten alınan belge 2019\'dan beri otomatik e-Apostil ile geliyor (120+ ülkede geçerli); konsoloslukta bizzat alınan belgenin apostil durumu hiçbir kaynakta netleşmedi. Konsoloslukta düzenlenen belge POSTA/E-POSTA İLE GÖNDERİLEMEZ, elden teslim edilir. Kanunun kendisi (md.17) küçük bir harç öngörüyor ama güncel konsolosluk sayfaları "ücretsiz" diyor — bu çelişki net değil, yayın öncesi teyit edilmeli.',
            ],
            [
                'slug' => 'secmen-kaydi',
                'ad' => 'Yurt Dışı Seçmen Kaydı',
                'aciklama' => 'Türkiye\'deki seçimlerde oy kullanabilmek için yurt dışı seçmen kütüğüne kayıt — ayrı bir başvuru değil, adres kaydına bağlı otomatik oluşur.',
                'evraklar' => [
                    ['ad' => 'Adres Beyan Formu (Form B)', 'not' => 'Adres kaydı eksik/hatalıysa; bkz. Yurtdışı Adres Kaydı kategorisi'],
                    ['ad' => 'T.C. kimlik kartı veya pasaport'],
                    ['ad' => 'Oy kullanırken: T.C. kimlik numaralı bir kimlik belgesi', 'not' => 'Yabancı ülke belgeleri (ehliyet vb.) kimlik tespitinde GEÇERLİ DEĞİL'],
                ],
                'sure_metni' => 'Otomatik oluşur, kesin süre belirtilmemiş',
                'ucret_metni' => 'Ücretsiz',
                'resmi_kaynak_url' => 'https://secmen.ysk.gov.tr/secmen-yurtdisi',
                'notlar' => 'İKİ KATMANLI sistem (298 sK.): (1) Yurt Dışı Seçmen Kütüğü, nüfus kaydındaki "yerleşim yeri" (adres kaydı/ADNKS) bilgisine göre YSK tarafından OTOMATİK oluşturulur — ayrı bir "seçmen kaydı başvurusu" yoktur. (2) Adres kaydının doğru/yurt dışı görünmesi için Adres Beyan Formu (Form B) ile konsolosluğa/nüfus müdürlüğüne başvurmak gerekir. KRİTİK: kayıt olduğuna dair HİÇBİR bildirim/mektup gönderilmez — vatandaş kendi kaydını secmen.ysk.gov.tr/secmen-yurtdisi, e-Devlet ya da YSK çağrı merkezinden (444 9 975) KENDİSİ kontrol etmelidir. Yurt dışı seçmenler yalnız Cumhurbaşkanlığı/milletvekili genel seçimi/referandumda oy kullanabilir — YEREL SEÇİMLERDE (belediye/muhtarlık) OY KULLANAMAZ. Oy iki yöntemle kullanılır: temsilcilikte sandıkta ya da gümrük kapısında; MEKTUPLA OY 2008\'de Anayasa Mahkemesi kararıyla iptal edildi, elektronik oy yasada var ama hiç kullanılmıyor.',
            ],
        ];

        $turler = [];
        $baslangicSira = (int) IslemTuru::query()->max('sort_order') + 1;
        foreach ($yeniTurler as $i => $t) {
            $turler[$t['slug']] = IslemTuru::query()->firstOrCreate(
                ['slug' => $t['slug']],
                [
                    'ad' => $t['ad'], 'aciklama' => $t['aciklama'],
                    'is_active' => true, 'sort_order' => $baslangicSira + $i,
                ],
            );
        }

        foreach ($temsilcilikler as $temsilcilik) {
            foreach ($yeniTurler as $t) {
                TemsilcilikIslemi::query()->updateOrCreate(
                    ['temsilcilik_id' => $temsilcilik->id, 'islem_turu_id' => $turler[$t['slug']]->id],
                    [
                        'evraklar' => $t['evraklar'],
                        'sure_metni' => $t['sure_metni'],
                        'ucret_metni' => $t['ucret_metni'],
                        'resmi_kaynak_url' => $t['resmi_kaynak_url'],
                        'notlar' => $t['notlar'],
                        'status' => TemsilcilikIslemi::STATUS_TASLAK,
                    ],
                );
            }
        }
    }
}
