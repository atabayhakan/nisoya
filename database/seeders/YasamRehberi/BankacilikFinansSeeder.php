<?php

namespace Database\Seeders\YasamRehberi;

use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonusu;
use Illuminate\Database\Seeder;

/**
 * Yaşam Rehberi F1 — Bankacılık & Finans içeriği (2026-08-21).
 *
 * 25 hücre (5 konu x 5 ülke hedefinden) AI araştırma + bağımsız
 * doğrulama ajanlarıyla üretildi (Workflow, resmi/güvenilir kaynaklardan —
 * BaFin, Verbraucherzentrale, Consumentenbond, FMA/OeNB, DGDDI, Wikifin.be
 * vb.). 0 hücre kaynak bulunamadığı için üretilmedi (bkz. altta).
 *
 * TASLAK OLARAK GİRER (K7): status=taslak, dogrulanma_tarihi=null. Ajanların
 * kendi doğrulaması bu tarihi DOLDURMAZ — sahip panelden son onayı verip
 * yayına aldığında dolar (Ülke Rehberi'ndeki aynı kapı).
 *
 * MUHAFAZAKÂR (Ülke Rehberi seeder deseni): yalnız kaydı YOKSA oluşturur;
 * panelden yapılan düzenlemeleri deploy'da EZMEZ.
 */
class BankacilikFinansSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = YasamKategorisi::query()->where('slug', 'bankacilik-finans')->firstOrFail();

        $konular = [
            YasamKonusu::query()->firstOrCreate(
                ['kategori_id' => $kategori->id, 'slug' => 'ssnsiz-hesap-acma'],
                ['baslik' => 'Vergi numarası/SSN olmadan banka hesabı açma', 'is_active' => true, 'sort_order' => 1],
            ),
            YasamKonusu::query()->firstOrCreate(
                ['kategori_id' => $kategori->id, 'slug' => 'kredi-gecmisi-olmadan-kredi-karti'],
                ['baslik' => 'Kredi geçmişi olmadan kredi kartı alma', 'is_active' => true, 'sort_order' => 2],
            ),
            YasamKonusu::query()->firstOrCreate(
                ['kategori_id' => $kategori->id, 'slug' => 'turkiyeye-para-transferi'],
                ['baslik' => 'Türkiye\'ye/\'den para transferi: en ucuz ve hızlı yollar', 'is_active' => true, 'sort_order' => 3],
            ),
            YasamKonusu::query()->firstOrCreate(
                ['kategori_id' => $kategori->id, 'slug' => 'ogrenci-genc-hesabi'],
                ['baslik' => 'Öğrenci/genç hesabı seçenekleri', 'is_active' => true, 'sort_order' => 4],
            ),
            YasamKonusu::query()->firstOrCreate(
                ['kategori_id' => $kategori->id, 'slug' => 'online-banka-mi-geleneksel-mi'],
                ['baslik' => 'Online banka mı geleneksel banka mı: yeni gelen için hangisi kolay', 'is_active' => true, 'sort_order' => 5],
            ),
        ];

        /** @var array<string, YasamKonusu> $konuHaritasi */
        $konuHaritasi = collect($konular)->keyBy('slug');

        $icerikler = [
            [
                'konuSlug' => 'ssnsiz-hesap-acma',
                'country_code' => 'DE',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Genel Kural: Steuer-ID (Vergi Kimlik No) Hesap Açmak İçin Şart Değil',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'BaFin\'in (Almanya Federal Finansal Denetim Kurumu) resmi Basiskonto sayfasına göre, Avrupa Birliği\'nde yasal olarak ikamet eden/bulunan herkesin bir \'Basiskonto\' (temel banka hesabı) açma hakkı vardır. BaFin\'in hesap açılışı için listelediği belgeler yalnızca kimlik belgelerinden oluşur; Steuer-ID (Steueridentifikationsnummer) ya da ikamet kaydı (Anmeldung/Meldebescheinigung) bu belgeler arasında sayılmaz ve hesap açılışının ön koşulu değildir.',
                    ],
                    2 => [
                        'tip' => 'baslik',
                        'metin' => 'Hesap Açmak İçin Gereken Belgeler',
                    ],
                    3 => [
                        'tip' => 'madde',
                        'metin' => 'Geçerli pasaport veya fotoğraflı resmi kimlik kartı (iltica süreci devam edenler için Aufenthaltsgestattung; sınır dışı edilmesi geçici olarak durdurulmuş/\'tolere edilen\' kişiler [Geduldete] için Duldungsbescheinigung; bu belgeler henüz düzenlenmemiş yeni sığınmacılar için Ankunftsnachweis/varış belgesi de kabul edilir)',
                    ],
                    4 => [
                        'tip' => 'madde',
                        'metin' => 'Sabit bir adresiniz yoksa bir tanıdık, aile üyesi veya danışma merkezi adına kayıtlı posta adresi yeterlidir; resmi ikamet kaydı (Meldeadresse) banka tarafından şart koşulamaz',
                    ],
                    5 => [
                        'tip' => 'baslik',
                        'metin' => 'Vergi Kimlik Numarası Hesap Açıldıktan Sonra da Verilebilir',
                    ],
                    6 => [
                        'tip' => 'paragraf',
                        'metin' => 'Almanya Federal Merkezi Vergi Dairesi\'nin (Bundeszentralamt für Steuern) kurallarına göre bankalar her müşterinin Steuer-ID\'sini kaydetmekle yükümlüdür, fakat bu bilginin iş ilişkisi kurulurken elde hazır olması gerekmez; yasa bankaya bunu \'en geç üç ay içinde\' (gerekirse BZSt\'ye elektronik sorgu yoluyla) tamamlama süresi tanır. Yani Steuer-ID mektubunuz elinize henüz ulaşmamış olsa bile hesabınızı açabilir, numara geldiğinde bankaya bildirebilirsiniz.',
                    ],
                    7 => [
                        'tip' => 'baslik',
                        'metin' => 'Banka Sizi Reddederse: BaFin\'e Ücretsiz Başvuru Hakkı',
                    ],
                    8 => [
                        'tip' => 'paragraf',
                        'metin' => 'Almanya\'nın önde gelen tüketici koruma kuruluşu Verbraucherzentrale\'nin yeni gelenler için hazırladığı rehbere göre, temel ödeme hizmeti sunan tüm banka ve tasarruf sandıkları (Sparkasse) yasal olarak Basiskonto açmakla yükümlüdür; bu hak sığınmacıları, tolere edilenleri (Geduldete) ve sabit adresi olmayanları da kapsar. Başvurunuz geçerli bir sebep gösterilmeden reddedilir veya yanıtsız bırakılırsa, başvurunuzun ve ret yazısının birer kopyasıyla BaFin\'e ücretsiz şikâyette bulunup hesabınızın açılmasını talep edebilirsiniz.',
                    ],
                    9 => [
                        'tip' => 'paragraf',
                        'metin' => 'Bankanın Basiskonto başvurunuzu reddedebileceği yasal gerekçeler sınırlıdır ve Ödeme Hesapları Kanunu\'nda (Zahlungskontengesetz) önceden tanımlanmıştır — örneğin başka bir bankada hâlihazırda kullanabildiğiniz bir temel hesabınızın bulunması, bankaya karşı kasıtlı işlenmiş bir suçtan mahkûm olmanız, önceki bir temel hesabınızı yasa dışı amaçla kullanmış olmanız, kimlik doğrulamasının (kara para aklamayı önleme mevzuatı gereği) yapılamaması veya önceki bir temel hesabınızın ödenmemiş ücretler nedeniyle bankaca feshedilmiş olması gibi durumlardır. Kötü kredi notu (Schufa) veya düşük gelir TEK BAŞINA geçerli bir ret sebebi DEĞİLDİR. Sayılan gerekçelerden biri yokken banka yine de reddeder veya yanıt vermezse, yukarıdaki BaFin başvuru yolu işletilebilir.',
                    ],
                ],
                'kaynak_url' => 'https://www.bafin.de/DE/verbraucherinnen-verbraucher/themen-finanzprodukte/konten-zahlungen/konten/basiskonto/basiskonto.html',
                'kaynak_aciklama' => 'BaFin (Almanya Federal Finansal Denetim Kurumu) – Basiskonto (Temel Banka Hesabı) resmi bilgi sayfası; bağımsız olarak yeniden erişildi ve doğrulandı (gerekli belge listesinde sadece kimlik belgeleri var — Steuer-ID/Anmeldung yok; Duldungsbescheinigung [§60a AufenthG] / Aufenthaltsgestattung [§63 AsylG] / Ankunftsnachweis kabul ediliyor; sabit adresi olmayanlar için posta adresi yeterli, Melderegister kaydı şart değil; BaFin idari başvurusu için başvuru+ret yazısı kopyası isteniyor ve süreç ücretsiz). Çapraz doğrulama: Bundeszentralamt für Steuern (BZSt) Kontenwahrheit sayfası (https://www.bzst.de/DE/Unternehmen/Kontenwahrheit/kontenwahrheit_node.html) — Steuer-ID\'nin hesap açıldıktan sonra bankaca en geç 3 ay içinde (MAV/Maschinelles Anfrageverfahren ile) tamamlanabileceğini doğruluyor. Verbraucherzentrale\'nin "Neu in Deutschland Angekommene" rehberi (https://www.verbraucherzentrale.de/wissen/geld-versicherungen/sparen-und-anlegen/das-recht-auf-ein-basiskonto-fuer-neu-in-deutschland-angekommene-12224) ve Basiskonto SSS sayfası (https://www.verbraucherzentrale.de/wissen/geld-versicherungen/sparen-und-anlegen/fragen-und-antworten-zum-basiskonto-16610) — sığınmacı/Geduldete/sabit adresi olmayan kişilerin hakkını, ücretsiz BaFin başvurusunu ve bankanın sınırlı/tanımlı yasal ret gerekçelerini (kötü Schufa notunun geçerli bir ret sebebi olmadığı dahil) doğruluyor. Düzeltme notu: taslaktaki "Duldungsbescheinigung = geçici koruma statüsü" eşlemesi hatalıydı; Duldungsbescheinigung tolere edilenlere (Geduldete) ait bir belgedir, "geçici koruma" farklı bir hukuki statüdür — düzeltildi.',
            ],
            [
                'konuSlug' => 'ssnsiz-hesap-acma',
                'country_code' => 'NL',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'BSN Olmadan Banka Hesabı Açmak Mümkün mü?',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Evet, ama yalnızca birkaç banka bunu sunuyor. ABN AMRO, ING ve bunq; Hollanda\'ya yeni taşınan ve belediyeden (gemeente) BSN (Burgerservicenummer – vatandaş hizmet numarası, aynı zamanda vergi kimlik numarası olarak da kullanılır) almayı bekleyen kişilerin, bu süreçte bile banka hesabı açmasına izin veriyor.',
                    ],
                    2 => [
                        'tip' => 'madde',
                        'metin' => 'ABN AMRO: Geçerli kimlik belgesi, Hollanda\'daki ikamet adresinin kanıtı ve başka bir ülkeden alınmış vergi/mali kimlik numarası (TIN/FIN) ile mobil uygulama üzerinden yaklaşık 10 dakikada başvuru tamamlanabiliyor; ABD vatandaşlarının ise (FATCA/uyum kuralları nedeniyle) uygulama yerine bir şube randevusu alması gerekiyor.',
                    ],
                    3 => [
                        'tip' => 'madde',
                        'metin' => 'ING: Pasaport veya ikamet izniyle (residence permit) ING Mobil Bankacılık uygulaması üzerinden BSN olmadan normal bir ING hesabı açılabiliyor.',
                    ],
                    4 => [
                        'tip' => 'madde',
                        'metin' => 'bunq: Yalnızca geçerli kimlik veya pasaport yeterli; BSN başlangıçta istenmiyor ve Hollanda\'ya fiilen taşınmadan önce bile uygulama üzerinden hesap açılabiliyor.',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'Kritik kural üçünde de aynı: Hesabı açtıktan sonra BSN\'nizi bankaya en geç 90 gün içinde bildirmezseniz hesabınız bloke edilir. (ABN AMRO özelinde bazı kaynaklar, 120 gün sonunda hâlâ BSN iletilmemişse müşteri ilişkisinin tamamen sonlandırılabileceğini de belirtiyor.) Bu yüzden banka başvurusuyla birlikte belediyeden BSN randevusunu da geciktirmeden almak önemli.',
                    ],
                ],
                'kaynak_url' => 'https://dutchreview.com/financial/dutch-bank-accounts-without-a-bsn/',
                'kaynak_aciklama' => 'DutchReview.com — Hollanda\'daki yabancılar için köklü, güvenilir bir rehber/haber platformu. Bu makale, taslaktaki gibi ABN AMRO, ING ve bunq\'u (ayrıca Revolut\'u) tek yazıda karşılaştırıp üçü için de 90 günlük BSN bildirim kuralını doğruluyor — orijinal taslakta gösterilen tek IamExpat linkinin aksine (o makale SADECE ABN AMRO\'yu ele alıyor, ING/bunq/ABD vatandaşı konularına hiç değinmiyor). Ek doğrulama kaynakları: ABN AMRO\'ya özgü TIN/FIN şartı, ~10 dakikalık app süreci ve ABD vatandaşlarının şube randevusu alması gerektiği bilgisi IamExpat\'ın "Expats can finally open a bank account without a BSN" makalesinde (https://www.iamexpat.nl/expat-info/dutch-news/expats-can-finally-open-bank-account-without-bsn) ve DutchNews.nl\'nin 2023 haberinde (https://www.dutchnews.nl/2023/07/open-a-bank-account-without-a-bsn-bank-helps-new-expats/) doğrudan teyit ediliyor; bunq\'un 90 günlük kuralı bunq\'un kendi resmi expat sayfasında ("provide it within 90 days", https://www.bunq.com/en-nl/expats) yazıyor; ING\'nin pasaport/ikamet izniyle app üzerinden süreci DutchReview\'in ING\'ye özel makalesinde (https://dutchreview.com/expat/ing-bank-account-without-bsn/) detaylandırılıyor.',
            ],
            [
                'konuSlug' => 'ssnsiz-hesap-acma',
                'country_code' => 'FR',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Kısa cevap: Vergi numarası ya da sosyal güvenlik numarası aranmıyor',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Fransa\'da banka hesabı açmak için Fransız vergi numarası (numéro fiscal/NIF) veya sosyal güvenlik numarası (numéro de sécurité sociale) yasal olarak şart değildir. Banque de France\'ın \'hesap açma hakkı\' (droit au compte) için gereken belgeleri belirleyen 31 Temmuz 2015 tarihli resmi kararnamesinde (Légifrance) bu iki numaradan hiçbiri istenen belgeler listesinde yer almaz; sadece kimlik ve ikametgah belgesi geçerlidir.',
                    ],
                    2 => [
                        'tip' => 'paragraf',
                        'metin' => 'Pratikte bankalar sizden şunları ister: fotoğraflı geçerli bir kimlik belgesi (pasaport veya ikamet izni/titre de séjour) ve 3 aydan eski olmayan bir ikametgah belgesi (elektrik/su/internet faturası, kira makbuzu; kendi adınıza fatura yoksa birlikte yaşadığınız kişiden alınan \'attestation d\'hébergement\' de kabul edilir). Bu koşullar Fransız veya yabancı olmanızdan bağımsız olarak aynıdır.',
                    ],
                    3 => [
                        'tip' => 'paragraf',
                        'metin' => 'Bir banka hesap açma talebinizi reddederse (ya da 15 gün içinde cevap vermezse), service-public.gouv.fr\'e göre ret yazısı/gönderi kanıtı, kimlik ve ikametgah belgenizle Banque de France\'a \'droit au compte\' başvurusu yapabilirsiniz; Banque de France dosya tam olduğunda 1 iş günü içinde sizin için bir banka belirler ve belirlenen banka en geç 3 iş günü içinde temel bankacılık hizmetleri sunan bir hesap açmakla yükümlü olur. Bu başvuru dosyasında da vergi veya sosyal güvenlik numarası istenmez.',
                    ],
                    4 => [
                        'tip' => 'paragraf',
                        'metin' => 'Bazı bankaların sorduğu \'vergi mukimliği ülkesi / vergi kimlik numarası\' sorusu, göründüğünden daha bağlayıcıdır: bu, hesap açılışından bağımsız veya atlanabilir bir form değildir. Genel Vergi Kanunu\'nun 1649 AC maddesini uygulamaya koyan 5 Aralık 2016 tarihli 2016-1683 sayılı kararnamenin 46. maddesi, bankaların yeni hesap açılışı SIRASINDA bir vergi mukimliği öz-beyanı (auto-certification/CRS) almasını şart koşar; Fransız vergi idaresinin resmi doktrini BOFIP\'e göre (BOI-INT-AEA-20-20-30, §55) bu beyan hiç verilmezse banka sözleşme ilişkisini kurmayı, yani hesabı açmayı, reddetmek ZORUNDADIR. Ama bu form Fransız vergi numarası istemez: formda gerçek vergi mukimliğinizi (örneğin Türkiye) ve varsa oradaki vergi kimlik numaranızı beyan edersiniz; ülkeniz böyle bir numara vermiyorsa ya da elinizde yoksa \'numaram yok\' kutucuğunu işaretleyip nedenini belirtmeniz yeterlidir — bu durum hesap açılışını engellemez. impots.gouv.fr\'nin ve BOFIP\'in ayrıca belirttiği \'1.500 avroya kadar ceza\' ile \'bankanın 30 gün içinde talep, sizin 60 gün içinde cevap vermeniz\' kuralı ise farklı bir senaryo içindir: zaten sunulmuş bir öz-beyanın bankaya \'inandırıcı görünmemesi\' (non plausible) hâlinde devreye giren sonradan düzeltme sürecidir — Türkiye\'de vergi mukimi olduğunuzu doğru şekilde beyan eden biri için söz konusu olan bir risk değildir.',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'Henüz elinizde ikametgah belgesi bile yoksa (yeni geldiyseniz), resmi nickel.eu sayfasına göre \'Compte Nickel\' ödeme hesabı sadece geçerli bir kimlik belgesiyle (190\'dan fazla ülkenin pasaportu ve ikamet izni dahil) açılabiliyor; adres kanıtı, gelir belgesi veya vergi numarası istenmiyor ve hesap Fransa genelindeki tütüncü (bureau de tabac) noktalarında 5-10 dakikada açılabiliyor. Bu bir \'ödeme hesabı\' (compte de paiement) statüsündedir; klasik bir banka değildir ama IBAN ve kartla günlük kullanımda banka hesabı gibi işlev görür.',
                    ],
                ],
                'kaynak_url' => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F2417',
                'kaynak_aciklama' => 'Ana kaynak service-public.gouv.fr\'nin "Refus d\'ouverture de compte bancaire : droit au compte" sayfası (F2417) — droit au compte süreci, belge listesi ve süreler (1 iş günü/3 iş günü/15 gün) için bağımsızca doğrulandı, doğru. Çapraz doğrulama kaynakları da doğrulandı: Légifrance\'daki droit au compte kararnamesi https://www.legifrance.gouv.fr/loda/id/LEGITEXT000031024044/ (güncel madde metni: https://www.legifrance.gouv.fr/loda/article_lc/LEGIARTI000045894027, son güncelleme 13 Haziran 2022) — belge listesinde vergi/SSN numarası yok; Nickel\'in resmi belge sayfası https://nickel.eu/fr/documents-pour-ouvrir-compte-bancaire.

DÜZELTME için eklenen/kullanılan kaynaklar (taslaktaki impots.gouv.fr FAQ sayfası tek başına yanıltıcıydı, şu iki resmi kaynakla tamamlandı): (1) Décret n° 2016-1683 du 5 décembre 2016, Article 46 — https://www.legifrance.gouv.fr/loda/article_lc/LEGIARTI000033547501 — "une institution financière requiert LORS DE L\'OUVERTURE une auto-certification..."; (2) BOFIP (Fransız vergi idaresinin resmi doktrini) BOI-INT-AEA-20-20-30 — https://bofip.impots.gouv.fr/bofip/10298-PGP.html/identifiant=BOI-INT-AEA-20-20-30-20250723 — §55: "lorsqu\'une personne physique qui entend ouvrir un nouveau compte n\'auto-certifie pas ses résidences fiscales..., l\'institution financière doit refuser d\'établir la relation contractuelle" (yani öz-beyan verilmezse banka hesap açmayı reddetmek zorunda — taslağın "hesap açma şartı değil" ifadesinin tersi). impots.gouv.fr sayfası (https://www.impots.gouv.fr/particulier/questions/pourquoi-ma-banque-me-demande-de-lui-fournir-les-informations-concernant-ma) hâlâ geçerli ama tek başına bu nüansı vermiyor, yukarıdaki iki resmi kaynakla birlikte okunmalı.</duzeltilmisKaynakAciklama>
',
            ],
            [
                'konuSlug' => 'ssnsiz-hesap-acma',
                'country_code' => 'BE',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Belçika\'da "vergi numarası" neye karşılık gelir?',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belçika\'da ABD tipi tek bir SSN ya da vergi numarası yoktur. Onun yerine, belediyeye (gemeente/commune) nüfus kaydı yapıldığında verilen 11 haneli "Rijksregisternummer" (numéro de registre national / ulusal sicil numarası) kullanılır; bu numara hem vergi beyanı sisteminde hem sosyal güvenlikte birincil kimlik bilgisidir. Belçika\'da ikamet etmediği hâlde idareyle ilişkisi olan kişilere (örneğin sınır işçileri veya Belçika\'da mülkü olanlar) Sosyal Güvenlik Çapraz Bankası (KSZ/BCSS) tarafından aynı yapıda bir "BIS numarası" verilir.',
                    ],
                    2 => [
                        'tip' => 'baslik',
                        'metin' => 'Bu numara olmadan da yasal hesap açma hakkınız var',
                    ],
                    3 => [
                        'tip' => 'paragraf',
                        'metin' => 'AB\'nin 2014/92/EU sayılı Ödeme Hesapları Direktifi\'ni Belçika hukukuna aktaran Ekonomi Hukuku Kanunu\'nun (Code de droit économique) VII. Kitabı, madde VII.56/1–VII.59/3 (özellikle hak sahipliğini düzenleyen VII.57 §2) uyarınca, bir AB üye devletinde yasal olarak ikamet eden HERKES -vatandaşlık fark etmeksizin, Belçika\'da sabit bir ikametgahı olmasa ve henüz bir rijksregisternummer\'ı bulunmasa dahi- en az bir "temel bankacılık hizmeti" (basisbankdienst / service bancaire de base) hesabı açma hakkına sahiptir. Yasal koşullar sağlandığı ve kara para aklamayı/terörün finansmanını önleme mevzuatına aykırı bir durumunuz olmadığı sürece banka bu talebi ilke olarak reddedemez.',
                    ],
                    4 => [
                        'tip' => 'baslik',
                        'metin' => 'Kimlik için hangi belgeler yeterli?',
                    ],
                    5 => [
                        'tip' => 'madde',
                        'metin' => 'Pasaport veya fotoğraflı resmi kimlik kartı',
                    ],
                    6 => [
                        'tip' => 'madde',
                        'metin' => 'Elektronik yabancı oturum kartı (A\'dan F+\'ya kadar kart türleri)',
                    ],
                    7 => [
                        'tip' => 'madde',
                        'metin' => 'Kayıt belgesi / "attestation d\'immatriculation" (turuncu kart) gibi geçici ikamet belgeleri',
                    ],
                    8 => [
                        'tip' => 'madde',
                        'metin' => 'Sığınma başvurusu, mülteci/ikincil koruma statüsü ya da (Ukraynalı vatandaşlar için) geçici koruma belgesi',
                    ],
                    9 => [
                        'tip' => 'baslik',
                        'metin' => 'Bankanın sizden isteyemeyeceği şeyler',
                    ],
                    10 => [
                        'tip' => 'madde',
                        'metin' => 'Belçika\'da esas ikametgahınızın kanıtı',
                    ],
                    11 => [
                        'tip' => 'madde',
                        'metin' => 'Bir kefil veya garantör',
                    ],
                    12 => [
                        'tip' => 'madde',
                        'metin' => 'Başka bir ürün ya da hizmet satın alma şartı',
                    ],
                    13 => [
                        'tip' => 'madde',
                        'metin' => 'Mali ihtiyaç ya da yoksulluk kanıtı',
                    ],
                    14 => [
                        'tip' => 'baslik',
                        'metin' => '2026 için maliyet ve koşullar',
                    ],
                    15 => [
                        'tip' => 'paragraf',
                        'metin' => 'Temel bankacılık hizmeti mutlaka ücretsiz değildir: banka 2026 yılında (1 Ocak 2026\'dan itibaren geçerli tutarla) en fazla yıllık 20,34 avro talep edebilir; bu tavan her yıl tüketici fiyat endeksine göre güncellenir. Başvurabilmek için Belçika\'da hâlihazırda başka bir vadesiz hesabınızın veya temel bankacılık hizmetinizin OLMAMASI gerekir; ayrıca hem Belçika\'daki hesaplarınızdaki (tasarruf/vadeli mevduat gibi) toplam bakiyenin, hem de kredi sözleşmelerinizin -ayrı ayrı- 10.239,43 avronun altında kalması şarttır.',
                    ],
                ],
                'kaynak_url' => 'https://economie.fgov.be/fr/themes/services-financiers/services-de-paiement/service-bancaire-de-base/service-bancaire-de-base-pour-0',
                'kaynak_aciklama' => 'Belçika Ekonomi Bakanlığı\'nın (SPF Economie / FOD Economie) "particuliers için temel bankacılık hizmeti" (service bancaire de base) resmi sayfası; hak sahipliği koşulları (madde VII.56/1–VII.59/3, özellikle hak sahipliğini düzenleyen VII.57 §2), azami ücret ve tam ret/başvuru koşulları listesini (vadesiz hesap yokluğu, 10.239,43 € hesap+kredi eşiği, kara para aklama mevzuatına uyum) içerir. Bilgiler ayrıca FSMA\'nın tüketici sitesi Wikifin.be, banka federasyonu Febelfin.be, Belçika Federal Göç Merkezi Myria.be, hukuki bilgi platformu Vreemdelingenrecht.be (Vlaamse Balies/Vluchtelingenwerk Vlaanderen) ve doğrudan Argenta/CPH/Beobank bankalarının kendi hizmet sayfalarıyla çapraz doğrulanmıştır. Not: Wikifin.be sayfası aynı eşik için (muhtemelen güncellenmemiş) 6.000 € rakamı veriyor; bu tek nokta dışında tüm kaynaklar birbiriyle ve resmi sayfayla örtüşüyor.',
            ],
            [
                'konuSlug' => 'ssnsiz-hesap-acma',
                'country_code' => 'AT',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Vergi numarası/sosyal güvenlik numarası şart değil: "Basiskonto" hakkı',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => '18 Eylül 2016\'dan bu yana Avusturya\'da yasal bir düzenleme yürürlükte: Avrupa Birliği sınırları içinde yasal olarak ikamet eden her tüketici, bir Avusturya bankasında "temel işlevli ödeme hesabı" (Basiskonto) açma hakkına sahiptir. Bu hakkı kullanmak için Avusturya vergi numarası (Steuernummer) veya sosyal güvenlik numarası (Sozialversicherungsnummer) sahibi olmak yasal bir ön koşul değildir.',
                    ],
                    2 => [
                        'tip' => 'baslik',
                        'metin' => 'Kimler başvurabilir',
                    ],
                    3 => [
                        'tip' => 'paragraf',
                        'metin' => 'Bu hak yalnızca AB vatandaşlarıyla sınırlı değildir; Avusturya\'da yasal ikamet hakkı bulunan üçüncü ülke vatandaşlarını da kapsar — örneğin öğrenci vizesi, çalışma izni veya aile birleşimiyle ikamet eden Türk vatandaşları. Başka resmi kimlik belgesi bulunmayan sığınma başvurucuları bile, Avusturya iltica hukukuna göre düzenlenmiş "Verfahrenskarte" (işlem kartı, §50 AsylG), ikamet yetki kartı (§51 AsylG), yardımcı koruma kartı (§52 AsylG) veya hoşgörülü ikamet kartıyla (§46a FPG) başvurabilir.',
                    ],
                    4 => [
                        'tip' => 'baslik',
                        'metin' => 'Hesap açmak için fiilen istenen belgeler',
                    ],
                    5 => [
                        'tip' => 'madde',
                        'metin' => 'Geçerli fotoğraflı kimlik belgesi (pasaport veya kimlik kartı)',
                    ],
                    6 => [
                        'tip' => 'madde',
                        'metin' => 'İkamet kayıt belgesi (Meldezettel) — özellikle şubede yapılan başvurularda genellikle istenir',
                    ],
                    7 => [
                        'tip' => 'madde',
                        'metin' => 'Vergi numarası veya sosyal güvenlik numarası, zorunlu belge listesinde yer almaz',
                    ],
                    8 => [
                        'tip' => 'baslik',
                        'metin' => 'Bankanın başvuruyu reddedebileceği yalnızca iki durum',
                    ],
                    9 => [
                        'tip' => 'paragraf',
                        'metin' => 'Banka, Basiskonto başvurusunu ancak şu iki durumda reddedebilir: kişinin Avusturya\'da hâlihazırda kullanılabilir bir başka çek hesabı (Girokonto) varsa, veya bankaya ya da çalışanlarına karşı kasıtlı bir suçtan devam eden ceza davası ya da kesinleşmiş mahkûmiyeti varsa. Bunların dışında, eksiksiz başvurudan sonra hesap en geç 10 iş günü içinde açılmalıdır; reddedilirse banka gerekçeyi yazılı bildirmek ve Finanzmarktaufsicht (FMA) ile ilgili ombudsmana başvuru hakkını hatırlatmak zorundadır.',
                    ],
                    10 => [
                        'tip' => 'baslik',
                        'metin' => 'Hesabın kapsamı ve ücret tavanı',
                    ],
                    11 => [
                        'tip' => 'paragraf',
                        'metin' => 'Basiskonto; para yatırma/çekme, banka kartı, havale ve otomatik ödeme talimatı gibi temel işlemleri kapsar, kredi kartı ve hesap açığı (kredili mevduat) içermez. Yıllık hesap işletim ücreti standart kullanıcılar için en fazla 83,45 Euro; asgari geçim desteği alıcıları, öğrenciler veya evsizler gibi sosyal/ekonomik açıdan dezavantajlı kişiler için başvuru üzerine en fazla 41,73 Euro ile sınırlıdır.',
                    ],
                    12 => [
                        'tip' => 'baslik',
                        'metin' => 'Hesap açılırken sorulabilecek farklı bir şey: vergi mükellefliği beyanı',
                    ],
                    13 => [
                        'tip' => 'paragraf',
                        'metin' => 'Vergi numarası hesap açmanın ön koşulu olmasa da, uluslararası vergi şeffaflığı düzenlemesi (Gemeinsamer Meldestandard-Gesetz/GMSG — Common Reporting Standard) gereği banka, hesap açılışı sırasında hangi ülke ya da ülkelerde vergi mükellefi olduğunuzu ve o ülkedeki vergi kimlik numaranızı (TIN) beyan etmenizi isteyebilir. Bu, Avusturya\'da bir Steuernummer sahibi olmayı gerektirmez; vergi mükellefiyseniz kendi ülkenizin (örneğin Türkiye\'nin) numarasını bildirmeniz yeterlidir.',
                    ],
                ],
                'kaynak_url' => 'https://wien.arbeiterkammer.at/beratung/konsumentenschutz/geld/konto/Bankkonto_fuer_Jedermann.html ; https://www.finfo.at/konto/ein-basiskonto-fuer-alle-in-oesterreich/',
                'kaynak_aciklama' => 'Ana kaynak: wien.arbeiterkammer.at (Avusturya İşçi Odası, Konsumentenschutz bölümü) — bu sayfa doğrudan fetch edilerek şu iddialar birebir doğrulandı: 18 Eylül 2016 tarihi, AB\'de yasal ikamet hakkı olan tüketicilerin Basiskonto hakkı, iki ret gerekçesi (mevcut Girokonto / bankaya-çalışanlarına karşı kasıtlı suç), 10 iş günü süresi, ret halinde yazılı gerekçe + FMA/ombudsman bilgilendirme zorunluluğu, hesabın kapsamı (kredi kartı/kredili mevduat hariç) ve ücret tavanları (83,45 € standart / 41,73 € dezavantajlı, 2026 için hâlâ geçerli).

Zorunlu ikinci kaynak: finfo.at/konto/ein-basiskonto-fuer-alle-in-oesterreich/ — "Kimler başvurabilir" bölümündeki üçüncü ülke vatandaşları (Türk vatandaşları dahil) ve sığınmacıların "Verfahrenskarte", "Aufenthaltsberechtigungskarte", "Karte für subsidiär Schutzberechtigte", "Karte für Geduldete" ile başvurabileceği iddiası SADECE bu sayfada birebir aynı terimlerle geçiyor; AK Wien sayfası bu konuya hiç değinmiyor (doğrudan fetch ile teyit edildi — sayfada "Drittstaatsangehörige", "Asylwerber", "Verfahrenskarte" kelimeleri yok).

Ek doğrulama: raiffeisen.at/de/meine-bank/raiffeisen-bankengruppe/fatca---crs.html — CRS/GMSG paragrafı bu sayfadan birebir doğrulandı (yabancı TIN beyanının yeterli olduğu dahil).

Not: Taslağın kaynak listesindeki ABA–Work in Austria sayfası (workinaustria.com) doğrudan fetch edildi; bu sayfa Basiskonto veya üçüncü ülke/sığınmacı konusundan hiç bahsetmiyor, yalnızca standart hesap açma için Meldezettel+kimlik istendiğini destekliyor — üçüncü ülke vatandaşları/Verfahrenskarte iddiası için kaynak olarak kullanılamaz. FMA sayfası (fma.gv.at/konto/basiskonto) 403 hatasıyla doğrudan erişilemedi; dolaylı arama sonuçları tutarlı ama bağımsız doğrudan teyit yapılamadı.',
            ],
            [
                'konuSlug' => 'kredi-gecmisi-olmadan-kredi-karti',
                'country_code' => 'DE',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'SCHUFA\'sız \'gerçek\' kredi kartı diye bir şey yok',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Almanya\'da harcama limiti (Kreditrahmen) tanımlı bir kredi kartı hukuken bir kredi sözleşmesi sayılır; kredi kartı karşılaştırma platformu smava.de\'nin belirttiği gibi bankalar müşterinin ödeme gücünü (Bonitätsprüfung/SCHUFA sorgusu) değerlendirmeden böyle bir limit açamaz. Bu yüzden kredi geçmişi olmayanların gerçekte kullandığı çözüm \'Prepaid-Kreditkarte\' adıyla bilinen bakiye (guthaben) esaslı kartlardır: önce karta para yüklenir, Visa veya Mastercard logosuyla -online alışveriş dahil- dünya genelinde o bakiye kadar harcama yapılır, hesap asla eksiye düşürülemez ve gerçek bir kredi verilmediği için banka genellikle SCHUFA sorgusu yapmaz.',
                    ],
                    2 => [
                        'tip' => 'baslik',
                        'metin' => 'Doğrulanmış örnek: N26 Flex hesabı',
                    ],
                    3 => [
                        'tip' => 'madde',
                        'metin' => 'N26\'nın resmi sitesine göre N26 Flex hesabı ayda 8,90 € ücretlidir; SCHUFA sorgusu yapılmayan, temassız ödeme ile Apple Pay/Google Pay destekli bir Mastercard Debit kart sunar. Hesap özellikle olumsuz veya hiç SCHUFA geçmişi olmayan kişiler için tasarlanmıştır; başvuru yalnızca kimlik belgesiyle, şubeye gitmeden, akıllı telefonla birkaç dakikada tamamlanır ve minimum para yatırma şartı aranmaz.',
                    ],
                    4 => [
                        'tip' => 'baslik',
                        'metin' => 'Dikkat: Sahte \'SCHUFA\'sız kart\' vaatleri',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'Almanya\'da eyalet düzeyinde resmi olarak tanınan tüketici koruma kuruluşlarından biri olan Verbraucherzentrale Hamburg (Hamburg Tüketici Merkezi), gerçek kredi limitli ve SCHUFA sorgusuz kart vaat eden firmalara karşı somut vakalarla uyarıyor: \'Alpha Finanz Ltd.\' ve \'Optimize Consumer Service BV\' adlı firmalar 99,80-149,90 € arası \'çıkarma/iptal ücreti\' talep edip iade taleplerini reddetmiş; \'GlobalPayments BV\' ise 6.499 €\'ya kadar \'SofortKredit\' ve MasterCard Gold vaat edip yalnızca 49,90 € kart çıkarma ücreti + 10 € kargo ücreti karşılığında sıradan bir prepaid kart göndermiştir. Ayrıca \'VeriPay\' adlı firma izinsiz soğuk telefon aramalarıyla kredi kartı pazarlamış; kapıda ödemeli (Nachnahme, ~100 €) gönderiyi kabul etmeyenlere ise tahsilat şirketi Euro Collect GmbH üzerinden \'ödenmemiş kart siparişi\' gerekçesiyle yaklaşık 180 € talep edilmiştir. Kurumun tavsiyesi: sözleşme şartlarını başvurmadan önce dikkatle okuyun, istenmeyen/soğuk telefon aramalarıyla gelen \'SCHUFA\'sız kart\' vaatlerine güvenmeyin, kapıda ödemeli gönderileri kabul etmeden önce yazılı itiraz/fesih hakkınızı kullanın ve bir mahkeme kararı/ödeme emri gelmeden ödeme yapmayın.',
                    ],
                ],
                'kaynak_url' => 'https://www.vzhh.de/themen/finanzen/kredit/teure-kreditkarte-statt-schufafreier-kredit',
                'kaynak_aciklama' => 'Almanya\'da eyalet düzeyinde resmi olarak tanınan tüketici koruma kuruluşlarından biri olan Verbraucherzentrale Hamburg\'un (Stand/son güncelleme: 07.03.2024) "SCHUFA\'sız kredi kartı" vaatleri hakkındaki uyarı sayfası — doğrudan iki kez tarandı. Sayfa taslakta atlanan üçüncü bir vakayı da içeriyor: "VeriPay" firmasının izinsiz soğuk telefon aramaları ve tahsilat şirketi Euro Collect GmbH üzerinden ~180 € talebi. İçerik ayrıca N26\'nın resmi ürün sayfası (https://n26.com/de-de/konto-trotz-schufa — doğrudan tarandı; 8,90 €/ay, SCHUFA sorgusuz, Mastercard Debit, Apple/Google Pay, şubesiz başvuru, minimum yatırım şartı yok, tümü doğrulandı) ve kredi kartı karşılaştırma platformu smava.de\'nin sayfası (https://www.smava.de/kreditkarte/ohne-bonitaetspruefung/ — doğrudan tarandı; "Ein echter Kreditrahmen... gilt rechtlich als Kreditvergabe. Und dafür ist die Bonitätsprüfung Pflicht" ve prepaid kartların SCHUFA\'ya girmediği birebir alıntılarla teyit edildi) ile çapraz doğrulanmıştır.',
            ],
            [
                'konuSlug' => 'kredi-gecmisi-olmadan-kredi-karti',
                'country_code' => 'NL',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Hollanda\'da \'kredi skoru\' değil, BKR kaydı var',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => '1965\'ten beri faaliyette olan Stichting BKR (Bureau Krediet Registratie), 250 Euro üzerindeki ve 1 aydan uzun vadeli kredi sözleşmelerini (kişisel kredi, taşıt kredisi, taksitli ödeme seçeneği kullanılan kredi kartları vb.) ve ödeme gecikmelerini merkezi bir sistemde (CKI) toplar; olumsuz kayıtlar borç kapandıktan sonra bile 5 yıl boyunca görünür kalır. Önemli bir ayrıntı: faturasını her ay tam ve zamanında kapatan standart bir kredi kartı genelde BKR\'ye hiç kaydedilmez — kayıt ancak kartta taksitli/uzatmalı (\'gespreid betalen\') ödeme seçeneği kullanıldığında veya bir ödeme temerrüdü oluştuğunda devreye girer. Türkiye\'deki gibi zamanla pozitif puan biriktirdiğiniz bir \'kredi skoru\' da yoktur; sistem esas olarak sorunlu ödeme geçmişini işaretlemek için vardır.',
                    ],
                    2 => [
                        'tip' => 'paragraf',
                        'metin' => 'ICS Cards ve göçmen rehberi DutchReview\'a göre standart bir kredi kartı başvurusunda BKR kontrolü kanunen zorunludur; geçmiş ödeme gecikmesi gibi olumsuz bir kaydınız varsa bu silinmeden başvurunuz kabul edilmez. Hollanda\'ya yeni gelmiş ve ülkede hiç kredi kullanmamış birinin bu anlamda olumsuz bir kaydı olmaz, ama bankanın dayanacağı bir ödeme geçmişi de bulunmadığından başvuru yine gelir ve esas olarak gelir durumunuza (ayrıca ikamet ve BSN kaydınıza) göre değerlendirilir.',
                    ],
                    3 => [
                        'tip' => 'madde',
                        'metin' => 'Örnek şart seti (ICS Visa World Card): Hollanda\'da ikamet adresi, geçerli kimlik belgesi, BSN (burgerservicenummer — vergi dairesi ve mevduat garanti sistemi mevzuatı gereği pratikte zorunlu; olmadan başvuru kabul edilmiyor), Avrupa IBAN\'lı herhangi bir bankadan hesap (belirli bir bankaya bağlı değil) ve asgari net 1.500 Euro/ay gelir; onaylananlara 1.000-5.000 Euro arası harcama limiti ve yıllık 42,95 Euro kart ücreti uygulanıyor.',
                    ],
                    4 => [
                        'tip' => 'paragraf',
                        'metin' => 'Bu gelir eşiğini karşılayamıyorsanız somut iki yol var: ICS\'e göre önceden kendi paranızı yüklediğiniz, gelir/BKR şartı aranmayan \'prepaid\' kredi kartı, ya da bir eşin/aile üyesinin mevcut kartına \'ek kart\' (extra card) olarak eklenmek. DutchReview\'a göre ayrıca ING gibi bazı bankalar gelir şartını tam karşılamayan başvuruculara da kart verebiliyor, ancak bu durumda harcama limiti belirgin biçimde düşürülüyor.',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'Expatica\'nın rehberine göre Hollanda\'da günlük harcamalarda zaten kredi kartından çok banka kartı (pinpas) ve iDEAL kullanılıyor; kredi kartı daha çok seyahat ve büyük harcamalar için tercih ediliyor. Bankaların kendi güncel tarifelerine bakıldığında daha düşük ücretli standart kartlara örnek: ING (aylık yaklaşık 2 Euro), Rabocard (aylık yaklaşık 2 Euro) ve ABN AMRO (aylık 2,55 Euro) kredi kartları — Expatica\'nın karşılaştırdığı ICS Visa World Card (yıllık 42,95 Euro) ya da ING Platinum Card (yıllık 52,20 Euro) gibi premium kartlara kıyasla belirgin biçimde ucuz.',
                    ],
                ],
                'kaynak_url' => 'https://www.icscards.nl/tips/creditcard-aanvragen-zonder-bkr',
                'kaynak_aciklama' => 'Ana kaynak (icscards.nl/tips/creditcard-aanvragen-zonder-bkr) doğrulandı ve gerçek; BKR kontrolünün zorunlu olduğu ve olumsuz kayıtla başvurunun kabul edilmediği bilgisi doğrudan bu sayfadan teyit edildi. Çapraz doğrulama için bağımsızca incelenenler: ICS Visa World Card başvuru/şart sayfası (icscards.nl/creditcard-aanvragen/visa-world-card — €1.500/ay gelir, €1.000-5.000 limit, €42,95/yıl ücret, herhangi bir Avrupa IBAN\'ı kabul edildiği teyit edildi), ICS\'in "creditcard zonder inkomen" sayfası (prepaid kart ve "Extra Card" seçenekleri teyit edildi), ICS\'in BSN sayfası (icscards.nl/info/burgerservicenummer — BSN verilmezse başvurunun reddedildiği/kartın iptal edildiği teyit edildi, taslakta eksikti ve eklendi), ICS\'in "creditcard aanvragen met BKR-registratie" sayfası ve ANWB (anwb.nl/creditcard/informatie/bkr-toetsing) (standart, ayda tam ödenen kredi kartlarının BKR\'ye kaydedilmediği bilgisi — taslağın ilk paragrafındaki eksik nüans buradan düzeltildi), resmi kredi kayıt kurumu Stichting BKR (bkr.nl) ve BKR\'nin kendi tüketici portalı mijnkredietregistratie.nl ile hükümet destekli finansal okuryazarlık platformu wijzeringeldzaken.nl (€250 eşiği, 1 aydan uzun vade, 5 yıl saklama teyit edildi — nl.wikipedia.org\'daki BKR maddesi vadeyi hatalı biçimde "3 ay" veriyor, bu iki güncel/uzman kaynakla çelişiyor ve güvenilmemeli), göçmen rehberleri Expatica (expatica.com/nl/finance/money-management/best-credit-cards-in-the-netherlands-2173641 — yalnız Amex Gold, ING Platinum €52,20/yıl, ICS Visa World, ANWB Visa Classic, ICS Mastercard Gold\'u karşılaştırıyor; pinpas/iDEAL\'in günlük kullanımda daha yaygın olduğu bilgisi buradan doğru alınmış) ve DutchReview (dutchreview.com/expat/best-credit-cards-netherlands — BKR kontrolünün zorunluluğu ve ING\'nin düşük gelirle de indirimli limitli kart verebilmesi teyit edildi), ayrıca bankaların kendi güncel tarife sayfaları (ABN AMRO €2,55/ay, ING\'nin Ocak 2026 zammı sonrası €2,00/ay, Rabobank Standaard paketiyle RaboCard €2/ay). NOT: Taslağın son paragrafı ING/Rabocard/ABN AMRO rakamlarını hatalı biçimde "Expatica\'nın rehberine göre" diye gösteriyordu — incelenen Expatica makalesi bu üç karttan hiç bahsetmiyor. Rakamların kendisi doğru çıktı ama kaynak ataması yanlıştı; düzeltilmiş metinde bu rakamlar bankaların kendi tarifelerine atfedildi. Küçük not: ICS resmi olarak ABN AMRO Bank\'ın bir iştirakidir (nl.wikipedia.org/wiki/International_Card_Services), taslağın betimlediği gibi tam "bağımsız" bir şirket değildir — piyasada kendini bankadan ayrı, uzman bir kredi kartı ihraççısı olarak konumlandırır. Ek bilgi: BKR\'yi yasal zemine oturtacak "Wet stelsel kredietregistratie" taslağı 2023\'ten beri görüşülüyor (bkr.nl basın bültenleri, autoriteitpersoonsgegevens.nl); yürürlüğe girerse saklama süresini 5 yıldan 3 yıla indirebilir, ama Ağustos 2026 itibarıyla henüz kanunlaşmamış görünüyor.',
            ],
            [
                'konuSlug' => 'kredi-gecmisi-olmadan-kredi-karti',
                'country_code' => 'FR',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Fransa\'da \'kredi geçmişi\' kavramı ABD\'deki gibi işlemiyor',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Fransa\'da bireyler için ABD\'deki FICO benzeri pozitif bir kredi skoru veya kredi geçmişi sistemi bulunmuyor. Bankalar kendi dahili risk skorlarını kullanıyor ve bu skorlar bankalar arası paylaşılmıyor (CNIL kuralı gereği). Banque de France\'ın yönettiği FICP (Fichier national des Incidents de remboursement des Crédits aux Particuliers) yalnızca kredi geri ödeme aksaklıklarını, yetkili banka hesabı açığındaki (découvert autorisé) ödenmemiş temerrütleri ve borç yapılandırma (surendettement) dosyalarını kaydeden olumsuz bir sicildir. Bu dosyada kaydınızın olmaması \'iyi bir kredi geçmişiniz var\' anlamına gelmez, çünkü Fransa\'da böyle pozitif bir kayıt sistemi zaten tutulmuyor; dolayısıyla \'kredi geçmişi yok\' diye bir kart başvurusunun reddedilmesi ABD veya İngiltere\'deki gibi işlemez.',
                    ],
                    2 => [
                        'tip' => 'baslik',
                        'metin' => '\'Kredi kartı\' Fransa\'da çoğunlukla gerçek bir kredi hattı değildir',
                    ],
                    3 => [
                        'tip' => 'paragraf',
                        'metin' => 'Fransız bankalarının standart olarak verdiği ve üzerinde \'CRÉDIT\' ibaresi taşıyan kartların büyük bölümü, aslında ay içindeki harcamaları biriktirip ay sonunda hesabınızdan tek seferde çeken \'carte à débit différé\' (ertelemeli banka kartı)dır. 2016\'dan beri yürürlükte olan Avrupa/Fransa düzenlemesi bu kartları biçimsel olarak \'carte de crédit\' (kredi kartı) kategorisinde sınıflandırır — kartın üzerinde \'CRÉDIT\' yazmasının nedeni tam olarak budur; La Banque Postale\'in kendi resmi sayfası da bu kartların hesabınıza bağlı olmasına rağmen \'carte de crédit\' sayıldığını doğrular. Ancak bu yalnızca düzenleyici bir etikettir: harcadığınız para zaten kendi hesabınızdaki paradır, faiz işlemez ve gerçek bir kredi riski/borç ilişkisi oluşturmaz (gerçek faizli ve bir kredi hattı içeren \'crédit renouvelable\'e bağlı kartlar ayrı ve farklı bir üründür). Bu yüzden başvuruda ABD/İngiltere tarzı bir kredi geçmişi sorgulanmaz; yeterli olan bir Fransız banka hesabına sahip olmaktır. Kredi/mali geçmişi olmayan veya yeni hesap açan müşterilere bankalar sıklıkla, her işlemde bakiyeyi kontrol edip borç oluşturmayan \'carte à autorisation systématique\' (sistematik onaylı kart) sunar; bu kart, aşağıda anlatılan droit au compte prosedürüyle hesap açan kişilere de doğrudan sunulan standart karttır.',
                    ],
                    4 => [
                        'tip' => 'paragraf',
                        'metin' => 'Kart alabilmeniz için önce bir Fransız banka hesabınızın olması gerekir. Bir banka hesap açmayı reddederse (veya başvurunuzdan itibaren 15 gün içinde yanıt vermezse), resmi devlet portalı service-public.gouv.fr\'de anlatılan \'droit au compte\' (hesap hakkı) prosedürüyle Banque de France\'a başvurulabilir. Banque de France, dosyanız tam olduktan sonra 1 iş günü içinde size yakın bir banka belirler; belirlenen banka da gerekli belgeleri aldıktan sonra en geç 3 iş günü içinde hesabınızı açmak ve kart, hesap özeti, para yatırma/çekme gibi temel bankacılık hizmetlerini ücretsiz sağlamak zorundadır. Bu hak Fransa\'da ikamet eden yabancı uyruklu kişiler için de geçerlidir ve kredi geçmişi, banka yasağı (FICP kaydı) ya da mali güçlük (surendettement) durumuna bakılmaksızın uygulanır.',
                    ],
                    5 => [
                        'tip' => 'madde',
                        'metin' => 'Droit au compte başvurusu için gerekenler: doldurulmuş başvuru formu, geçerli kimlik belgesi, 3 aydan eski olmayan ikametgah belgesi, bankanın yazılı ret yazısı (veya başvurudan bu yana en az 15 gün geçtiğinin kanıtı) ve başka bir mevduat hesabınız olmadığına dair beyan.',
                    ],
                ],
                'kaynak_url' => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F2417',
                'kaynak_aciklama' => 'Ana kaynak: Fransız hükümetinin resmi hizmet portalı service-public.gouv.fr — "Refus d\'ouverture de compte bancaire : droit au compte" sayfası (doğrudan fetch edilerek kimlerin yararlanabileceği, 15 gün kuralı, gerekli belgeler, Banque de France\'ın 1 iş günü/bankanın 3 iş günü süreleri ve FICP/surendettement durumundakileri de kapsadığı teyit edildi). Ek doğrulama kaynakları: Banque de France\'ın desteklediği lafinancepourtous.com\'un FICP sayfası ve "carte à autorisation systématique" sayfası (bu kartın droit au compte hesap sahiplerine doğrudan sunulduğunu da doğruluyor); CNIL\'in FICP açıklaması; lesclesdelabanque.com\'un carte débit/crédit karşılaştırması (2016 düzenlemesinin CRÉDIT etiketinin kaynağı olduğunu doğruluyor). La Banque Postale\'in resmi "differences-carte-credit-debit" sayfası da kaynak olarak kullanıldı, ancak taslaktaki orijinal atıf ("teknik anlamda kredi sayılmaz") bu sayfanın gerçekte söylediğinin (ertelemeli kartların hesaba bağlı olmalarına rağmen resmi olarak "carte de crédit" SAYILDIĞI) tersiydi; düzeltilmiş metinde bu doğru şekilde temsil edildi. Not: Fransa\'da ABD/UK tipi bir "kredi geçmişi puanlama" sistemi hiç bulunmadığından, konu ülkeye özgü mekanizmaya (droit au compte + carte à autorisation systématique) yönlendirilmiştir; kesin olmayan hiçbir rakam/şart eklenmemiştir. Kasım 2026\'da yürürlüğe girecek CCD2/découvert reformu bu konunun kapsamı dışında bırakıldı çünkü doğrudan kredi kartı edinme sürecini değiştirmiyor.',
            ],
            [
                'konuSlug' => 'kredi-gecmisi-olmadan-kredi-karti',
                'country_code' => 'BE',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Belçika\'da ABD tarzı üç haneli \'kredi skoru\' yok, merkezi bir kredi sicili var',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belçika Ulusal Bankası\'nın (Nationale Bank van België) işlettiği Centrale voor Kredieten aan Particulieren (CKP / Bireysel Kredi Sicili - ICR), Belçika\'da alınan tüm tüketici kredilerini, ipotek kredilerini ve varsa ödeme temerrütlerini (\'kara liste\') kaydeder; ABD\'dekine benzer pozitif bir puanlama sistemi yoktur. Kredi verenler -kredi kartı başvuruları dahil- karar vermeden önce bu sicili sorgulamak ve başvuranın krediyi geri ödeme kapasitesini araştırmakla yasal olarak yükümlüdür.',
                    ],
                    2 => [
                        'tip' => 'paragraf',
                        'metin' => 'Bu yapı nedeniyle yurt dışından getirdiğiniz kredi geçmişi Belçika\'ya taşınmaz; sicilde kaydınızın bulunmaması tek başına bir engel değildir, ama banka bunun yerine somut gelir ve kimlik kanıtı ister. Genelde istenenler: geçerli kimlik/pasaport, Belçika\'da adres kanıtı (kira sözleşmesi ya da fatura) ve güncel maaş bordrosu, iş sözleşmesi veya düzenli banka hesap hareketleri gibi gelir kanıtlarıdır; maaş bordrosu sunamayanlar için standart bir kredi kartı başvurusu genelde zorlaşır.',
                    ],
                    3 => [
                        'tip' => 'baslik',
                        'metin' => 'Gelir kanıtı/kredi geçmişi yoksa gerçekçi bir alternatif: ön ödemeli (prepaid) kart',
                    ],
                    4 => [
                        'tip' => 'paragraf',
                        'metin' => 'KBC bankasının resmi sitesine göre KBC Prepaid Card (Mastercard) ayda 1 euro sabit ücretle sunulur, kartta aynı anda en fazla 5.000 euro bakiye taşınabilir (tek seferde yüklenebilecek tutar ise 1.250 euro ile sınırlıdır) ve bir banka hesabına bağlı olması gerekmez. Bu gerçek bir kredi kartı değildir -borç oluşturmaz, yalnızca yüklediğiniz tutarı harcarsınız- ama maaş bordrosu ya da yerel kredi geçmişi olmayan biri için pratikte hemen hemen herkesin başvurabildiği bir seçenektir.',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belçika\'da bir yükseköğretim kurumuna kayıtlı öğrenciyseniz gerçek bir kredi kartı seçeneği de var: Beobank\'ın resmi sitesine göre Young Mastercard, 18 yaş üstü olup Belçika\'da bir üniversite/yüksekokula kayıtlı ve Belçika\'da bir vadesiz hesabı bulunan (Beobank\'ta olması şart değil) öğrencilere 750 euro kredi limiti, yıllık 5 euro aidat ve %16,49 yıllık maliyet oranıyla (TAEG) sunulur; başvuru için istenen belgeler geçerli kimlik belgesi ve güncel öğrenci kartından ibarettir, sayfada gelir ya da maaş bordrosu şartından hiç söz edilmiyor.',
                    ],
                ],
                'kaynak_url' => 'https://www.nbb.be/en/central-credit-registers/individual-credit-register-icr',
                'kaynak_aciklama' => 'Ana kaynak: Belçika Ulusal Bankası\'nın (NBB) resmi Bireysel Kredi Sicili (ICR/CKP) sayfası (https://www.nbb.be/en/central-credit-registers/individual-credit-register-icr) — doğrudan fetch ile teyit edildi. Ek olarak: FSMA\'nın wikifin.be sayfası (bankaların CKP sorgulama ve geri ödeme kapasitesi inceleme yükümlülüğü, https://www.wikifin.be/nl/budget-betalen-lenen-en-verzekeren/lening-en-krediet/hoe-sluit-je-een-lening-af-praktische-6); credit2consumer.be (kredi kartlarının/"kredietopening"in Tüketici Kredisi Kanunu ve CKP kapsamında olduğunun teyidi, https://credit2consumer.be/nl/article/kredietopening); KBC\'nin resmi Prepaid Card sayfası (ücret ve azami bakiye, https://www.kbc.be/retail/en/products/payments/payment-cards/credit-cards-and-prepaid-cards/prepaid-card.html) VE KBC\'nin ayrı kullanım/limit sayfası (tek seferlik yükleme limitinin azami bakiyeden farklı olduğunun kaynağı — 1.250 euro/işlem vs 5.000 euro azami bakiye, https://www.kbcbrussels.be/retail/en/products/payments/payment-cards/credit-cards-and-prepaid-cards/using-your-prepaid-card.html); Beobank\'ın resmi Young Mastercard sayfası (şartlar, ücretler, istenen belgeler: kimlik + öğrenci kartı, https://www.beobank.be/fr/particulier/payer/cartes-de-credit/young-mastercard); genel çerçeve ve belge listesi için businessam.be (https://businessam.be/kredietkaart-aanvragen-zonder-loonfiche/) ile Expatica\'nın Belçika kredi kartı rehberi (https://www.expatica.com/be/finance/money-management/best-credit-cards-in-belgium-2173655/).',
            ],
            [
                'konuSlug' => 'kredi-gecmisi-olmadan-kredi-karti',
                'country_code' => 'AT',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Gerçek kredi kartı: bonite kontrolü yasal bir zorunluluk',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Avusturya\'da harcama limitli (Verfügungsrahmen) \'gerçek\' bir kredi kartı başvurusunda bankalar, Tüketici Kredi Kanunu (Verbraucherkreditgesetz) gereği başvuranın ödeme gücünü (Bonität) yeterli bilgiyle değerlendirmek zorundadır; bu genelde KSV (Kreditschutzverband) gibi bir kredi bilgi kurumundan sorgu anlamına gelir (kaynak: WKO, wko.at/vertragsrecht/verbraucherkreditgesetz). Avusturya\'nın resmi tüketici bilgilendirme portalına göre bankanın başvuruyu reddetmesi ödeme gücüyle ilgili ciddi bir uyarı işareti sayılmalıdır; hiç Avusturya kredi geçmişi olmayan biri de -değerlendirme için yeterli veri bulunmadığından- bu süreçte olumsuz etkilenip reddedilebilir (kaynak: konsumentenfragen.at). Ayrıca AB\'nin yeni Tüketici Kredisi Direktifi\'ni (CCD II) uygulayan Verbraucherkreditrechts-Änderungsgesetz 2026, 20 Kasım 2026\'dan itibaren yürürlüğe girecek ve olumsuz bonite değerlendirmesi sonrasında kredi verilmesini daha da katı biçimde yasaklayacak; yani bu kontrol yakın gelecekte gevşemeyecek, tam tersine sıkılaşacaktır.',
                    ],
                    2 => [
                        'tip' => 'baslik',
                        'metin' => 'Bonite kontrolsüz gerçek alternatif: ön yüklemeli (prepaid) kart',
                    ],
                    3 => [
                        'tip' => 'paragraf',
                        'metin' => 'Banka herhangi bir kredi riski üstlenmediği için -yalnızca önceden yüklediğiniz bakiyeyi harcadığınız- prepaid (ön yüklemeli) kartlarda bonite kontrolü devreye girmez. Avusturya\'nın en büyük bağımsız kart kuruluşu card complete\'in resmi ürün sayfasına göre Visa Prepaid Card\'ın aylık ücreti 1,50 Euro\'dur, karta 20-2.500 Euro arasında bakiye yüklenebilir, her yükleme için %1,65 işlem bedeli alınır ve başvuru ID Austria (e-imza) ya da VideoID ile tamamen dijital tamamlanabilir (kaynak: cardcomplete.com/privatkarten/kartenuebersicht/prepaid-card).',
                    ],
                    4 => [
                        'tip' => 'baslik',
                        'metin' => 'Herkesin hakkı: bonite sorgusuz temel banka hesabı (Basiskonto)',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'Avrupa Birliği\'nde yasal ikameti olan herkesin -bonite sorunu ya da geçmişte özel iflas (Privatkonkurs) yaşamış olsa bile- Avusturya\'da bir Basiskonto (temel banka hesabı) açtırma hakkı vardır. Banka bu başvuruyu yalnızca iki durumda reddedebilir: kişinin zaten kullanılabilir başka bir Avusturya hesabı varsa, ya da bankaya veya çalışanlarına karşı işlenmiş kasıtlı bir suç nedeniyle hakkında dava açılmışsa (mahkûmiyet henüz kesinleşmemiş olsa bile) veya silinmemiş bir mahkûmiyeti varsa; bonite ya da kredi geçmişi -bu iki durumun dışında- tek başına bir ret nedeni değildir. Hesap en geç 10 iş günü içinde açılmalı, standart yıllık ücret en fazla 83,45 Euro olabilir; sosyal/ekonomik açıdan özellikle muhtaç kişiler başvuru üzerine yıllık en fazla 41,73 Euro\'luk indirimli tavandan yararlanabilir ve hesap bir Bankomatkarte (banka/debit kartı) içerir. Bu, harcama limitli bir kredi kartı değildir; ama bonite geçmişi sıfırken Avusturya\'da resmi bir bankacılık ilişkisi kurmanın güvenceli ilk adımıdır (kaynak: Arbeiterkammer, arbeiterkammer.at/beratung/konsument/Geld/Konto/Recht_auf_ein_Girokonto.html).',
                    ],
                    6 => [
                        'tip' => 'baslik',
                        'metin' => 'Pratik yol: önce giro hesabı ve düzenli gelir, sonra kredi kartı',
                    ],
                    7 => [
                        'tip' => 'paragraf',
                        'metin' => 'Avusturya\'ya yeni yerleşenler için resmi yerleşim/yatırım kurumu Austrian Business Agency\'nin rehberine göre banka hesabı açarken geçerli fotoğraflı kimlik (pasaport), Meldezettel (adres kayıt belgesi) ve çalışma ya da öğrencilik durumunu gösteren bir belge istenir; aynı kurumun rehber sayfasına göre büyük krediler genelde ancak belirli bir eşiğin üzerinde ve sürekli bir iş sözleşmesine bağlı istikrarlı bir gelir kanıtlandığında onaylanır (kaynak: workinaustria.com, Austrian Business Agency). Pratikte önce bir bankada giro hesabı açıp maaşınızı düzenli olarak o hesaba yönlendirmek, birkaç ay sonra aynı bankadan kredi kartı başvurusunda bulunmayı -Avusturya\'da hiç kredi geçmişi olmasa da- kolaylaştıran yaygın bir yoldur.',
                    ],
                    8 => [
                        'tip' => 'baslik',
                        'metin' => 'Başvuru için genelde istenen belgeler',
                    ],
                    9 => [
                        'tip' => 'madde',
                        'metin' => 'Geçerli fotoğraflı kimlik (pasaport)',
                    ],
                    10 => [
                        'tip' => 'madde',
                        'metin' => 'Meldezettel (Avusturya\'da ikamet adresini gösteren resmi kayıt belgesi)',
                    ],
                    11 => [
                        'tip' => 'madde',
                        'metin' => 'Çalışma sözleşmesi, maaş bordrosu veya öğrencilik belgesi gibi gelir/durum kanıtı',
                    ],
                    12 => [
                        'tip' => 'madde',
                        'metin' => 'Basiskonto başvurusu için ek şart: Avusturya\'da hâlihazırda kullanılabilir başka bir hesabınızın olmaması (ayrıca bankaya/çalışanlarına karşı kasıtlı suçtan dava veya silinmemiş mahkûmiyet de ayrı bir ret nedenidir)',
                    ],
                ],
                'kaynak_url' => 'https://www.arbeiterkammer.at/beratung/konsument/Geld/Konto/Recht_auf_ein_Girokonto.html',
                'kaynak_aciklama' => 'Arbeiterkammer\'in resmi "Bankkonto für Jedermann" sayfası (arbeiterkammer.at/beratung/konsument/Geld/Konto/Recht_auf_ein_Girokonto.html) Basiskonto bölümündeki düzeltmenin dayanağı: sayfa, ret gerekçesini tam olarak "Gegen Sie liegt wegen einer vorsätzlichen Straftat zum Nachteil der Bank oder ihrer Mitarbeiter eine Anklage oder eine nicht getilgte Verurteilung vor" şeklinde tanımlıyor (aynı metin tirol.arbeiterkammer.at yansımasında da doğrulandı) — yani suç bankaya/çalışanlarına karşı olmalı VE kesinleşmemiş bir dava (Anklage) bile tek başına yeterli, mahkûmiyetin kesinleşmesi şart değil. Ayrıca 83,45/41,73 Euro ücret tavanları, 10 iş günü süresi ve Bankomatkarte dahil olması da aynı sayfadan birebir doğrulandı. Diğer bloklardaki kaynaklar da bağımsızca teyit edildi: cardcomplete.com/privatkarten/kartenuebersicht/prepaid-card (prepaid kart rakamları, tam eşleşme), wko.at/vertragsrecht/verbraucherkreditgesetz (Bonitätsprüfung\'un VKrG gereği zorunlu olduğu genel ilke), konsumentenfragen.at (ret=uyarı işareti alıntısı, tam eşleşme) ve workinaustria.com (Austrian Business Agency\'nin resmi sitesi olduğu footer\'dan doğrulandı; hem belge listesi hem "büyük krediler istikrarlı gelir ister" ifadesi site içinde ayrı sayfalarda birebir bulundu). Ek olarak parlament.gv.at ve chg.at üzerinden doğrulanan Verbraucherkreditrechts-Änderungsgesetz 2026 (CCD II uygulaması, 20 Kasım 2026 yürürlük) taslağa güncel bağlam olarak eklendi.',
            ],
            [
                'konuSlug' => 'turkiyeye-para-transferi',
                'country_code' => 'DE',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'SEPA neden işe yaramıyor: Türkiye SEPA alanı dışında',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Almanya\'daki bankalar arasında geçerli olan ücretsiz ya da çok düşük maliyetli SEPA Euro havalesi yalnızca SEPA üyesi ülkeler arasında çalışır. Verbraucherzentrale\'nin açıkladığı gibi, euro bölgesi dışına yapılan sınır ötesi ödemelerde SEPA havalesi kullanılamaz ve bunun yerine daha pahalı olan klasik yurt dışı havalesi (Auslandsüberweisung / SWIFT) tercih edilmek zorundadır. Türkiye, SEPA şema listesinde yer almadığı için bu kapsamın dışındadır — yani Almanya\'dan Türkiye\'ye gönderilen bir euro transferi SEPA değil, Auslandsüberweisung/SWIFT olarak işlem görür. (Kaynak: verbraucherzentrale.de; Türkiye\'nin SEPA-dışı statüsü SEPA şema/ülke listeleriyle ayrıca doğrulanmıştır)',
                    ],
                    2 => [
                        'tip' => 'paragraf',
                        'metin' => 'Güncel gelişme: Şubat 2026\'da AB Komisyonu\'nun genişlemeden sorumlu üyesi Marta Kos\'un Ankara ziyaretinde AB, Türkiye\'ye SEPA\'ya katılım teklifi götürdü; kabul edilirse Almanya-Türkiye euro transferleri SEPA hız ve maliyetine kavuşabilir. Ancak bu şu ana kadar yalnızca bir tekliftir — Türkiye tarafından resmi bir kabul açıklanmamıştır ve yürürlüğe girmemiştir. Bu yazının kapsadığı tarih itibarıyla (Ağustos 2026) yukarıdaki SEPA-dışı durum hâlâ geçerlidir, ancak okuyucular bu gelişmeyi takip etmelidir.',
                    ],
                    3 => [
                        'tip' => 'baslik',
                        'metin' => 'Asıl gizli maliyet çoğu zaman döviz kuru farkında',
                    ],
                    4 => [
                        'tip' => 'paragraf',
                        'metin' => 'Almanya\'nın finansal denetim kurumu BaFin\'in bu konudaki tüketici uyarısı, aslında yurt dışında KARTLA ödeme yapma veya ATM\'den nakit çekme senaryosuna yöneliktir (banka havalesi/para transferi hizmetleri için değil): euro bölgesi dışındaki ülkelerde ATM işletmecileri veya işyerleri genellikle \'sabit\' veya \'garantili\' bir kurla anlık euro çevirme (Sofortumrechnung) seçeneği sunar; BaFin bunun genellikle sağlayıcı lehine belirgin bir kur farkı içerdiğini, bu farkın işlem anında görünmeyip daha sonra hesap ekstresinde ortaya çıktığını belirtir ve bu anlık çevirmeyi reddedip işlemin kendi bankanızın güncel kuruyla yapılmasını önerir. BaFin aynı sayfada, Avrupa Ekonomik Alanı dışında KART kullanımında sağlayıcının bilgilendirme yükümlülüklerinin de sınırlı olduğunu belirtiyor — ama bu kısıtlama da kart kullanımına özgüdür. Bu uyarının kendisi para transferi/havale hizmetleri için yazılmamış olsa da, altında yatan ilke transfer sağlayıcısı seçerken de geçerlidir: asıl maliyet çoğu zaman ilan edilen işlem ücretinde değil, uygulanan döviz kurunun piyasa (mid-market) kuruna ne kadar uzak olduğundadır. (Kaynak: bafin.de, \'Bezahlen und Geld abheben im Ausland\' sayfası)',
                    ],
                    5 => [
                        'tip' => 'baslik',
                        'metin' => 'Uzman transfer sağlayıcısı örneği: Wise',
                    ],
                    6 => [
                        'tip' => 'paragraf',
                        'metin' => 'Wise\'ın kendi resmi yardım merkezi sayfasında verdiği örneğe göre, Almanya\'daki bir Wise hesabından Türkiye\'de euro cinsinden bir hesaba 1.000 Euro gönderildiğinde (alıcı bankasından ek bir ücret beklenmiyorsa) toplam maliyet 5,21 Euro olarak hesaplanıyor. İşlem gerçek piyasa (mid-market) döviz kuru üzerinden yapılıyor; Wise\'ın kendi açıklamasına göre SEPA alanı dışındaki hesaplara euro gönderiminde SWIFT ağı kullanılıyor, bu yüzden kesin varış süresi işlem anına ve aradaki bankalara göre değişebiliyor. Ücretler zamanla değişebileceğinden gönderim öncesi uygulamada gösterilen güncel tutarı kontrol etmek gerekir. (Kaynak: wise.com/help, \'Sending EUR to countries outside of Europe\')',
                    ],
                    7 => [
                        'tip' => 'baslik',
                        'metin' => 'Türkiye\'ye özelleşmiş bir banka: Ziraat Bank Almanya',
                    ],
                    8 => [
                        'tip' => 'paragraf',
                        'metin' => 'Almanya\'da BaFin denetimi altında faaliyet gösteren Ziraat Bank International AG (T.C. Ziraat Bankası\'nın Almanya\'daki bankası), Kombi-Konto sahiplerine internet bankacılığı üzerinden Türkiye\'deki Ziraat Bankası şubelerine düşük ücretli transfer imkânı sunuyor; bankanın kendi sitesine göre gönderilen tutar birkaç dakika içinde alıcıya ulaşıyor. Banka, Almanya\'nın yasal mevduat güvence sistemine (Entschädigungseinrichtung deutscher Banken) üyedir. Güncel ücret tarifesi (yalnızca internet bankacılığı üzerinden yapılan online transferler için) şöyle: (Kaynak: ziraatbank.de/de/turkeiuberweisungen)',
                    ],
                    9 => [
                        'tip' => 'madde',
                        'metin' => '500 Euro\'ya kadar: 5 Euro',
                    ],
                    10 => [
                        'tip' => 'madde',
                        'metin' => '500,01–1.000 Euro arası: 5 Euro',
                    ],
                    11 => [
                        'tip' => 'madde',
                        'metin' => '1.000,01–2.000 Euro arası: 6 Euro',
                    ],
                    12 => [
                        'tip' => 'madde',
                        'metin' => '2.000,01–6.000 Euro arası: 7 Euro',
                    ],
                    13 => [
                        'tip' => 'madde',
                        'metin' => '6.000,01–10.000 Euro arası: 9 Euro',
                    ],
                    14 => [
                        'tip' => 'madde',
                        'metin' => '10.000 Euro üzerinde yüzdesel bir ücret uygulanıyor; güncel oranı transfer öncesi bankanın ücret tarifesi sayfasından teyit edin (ikincil kaynaklarda %0,15 ile %0,40 arası farklı rakamlar görüldü, bu yüzden tek bir oran burada verilmiyor — mutlaka anlık tarifeyi kontrol edin)',
                    ],
                    15 => [
                        'tip' => 'madde',
                        'metin' => 'Ziraat Bank müşterisi olmayanlar da banka nezdinde hesap açmadan, bankanın geliştirdiği ayrı bir mobil uygulama (Non-Kunden App) üzerinden benzer düşük ücretlerle (kimlik doğrulaması Web-ID yöntemiyle) transfer yapabiliyor',
                    ],
                    16 => [
                        'tip' => 'madde',
                        'metin' => 'Alıcı hesabının Euro (EUR) cinsinden olması gerekiyor; bankanın kendi uyarısına göre farklı para biriminde (örn. Türk Lirası) bir hesaba gönderim otomatik alacaklandırmayı engelleyebiliyor ve gecikme/ek maliyete yol açabiliyor',
                    ],
                    17 => [
                        'tip' => 'madde',
                        'metin' => 'Alıcı bilgileri (ad-soyad, hesap numarası, mümkünse T.C. kimlik numarası) kimlik belgesiyle birebir uyuşmalı; bankanın kendi uyarısına göre alıcı bilgileri hesap adı ve numarasıyla %100 örtüşmezse işlem ücretli olarak iade ediliyor',
                    ],
                    18 => [
                        'tip' => 'baslik',
                        'metin' => 'Göndermeden önce kontrol listesi',
                    ],
                    19 => [
                        'tip' => 'madde',
                        'metin' => 'Yalnızca ilan edilen işlem ücretine değil, uygulanan döviz kurunun piyasa kuruna ne kadar yakın olduğuna da bakın; asıl maliyet çoğu zaman kur farkında gizlidir',
                    ],
                    20 => [
                        'tip' => 'madde',
                        'metin' => 'Göndermeden önce alıcının hesabının Euro mu yoksa Türk Lirası mı olduğunu netleştirin',
                    ],
                    21 => [
                        'tip' => 'madde',
                        'metin' => 'Kartla ödeme veya ATM\'den nakit çekerken karşınıza çıkan \'sabit/garantili kur\' vaat eden anlık döviz çevirme (Sofortumrechnung) seçeneklerini kabul etmeyin, kendi bankanızın güncel kurunu uygulamasını isteyin (BaFin uyarısı)',
                    ],
                    22 => [
                        'tip' => 'madde',
                        'metin' => 'Verbraucherzentrale\'ye göre euro alanı dışına yapılan klasik banka havalesi (Auslandsüberweisung) SEPA\'ya kıyasla daha pahalıdır; Ziraat Bank gibi Türkiye\'ye özelleşmiş bir banka veya Wise gibi lisanslı bir transfer sağlayıcısı üzerinden gönderim genelde daha uygun çıkıyor — ama her iki durumda da güncel ücret ve kuru gönderim anında teyit edin',
                    ],
                ],
                'kaynak_url' => 'https://www.verbraucherzentrale.de/wissen/geld-versicherungen/sparen-und-anlegen/sepa-europaweite-regeln-im-zahlungsverkehr-11512',
                'kaynak_aciklama' => 'Almanya tüketici merkezleri birliği Verbraucherzentrale\'nin SEPA sayfası, euro bölgesi dışına yapılan sınır ötesi ödemelerde SEPA\'nın kullanılamayacağını ve daha pahalı "Auslandsüberweisung"ın gerektiğini doğrudan doğruluyor (sayfa doğrudan fetch edilip alıntı teyit edildi); Türkiye\'nin SEPA-dışı statüsü ayrıca bağımsız SEPA ülke/şema listeleriyle doğrulandı. Bu temel bilgi; Wise\'ın resmi yardım merkezindeki Almanya→Türkiye örnek ücret hesaplaması (wise.com/help/articles/2968916 — 1.000 EUR için 5,21 EUR, mid-market kur, SWIFT ağı, hepsi doğrudan sayfadan teyit edildi) ve BaFin denetimindeki Ziraat Bank International AG\'nin resmi Türkiye transfer ücret tarifesi sayfası (ziraatbank.de/de/turkeiuberweisungen — tüm ücret kademeleri, EUR hesap şartı, isim/hesap uyuşmazlığı kuralı, non-müşteri uygulaması, "birkaç dakika" varış süresi birebir doğrulandı) ile çapraz doğrulandı. Ziraat Bank\'ın BaFin denetimi (BaFin\'in 2024 tarihli resmi tedbir duyurusuyla teyit) ve Alman mevduat güvence sistemine üyeliği (einlagensicherung.de ve einlagensicherungsfonds.de üzerinden teyit) ayrıca doğrulandı.

DÜZELTME: BaFin\'in "garantili kur" ve "AEA-dışı bilgilendirme kısıtlaması" uyarısı, bafin.de\'nin "Bezahlen und Geld abheben im Ausland" sayfasından geliyor ve bu sayfa YALNIZCA kartla ödeme/ATM\'den nakit çekme senaryosunu kapsıyor, banka havalesi/para transferi hizmetlerini değil — taslaktaki orijinal atıf bunu banka transferi bağlamında sunarak yanıltıcıydı, düzeltilmiş metinde bu ayrım netleştirildi.

EK BULGU: Şubat 2026\'da AB, Türkiye\'ye resmen SEPA\'ya katılım teklifi götürdü (Reuters/PaymentsJournal/Hürriyet Daily News haberleriyle teyit edildi); henüz kabul edilmedi/yürürlükte değil ama okuyucuya bildirilmesi gereken önemli bir gelişme olarak eklendi.',
            ],
            [
                'konuSlug' => 'turkiyeye-para-transferi',
                'country_code' => 'NL',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'SEPA Dışı Transfer: Türkiye\'ye Havale Neden \'Ücretsiz Avrupa Ödemesi\' Kapsamında Değil',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Hollanda\'daki bankalar, SEPA alanı içindeki euro cinsinden havaleleri kanunen ücretsiz sunmak zorundadır. Türkiye SEPA alanının dışında kaldığı için bu kural geçerli değildir: bankalar Türkiye\'ye yapılan transferlerde hem sabit bir işlem ücreti hem de döviz kurunun üzerine kendi marjlarını ekleyebilir. Ekranda görünen sabit ücret, bu yüzden gerçek toplam maliyetin yalnızca bir kısmıdır.',
                    ],
                    2 => [
                        'tip' => 'madde',
                        'metin' => 'ABN AMRO: tüm masrafı siz karşılarsanız (\'OUR\' seçeneği) 9 EUR sabit ücret + varış ülkesine göre değişen ek tarife',
                    ],
                    3 => [
                        'tip' => 'madde',
                        'metin' => 'ING: tüm masrafı siz karşılarsanız (\'OUR\' seçeneği) 6 EUR sabit ücret + varış ülkesine göre değişen ek tarife',
                    ],
                    4 => [
                        'tip' => 'madde',
                        'metin' => 'Rabobank: tüm masrafı siz karşılarsanız (\'OUR\' seçeneği) 8 EUR + transfer tutarının %0,1\'i (en az 8 EUR, en çok 35 EUR)',
                    ],
                    5 => [
                        'tip' => 'baslik',
                        'metin' => 'Dijital Sağlayıcılar (Wise gibi) Genelde Daha Ucuz Çıkıyor',
                    ],
                    6 => [
                        'tip' => 'paragraf',
                        'metin' => 'Wise gibi lisanslı dijital sağlayıcılar, bankaların aksine gerçek piyasa (mid-market) döviz kurunu kullanır ve ücreti gönderim öncesinde net şekilde gösterir. Wise\'ın resmi hesaplayıcısında 1.000 EUR\'luk bir Hollanda→Türkiye transferinde en ucuz seçenekler Wise bakiyesinden gönderim (yaklaşık 6,31 EUR) ve banka hesabından besleme (yaklaşık 8,72 EUR). Kart ile ödeme bunlardan belirgin şekilde pahalıdır: kredi kartı, Google Pay ve Apple Pay yaklaşık 20,73 EUR\'a, banka kartı (debit) ise yaklaşık 43,18 EUR\'a mal oluyor — yani debit kart, genelde \'daha ucuz\' sanılmasına rağmen bu güzergâhta kredi karttan bile pahalıya gelebiliyor. Wise\'ın kurumsal/yatırımcı raporlarına göre transferlerinin küresel ortalamada yaklaşık %74\'ü 20 saniyeden, %95\'i bir günden kısa sürede alıcıya ulaşıyor; bu, Hollanda-Türkiye güzergâhına özgü bir rakam değil, şirketin dünya genelindeki tüm transferlerine ait genel bir istatistiktir. Tüm bu rakamlar örnektir; gönderilen tutara, ödeme yöntemine ve günün kuruna göre değişir.',
                    ],
                    7 => [
                        'tip' => 'paragraf',
                        'metin' => 'Hangi sağlayıcıyı seçerseniz seçin, şirketin Hollanda\'da faaliyet gösterebilmesi için De Nederlandsche Bank (DNB) tarafından ruhsatlandırılmış olması ya da başka bir AB ülkesinden \'pasaport\' ile bildirilmiş olması gerekir. Kullanmayı düşündüğünüz şirketin adını DNB\'nin herkese açık kayıt sayfasından ücretsiz arayarak ruhsatını doğrulayabilirsiniz.',
                    ],
                ],
                'kaynak_url' => 'https://www.consumentenbond.nl/betaalrekening/geld-overmaken-naar-buitenland',
                'kaynak_aciklama' => 'Ana kaynak: Consumentenbond (Hollanda Tüketici Birliği) - "Geld overmaken naar het buitenland" rehberi, sayfada "Bijgewerkt op: 27 januari 2026" tarih damgası var; 22 Ağustos 2026\'da doğrudan tarayıcıyla ziyaret edilerek teyit edildi. ABN AMRO (9 EUR + ülkeye göre tarife), ING (6 EUR + ülkeye göre tarife) ve Rabobank (8 EUR + tutarın %0,1\'i, min 8/maks 35 EUR) OUR-seçeneği ücretleri sayfanın "Kosten overboeking" bölümünden harfiyen doğrulandı; SEPA-dışı ülke kuralı "Europabetaling gratis" ve "Vreemde valuta overmaken" bölümleriyle teyit edildi.

Wise ücretleri wise.com/nl/send-money/send-money-to-turkey sayfasının canlı hesaplayıcısı doğrudan tarayıcıyla açılıp okunarak 22 Ağustos 2026\'da doğrulandı: Wise bakiyesi 6,31 EUR ve banka hesabı 8,72 EUR taslakla birebir örtüşüyor. ANCAK kart ücretleri taslaktaki "14-17 EUR" iddiasıyla uyuşmuyor — sayfada net biçimde kredi kartı/Google Pay/Apple Pay 20,73 EUR, banka kartı (debit) 43,18 EUR olarak listeleniyor; bu yüzden kart ücreti düzeltildi (iki bağımsız WebFetch + doğrudan tarayıcı okumasıyla üçlü çapraz kontrol edildi; yalnız URL\'ye sorgu parametresi eklenen bir WebFetch denemesi hatalı/taslağa yakın sayılar üretti, bu yüzden tarayıcı ground-truth\'u esas alındı).

"%74/%95 hız" istatistiği bu hesaplayıcı sayfasında görünmüyor; bu rakamlar Wise plc\'nin 2026 ara dönem (H1 FY26) yatırımcı/kurumsal raporlarına ait küresel bir istatistik olup üçüncü taraf kaynaklarla (ör. Wise\'ın kamuya açık finansal sonuç özetleri) çapraz kontrol edildi — sayı doğru ama Hollanda-Türkiye güzergâhına özgü değil, bu nüans metne eklendi.

De Nederlandsche Bank\'ın (DNB) kamuya açık, ücretsiz ve isimle aranabilir ödeme kuruluşları sicili dnb.nl/openbaar-register/register-van-betaalinstellingen üzerinden doğrulandı; Wise Europe SA\'nın Belçika Ulusal Bankası (NBB) ruhsatıyla AB pasaportu üzerinden Hollanda\'ya bildirildiği de ayrıca teyit edildi.',
            ],
            [
                'konuSlug' => 'turkiyeye-para-transferi',
                'country_code' => 'FR',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Türkiye SEPA dışında: SWIFT ile yaklaşık 4 iş günü',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Türkiye, Avrupa\'nın SEPA (Tek Euro Ödemeler Alanı) sistemine dahil değildir; bu yüzden Fransa\'daki hesabınızdan Türkiye\'deki bir TR IBAN\'a gönderdiğiniz para, ucuz ve hızlı SEPA havalesi değil, SWIFT ağı üzerinden yürüyen \'uluslararası havale\' (virement international) sayılır. Wise\'ın konuya ayrılmış rehberine göre böyle bir havalenin yürütülmesi için genel kural 4 iş günüdür; gerçek süre bankaların konumuna, aradaki muhabir banka sayısına ve talebin hafta sonuna denk gelip gelmediğine göre değişir — hafta sonu yapılan talepler ancak bir sonraki iş günü (Pazartesi) bankaların açılışıyla işleme alınır. Güncel not: Türkiye, 2 Temmuz 2026\'da İstanbul\'da yapılan AB-Türkiye Üst Düzey Ekonomik Diyaloğu\'nda Avrupa Ödemeler Konseyi\'ne SEPA\'ya katılım niyet mektubu sundu (AB\'nin resmi ortak bildirisiyle doğrulandı). Bu henüz sadece bir niyet beyanı; kara para aklamayla mücadele ve veri koruma alanında AB uyum şartları tamamlanmadan fiilî üyelik gerçekleşmeyecek. Bu yazının hazırlandığı Ağustos 2026 itibarıyla Türkiye SEPA üyesi değildir ve aşağıdaki SWIFT süreci geçerliliğini korumaktadır — ama önümüzdeki dönemde bu tablonun değişebileceğini bilin.',
                    ],
                    2 => [
                        'tip' => 'paragraf',
                        'metin' => 'Banka üzerinden SWIFT ile gönderirseniz, gönderim ücretine ek olarak döviz kuru farkı da ödersiniz. MoneyVox\'un Fransız banka ücretleri karşılaştırmasına göre (tablo 12 Mart 2026 itibarıyla güncel), örneğin Société Générale online gönderimde 500 €\'ya kadar 9 €, üzerinde 13 €; Crédit Agricole 15.000 €\'ya kadar sabit 15 €, üzerinde tutarın %0,1\'i; La Banque Postale ise tutarın %0,10\'u (en az 9,90 €, en çok 70 €) gönderim ücreti uyguluyor. Düzeltme: Aynı MoneyVox tablosunda görünen 13,50-21,84 € aralığındaki \'alma ücreti\' (frais de réception), bu Fransız bankaların yurt dışından PARİS\'teki bir hesaba gelen parada KENDİ müşterisinden kestiği ücrettir — Türkiye\'deki alıcı bankanın kestiği bir ücretle ilgisi yoktur ve bu şekilde Türkiye tarafına uygulanamaz. Türk bankalarının gelen SWIFT/döviz havalesinde uyguladığı komisyon bankadan bankaya, hatta işlemden işleme büyük fark gösterebiliyor; tüketici şikâyet platformlarında toplam tutarın %40\'ından fazlasının kesildiği örnekler bile var. Bu yüzden Türkiye\'deki alıcının kendi bankasından \'gelen döviz havalesi\' ücretini işlem öncesinde teyit ettirmesi, tek bir sabit rakam vermekten çok daha güvenilir bir yaklaşımdır.',
                    ],
                    3 => [
                        'tip' => 'baslik',
                        'metin' => 'En ucuz seçenek genelde lisanslı fintech: Wise örneği',
                    ],
                    4 => [
                        'tip' => 'paragraf',
                        'metin' => 'Wise gibi düzenlenmiş ödeme kuruluşları gerçek piyasa döviz kurunu uyguladığını ve ücreti ayrı, şeffaf şekilde gösterdiğini belirtir; bu da genelde klasik banka SWIFT havalesinden daha ucuza gelir. Wise\'ın resmi Fransa→Türkiye hesaplayıcısına göre (Ağustos 2026 itibarıyla, 1.000 € örneği — fiyatlar günlük değiştiği için göndermeden önce güncel hesaplayıcıdan teyit edin), ücret ödeme yöntemine göre değişir: en ucuzu 6,31 €\'yla Wise bakiyesinden ödeme; hemen ardından 6,89 €\'yla banka hesabı bağlantısı (açık bankacılık/PISP) geliyor — bu seçenek klasik banka havalesinden (8,72 €) bile ucuz ama az bilindiği için genelde atlanıyor; banka/debit kartıyla ödemede 14,74 €, kredi kartıyla ödemede 17,45 €; Apple Pay ve Google Pay ise 20,73 €\'yla en pahalı seçenekler. Ayrıca Wise\'ın transferlerinin çoğu SWIFT\'i hiç kullanmıyor — kendi yerel banka ağı sayesinde işlemlerin %74\'ü 20 saniyeden, %95\'i ise bir günden kısa sürede tamamlanıyor; hem ucuzluğun hem de yukarıdaki 4 iş günü kuralından çok daha hızlı olmasının açıklaması bu.',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'Hangi yöntemi seçerseniz seçin, SWIFT havalelerinde ücretin kimin üzerinde kalacağını belirleyen OUR/BEN/SHA kodlarına dikkat edin: varsayılan SHA\'da gönderen kendi bankasının, alıcı da kendi bankasının ücretini öder; OUR\'da tüm ücreti gönderen üstlenir ve alıcı tam tutarı alır; BEN\'de ise tüm ücret alıcının payından kesilir. Wise kendi transferlerinde SHA modelini kullandığını ama alıcı tarafında çıkabilecek olası ücretleri baştan üstlenerek OUR\'a yakın bir güvence sunduğunu belirtiyor. Türkiye\'deki yakınınıza net ne kadar ulaşacağını netleştirmek için bankanızdan veya servisinizden hangi kodun uygulandığını önceden sorun.',
                    ],
                ],
                'kaynak_url' => 'https://wise.com/fr/send-money/send-money-to-turkey',
                'kaynak_aciklama' => 'İçerik, bu oturumda doğrudan çekilip doğrulanan şu kaynaklardan derlendi: Wise\'ın resmi Fransa→Türkiye para gönderme sayfası (wise.com/fr/send-money/send-money-to-turkey — 1.000 € örneği için ödeme yöntemine göre ücretler, canlı olarak yeniden doğrulandı); Wise\'ın uluslararası havale süresi rehberi (wise.com/fr/blog/duree-virement-international — 4 iş günü kuralı ve hafta sonu işleme kuralı doğrudan doğrulandı); Wise\'ın masraf paylaşımı rehberi (wise.com/fr/blog/frais-partages-virement-international — OUR/BEN/SHA tanımları ve Wise\'ın SHA-ama-OUR-güvenceli uygulaması doğrudan doğrulandı); MoneyVox\'un Fransız bankaları uluslararası havale ücret karşılaştırması (moneyvox.fr/tarif-bancaire/virement-international.php, tablo 12.03.2026 tarihli — Société Générale/Crédit Agricole/La Banque Postale gönderim ücretleri doğrulandı; ama aynı tablonun "alma ücreti" sütunu yanlışlıkla "Türkiye\'deki alıcı banka ücreti" olarak taslağa girmiş, bu düzeltildi); AB\'nin resmi EEAS ortak bildirisi (eeas.europa.eu, 2 Temmuz 2026 — Türkiye\'nin SEPA\'ya katılım niyet mektubu sunduğu doğrudan doğrulandı) ve destekleyici haberler (electronicpaymentsinternational.com, thepaypers.com — AB\'nin Şubat 2026\'daki SEPA teklifi ve gerekli uyum şartları). Türk bankalarının gelen SWIFT ücretlerindeki değişkenlik, şikayetvar.com üzerindeki Garanti BBVA/Akbank şikayet kayıtlarıyla desteklendi (tek bir sabit rakam yerine "bankanıza sorun" tavsiyesinin gerekçesi). Banque de France ve ACPR\'nin resmi sayfalarına bu oturumda erişilmedi; ücretler döviz kuruna bağlı günlük değiştiğinden gönderim öncesi ilgili sitelerin canlı hesaplayıcısından teyit edilmesi önerilir.',
            ],
            [
                'konuSlug' => 'turkiyeye-para-transferi',
                'country_code' => 'BE',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Türkiye SEPA bölgesinde değil',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belçika AB/SEPA üyesi olsa da Türkiye SEPA (Tek Euro Ödemeler Alanı) üyesi değildir; IBAN kullanır ama SEPA şemasına dahil olmadığı için Belçika\'dan Türkiye\'ye yapılan transferler bankalarda \'Avrupa havalesi\' değil, SWIFT üzerinden yürüyen \'uluslararası havale\' olarak işlenir. Bu da Belçika bankalarının SEPA/Avrupa içi havalelere uyguladığı ücretsiz veya çok düşük maliyetli tarifenin Türkiye\'ye uygulanmadığı, bunun yerine döviz kuru farkı, komisyon ve muhabir banka ücretleri içeren daha pahalı bir tarifenin geçerli olduğu anlamına gelir.',
                    ],
                    2 => [
                        'tip' => 'paragraf',
                        'metin' => 'Not: Şubat 2026\'da AB, Türkiye\'yi SEPA\'ya katılmaya davet eden resmi bir öneri sundu; amaç sınır ötesi euro transferlerinin maliyetini düşürmek (AB, benzer katılımlarla -Arnavutluk, Moldova, Karadağ, Kuzey Makedonya- kullanıcılar için toplam 500 milyon euroya varan tasarruf öngörüyor). Ancak bu henüz bir davetten ibaret; Ağustos 2026 itibarıyla Türkiye SEPA\'ya katılmadı ve bu yazıdaki maliyet farkı geçerliliğini koruyor. İlerleyen dönemde katılım gerçekleşirse maliyet yapısı önemli ölçüde değişebilir, bu yüzden gelişmeleri takip etmekte fayda var.',
                    ],
                    3 => [
                        'tip' => 'baslik',
                        'metin' => 'Resmi kurumun (FSMA/Wikifin) önerdiği yöntemler',
                    ],
                    4 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belçika Finansal Hizmetler ve Piyasalar Otoritesi\'nin (FSMA) resmi tüketici bilgilendirme sitesi Wikifin.be, SEPA bölgesi dışına (Türkiye dahil) yapılan transferlerde döviz kuru farkı, komisyon ve muhabir banka masraflarının ülkeye, tutara ve para birimine göre değiştiğini belirtiyor ve üç ana yöntem sıralıyor (wikifin.be/nl/.../hoe-kan-je-geld-overschrijven-naar-het):',
                    ],
                    5 => [
                        'tip' => 'madde',
                        'metin' => 'IBAN ve BIC/SWIFT kodu ile doğrudan uluslararası banka havalesi',
                    ],
                    6 => [
                        'tip' => 'madde',
                        'metin' => 'Dijital cüzdan hizmetleri (örn. PayPal)',
                    ],
                    7 => [
                        'tip' => 'madde',
                        'metin' => 'Uluslararası para transfer büroları aracılığıyla doğrudan gönderim (örn. Western Union tipi hizmetler)',
                    ],
                    8 => [
                        'tip' => 'baslik',
                        'metin' => 'Hangi firmayı seçerseniz seçin: yetkili olmalı',
                    ],
                    9 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belçika Federal Ekonomi Kamu Hizmeti\'ne (FOD Economie / SPF Economie, economie.fgov.be/nl/themas/financiele-diensten/betalingsdiensten) göre, banka hesabı gerektirmeyen para transfer hizmetleri (\'money remittance\', örn. Western Union benzeri hizmetler) yasal olarak \'ödeme hizmeti\' sayılır ve bunu sunan şirketin Belçika Ulusal Bankası (NBB) tarafından yetkilendirilmiş bir ödeme hizmeti sağlayıcısı (\'betalingsdienstaanbieder\') olması gerekir. Bir sorun yaşanırsa FOD Economie\'nin merkezi şikâyet/bildirim platformu ConsumerConnect (consumerconnect.be) kullanılabilir; finansal hizmetlere özgü uyuşmazlıklarda ayrıca Ombudsman (Ombudsfin) veya Belmed gibi arabuluculuk mekanizmaları da devreye girebilir.',
                    ],
                    10 => [
                        'tip' => 'baslik',
                        'metin' => 'Somut örnek: ödeme yöntemi maliyeti nasıl değiştiriyor (Wise, 1.000 EUR)',
                    ],
                    11 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belçika\'da lisanslı bir ödeme kuruluşu olarak faaliyet gösteren Wise\'ın resmi Belçika-Türkiye fiyatlandırma sayfasında (wise.com/be/send-money/send-money-to-turkey) 22 Ağustos 2026\'da görüntülenen örnek fiyatlara göre, aynı 1.000 EUR\'luk transferde seçilen ödeme yöntemi maliyeti büyük ölçüde değişiyor. Not: bu rakamlar döviz kuruna (özellikle TRY\'nin oynaklığına) bağlı olarak günden güne değişebilir; gönderim öncesi güncel rakamı sitede teyit etmek gerekir:',
                    ],
                    12 => [
                        'tip' => 'madde',
                        'metin' => 'Wise bakiyesi, banka hesabı bağlantısı (PISP) veya klasik banka havalesi ile ödeme: yaklaşık 6-9 EUR ücret — en ucuz seçenekler, transfer birkaç saniye ile birkaç dakika içinde tamamlanıyor',
                    ],
                    13 => [
                        'tip' => 'madde',
                        'metin' => 'Banka/debit kartıyla doğrudan ödeme: yaklaşık 10-15 EUR ücret — hesap bağlantılı ödemeden biraz daha pahalı, ama kredi kartından ve cüzdan ödemelerinden daha ucuz',
                    ],
                    14 => [
                        'tip' => 'madde',
                        'metin' => 'Kredi kartı ile ödeme: yaklaşık 17 EUR ücret',
                    ],
                    15 => [
                        'tip' => 'madde',
                        'metin' => 'Apple Pay veya Google Pay ile ödeme: yaklaşık 21 EUR ücret — bu dört seçenek arasında en pahalısı',
                    ],
                    16 => [
                        'tip' => 'paragraf',
                        'metin' => 'Aynı sayfaya göre bu tür transferlerin %74\'ü 20 saniyeden, %95\'i bir günden kısa sürede alıcıya ulaşıyor ve Wise ara döviz kuru marjı eklemeden piyasa ortalama kurunu kullandığını belirtiyor.',
                    ],
                    17 => [
                        'tip' => 'baslik',
                        'metin' => 'Kendi bankanızdan (SWIFT) göndermek isterseniz',
                    ],
                    18 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belçika bankalarının (örn. KBC, kbc.be/particulieren/nl/betalen/kosten-internationale-overschrijvingen.html) resmi sitelerinde \'uluslararası havale\', SEPA bölgesi dışına veya euro dışı bir para biriminde yapılan her havale olarak tanımlanıyor. Bu tür işlemlerde banka tutara bağlı bir ücret/provizyon uyguluyor ve gönderen tüm masrafları üstlenmeyi seçerse (OUR seçeneği) yabancı muhabir bankaların kestiği ek ücretler de eklenebiliyor; KBC\'nin kendi sayfasında belirttiği üzere uygulanan döviz kurları saatlik güncelleniyor. Güncel ücret tutarları bankadan bankaya ve zaman içinde değiştiği için kesin rakam için bankanızın güncel tarife sayfasını veya şubesini kontrol etmeniz gerekiyor.',
                    ],
                    19 => [
                        'tip' => 'baslik',
                        'metin' => 'Özetle',
                    ],
                    20 => [
                        'tip' => 'madde',
                        'metin' => 'Mümkünse klasik SWIFT banka havalesi yerine, Belçika\'da yetkili bir ödeme kuruluşunun (örn. Wise) hesap/banka bağlantılı ödeme veya PISP seçeneğini kullanın; Apple Pay/Google Pay gibi cüzdan ödemelerinden ve kredi kartından mümkünse kaçının — örnekte en pahalıya gelen seçenekler bunlardı',
                    ],
                    21 => [
                        'tip' => 'madde',
                        'metin' => 'Göndermeden hemen önce güncel ücreti ve döviz kurunu ilgili firmanın veya bankanızın resmi sitesinden teyit edin, çünkü fiyatlar günlük ve yönteme göre değişiyor',
                    ],
                    22 => [
                        'tip' => 'madde',
                        'metin' => 'Seçtiğiniz firmanın Belçika\'da yetkili/lisanslı bir ödeme hizmeti sağlayıcısı olduğunu doğrulayın; sorun yaşarsanız FOD Economie/ConsumerConnect üzerinden şikâyet hakkınız var',
                    ],
                ],
                'kaynak_url' => 'https://www.wikifin.be/nl/budget-betalen-lenen-en-verzekeren/betalen-het-buitenland/hoe-kan-je-geld-overschrijven-naar-het',
                'kaynak_aciklama' => 'Ana kaynak Wikifin.be (FSMA\'nın resmi tüketici finans platformu, son güncelleme 12 Şubat 2026) doğrudan çekilerek doğrulandı: SEPA-dışı ülkelere (Türkiye dahil) transferler için önerilen 3 yöntem (IBAN/BIC banka havalesi, dijital cüzdanlar, para transfer büroları) ve "ücretler ülke/tutar/para birimine göre değişir" ifadesi birebir teyit edildi; sayfa FSMA girişimi olduğunu altbilgide açıkça belirtiyor. Ek doğrulanan kaynaklar: FOD Economie (economie.fgov.be/nl/themas/financiele-diensten/betalingsdiensten — "money remittance" ve yetkilendirme zorunluluğu teyit edildi), ConsumerConnect\'in gerçekliği (consumerconnect.be, 2024\'te FOD Economie tarafından kuruldu, bağımsız haber kaynaklarıyla doğrulandı), KBC (kbc.be — OUR/SHA/BEN sistemi ve saatlik kur güncellemesi birebir teyit edildi), ve Wise\'ın resmi Belçika-Türkiye fiyatlandırma sayfası (wise.com/be/send-money/send-money-to-turkey — 1.000 EUR için güncel fiyatlar BUGÜN iki bağımsız yöntemle yeniden çekildi ve taslaktaki rakamlarla ÇELIŞTI; düzeltilmiş rakamlar madde bloklarında verildi). World Bank Remittance Prices sayfası benim denememde de 403 Forbidden döndürdü, taslağın bu kaynağı atlaması doğrulandı/haklı bulundu. Yeni eklenen bağlam: AB\'nin Türkiye\'yi SEPA\'ya katılmaya davet eden Şubat 2026 resmi önerisi (thepaypers.com, paymentsjournal.com, Hürriyet Daily News, Daily Sabah gibi çoklu bağımsız haber kaynağıyla doğrulandı) — henüz gerçekleşmedi ama makaleyle doğrudan ilgili güncel gelişme.',
            ],
            [
                'konuSlug' => 'turkiyeye-para-transferi',
                'country_code' => 'AT',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Türkiye AB/AEA dışında sayılır: SEPA\'nın \'ücretsiz\' güvencesi burada geçerli değil',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Avusturya İşçi Odası\'nın (Arbeiterkammer) belirttiği SEPA kuralına göre, euro cinsinden yapılan standart bir AB havalesi -alıcı hesabı bir AB ülkesinde ya da İzlanda, Lihtenştayn veya Norveç\'te olduğu sürece- yurt içi havale kadar ucuz olmak zorunda. Türkiye bu listenin dışında kaldığı için, Avusturya\'dan Türkiye\'ye yapılan klasik banka (SWIFT) havalelerinde bu güvence geçerli değil; banka için yasal bir ücret tavanı yok.',
                    ],
                    2 => [
                        'tip' => 'paragraf',
                        'metin' => 'Uluslararası havalelerde SHA, OUR, BEN adında standart masraf paylaşım seçenekleri vardır: SHA\'da masraf gönderen ve alıcı arasında paylaşılır (Avusturya bankalarının varsayılan uygulaması budur), OUR\'da tüm masrafı gönderen üstlenir ve alıcıya tam tutar ulaşır, BEN\'de ise tüm masrafı alıcı öder. Bu seçim, Türkiye\'deki alıcıya ulaşan net tutarı doğrudan etkiler; ayrıca araya giren muhabir bankalar kendi masrafını ayrıca kesebilir. Transfer talimatını verirken bankanızdan hangi seçeneğin uygulandığını ve güncel ücret tarifesini açıkça sormak faydalı.',
                    ],
                    3 => [
                        'tip' => 'baslik',
                        'metin' => 'Somut ve doğrulanmış bir seçenek: Viyana merkezli, tam lisanslı bir Türk bankası',
                    ],
                    4 => [
                        'tip' => 'paragraf',
                        'metin' => 'VakıfBank International AG, Türkiye Vakıflar Bankası T.A.O.\'nun tamamına sahip olduğu iştiraki; merkezi Viyana\'da ve kendi ifadesiyle 20 yıldan uzun süredir Avusturya\'da tam bankacılık lisansına sahip. Viyana\'daki hesabı (IBAN: AT59 1969 0000 9002 4191) bir Avusturya IBAN\'ı olduğundan, kendi Avusturya bankanızdan buraya para göndermek genelde yurt içi/SEPA havalesi sayılır. Asıl maliyet VakıfBank\'ın parayı Türkiye\'ye aktarma hizmeti için aldığı ücret: bankanın kendi sitesine göre 200 €\'ya kadar 7 €, 201-500 € arası 8 €, 501-1.000 € arası 10 €, 1.001-2.000 € arası 15 €, 2.001-4.000 € arası 25 €, 4.000 €\'nun üzerinde ise tutarın %0,25\'i artı 20 € sabit masraf uygulanıyor. Para VakıfBank\'a ulaştıktan sonra genellikle aynı gün Türkiye\'ye iletiliyor; toplam süre 1-2 iş günü olarak belirtiliyor.',
                    ],
                    5 => [
                        'tip' => 'madde',
                        'metin' => '1.000 €\'nun altındaki transferlerde tutar alıcının adına gönderilebiliyor; 1.000 € ve üzerinde parayı gönderen kişinin kimliğini ibraz etmesi zorunlu ve tutar Türkiye Vakıflar Bankası\'ndaki bir euro hesabına yatırılıyor.',
                    ],
                    6 => [
                        'tip' => 'madde',
                        'metin' => 'Dikkat: VakıfBank bu sayfada EUR/TL döviz kurunu yayımlamıyor; yukarıdaki tutarlar sabit hizmet bedelidir. Para Türk Lirası\'na ayrıca çevrilecekse, o kur farkı (spread) tabloya dahil olmayan ayrı bir maliyet kalemidir ve özellikle büyük tutarlarda toplam maliyeti sabit ücretten daha çok etkileyebilir. Banka ücret tarifeleri zamanla değişebildiğinden, transfer öncesi güncel tarifeyi VakıfBank\'tan doğrudan teyit etmekte fayda var.',
                    ],
                ],
                'kaynak_url' => 'https://vakifbank.at/en/privatkunden/tuerkei-ueberweisung/',
                'kaynak_aciklama' => 'VakıfBank International AG (Viyana merkezli, Türkiye Vakıflar Bankası T.A.O.\'nun tamamına sahip olduğu iştiraki - bu iştirak/mülkiyet ilişkisi bankanın kendi "Über Uns" sayfasında [vakifbank.at/en/uber-uns/] "We are wholly owned by Türkiye Vakiflar Bankasi T.A.O." ifadesiyle birebir teyit edilmiştir) resmi sitesindeki Türkiye\'ye havale sayfası: ücret tarifesi, süre ve IBAN bilgisi bu sayfanın ham HTML kaynağından satır satır doğrulanmıştır (2026-08-22 itibarıyla). SEPA/AB kapsamı iddiası ayrıca Avusturya İşçi Odası\'nın (arbeiterkammer.at/beratung/konsument/Geld/Bargeldloszahlen/Zahlungsanweisung.html) doğrudan alıntısıyla çapraz doğrulanmıştır.',
            ],
            [
                'konuSlug' => 'ogrenci-genc-hesabi',
                'country_code' => 'DE',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Almanya\'da öğrenci/genç hesabı (Studentenkonto) nedir',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Almanya\'daki bankaların büyük çoğunluğu, örgün öğrencilere, çıraklara (Azubi) ve genellikle belirli bir yaşın altındaki gençlere hesap işletim ücreti alınmayan özel bir Girokonto (vadesiz hesap) sunar. Ortak mantık şudur: belirlenen yaş sınırının altındaysanız ve eğitim/öğrencilik durumunuzu belgeleyebiliyorsanız hesap tamamen ücretsizdir; yaş sınırını geçtiğinizde ya normal ücretli hesaba geçilir ya da hesabın ücretsiz kalabilmesi için hesaba düzenli asgari bir aylık para girişi şartı devreye girer.',
                    ],
                    2 => [
                        'tip' => 'madde',
                        'metin' => 'Deutsche Bank – Das Junge Konto: AB içinde bir ikamet adresi (Meldeadresse) olan öğrenci, çırak ve Bundesfreiwilligendienst (federal gönüllü hizmeti) katılımcılarına 30 yaşına kadar tamamen ücretsizdir; temel Deutsche Bank Card (Girocard, Mastercard özellikli banka/debit kartı) hesaba dahildir ve ücretsizdir. İsteğe bağlı iki farklı yükseltme kartı var, bunları karıştırmamak gerekir: (1) Deutsche Bank Card Plus, 12 yaşından itibaren alınabilen, online alışverişe uygun bir DEBIT karttır, bonite şartı yoktur, yıllık 18 EUR. (2) Gerçek Mastercard kredi kartı ise yalnızca 18 yaşından itibaren ve bonite (düzenli gelir) şartıyla açılabilir, yıllık ücreti 18 EUR değil 39 EUR\'dur. Açılışta geçerli kimlik/pasaport ve eğitim/öğrencilik durumu belgesi (Ausbildungsnachweis) isteniyor. Kaynak: https://www.deutsche-bank.de/pk/konto-und-karte/konten-im-ueberblick/das-junge-konto.html (kart fiyat/yaş ayrımı için ayrıca bkz. https://www.kontofinder.de/banken/deutsche-bank/junges-konto/ ve https://www.capitalo.de/anbieter/deutsche-bank/produkte/das-junge-konto)',
                    ],
                    3 => [
                        'tip' => 'madde',
                        'metin' => 'DKB – Studierendenkonto: 28. yaş gününe kadar herhangi bir asgari para girişi şartı olmadan ücretsizdir. 28 yaşını doldurduktan sonra hesabın ücretsiz kalabilmesi için hesaba ayda en az 700 EUR düzenli gelir girişi gerekir; bu şart sağlanmazsa aylık 4,50 EUR hesap işletim ücreti uygulanır. Visa Debit kartı hesaba dahil ve ücretsizdir. Kaynak: https://www.dkb.de/privatkunden/girokonto/studierendenkonto',
                    ],
                    4 => [
                        'tip' => 'madde',
                        'metin' => 'Sparkasse: Almanya\'nın en yaygın şube ağına sahip banka grubu olsa da tek bir merkezi banka değil, aralarında bağımsız olan çok sayıda yerel Sparkasse\'den oluşur. Sparkasse\'nin kendi resmi sitesi \'Girokonto ücretleri Sparkasse\'den Sparkasse\'ye değişir\' demektedir; yani öğrenci/çırak hesabındaki yaş sınırı ve ücretsizlik koşulu yaşadığınız şehrin Sparkasse\'sine göre değişebilir (örneğin bazı yerel Sparkasse\'lerde 24-25, bazılarında 27-30 yaşına kadar ücretsizlik uygulanıyor), başvurmadan önce yerel şubeye sorulması gerekir. Ortak avantaj olarak yaklaşık 20.000 Sparkasse ATM\'sinde ücretsiz nakit çekilebilir. Kaynak: https://www.sparkasse.de/unsere-loesungen/privatkunden/rund-ums-konto/girokonto-studenten-azubis.html',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'Genelde istenen belgeler: geçerli kimlik/pasaport, öğrencilik belgesi (Immatrikulationsbescheinigung) veya çıraklık/eğitim belgesi, bazı bankalarda ayrıca AB içi bir adres kaydı. Bu belgeler henüz elinizde olmasa bile, AB\'de yasal olarak ikamet eden herkesin (yeni gelen göçmenler, sığınmacılar ve geçici koruma/Duldung statüsündekiler dahil) BaFin (Almanya Federal Finansal Denetim Kurumu) güvencesindeki \'Basiskonto\' (temel hesap) açtırma yasal hakkı vardır; banka başvuruyu reddederse BaFin\'e ücretsiz idari başvuru yapılabilir. Kaynak: https://www.bafin.de/DE/verbraucherinnen-verbraucher/themen-finanzprodukte/konten-zahlungen/konten/basiskonto/basiskonto_node.html',
                    ],
                ],
                'kaynak_url' => 'https://www.dkb.de/privatkunden/girokonto/studierendenkonto',
                'kaynak_aciklama' => 'Ana kaynaklar taslaktakiyle aynı ve hepsi bağımsızca yeniden kontrol edildi: DKB\'nin resmi "Studierendenkonto" sayfası, Deutsche Bank\'ın resmi "Das Junge Konto" sayfası, Sparkasse\'nin resmi öğrenci/çırak Girokonto sayfası ve BaFin\'in resmi Basiskonto sayfası. DKB, Sparkasse ve BaFin bloklarındaki tüm iddialar doğrulandı ve değişiklik gerekmedi. Deutsche Bank bloğunda ise kart fiyatlandırması hatalıydı: sayfadaki "18 EUR/yıl" bir DEBIT kart (Deutsche Bank Card Plus, 12 yaş+, bonite şartsız) fiyatıdır; 18 yaş+ ve bonite şartlı GERÇEK Mastercard kredi kartının yıllık ücreti 39 EUR\'dur. Bu düzeltme https://www.kontofinder.de/banken/deutsche-bank/junges-konto/ ve https://www.capitalo.de/anbieter/deutsche-bank/produkte/das-junge-konto kaynaklarıyla (ikisi de birbirinden bağımsız ve tutarlı) çapraz doğrulandı.',
            ],
            [
                'konuSlug' => 'ogrenci-genc-hesabi',
                'country_code' => 'NL',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Hollanda\'da Öğrenci ve Genç Hesabı Sunan Bankalar',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Hollanda\'daki büyük bankaların çoğu, 18 yaşından küçükler için ücretsiz bir \'jongerenrekening\' (gençlik hesabı), 18 yaşını dolduran öğrenciler için ise ayrı bir \'studentenrekening\' (öğrenci hesabı) sunuyor. Hollanda Tüketici Birliği\'nin (Consumentenbond) karşılaştırmasına göre bu hesaplar ABN AMRO\'da 16-30, ASN Bank\'ta 17-29 ve ING\'de 18-30 yaş aralığında açılabiliyor; fintech bankası bunq ise normalde aylık 9,99 euro olan paketini DUO\'dan öğrenim finansmanı alan öğrencilere ücretsiz sunuyor. Rabobank ise Şubat 2026\'da eski \'StudentenPakket\' ürününü kaldırarak yerine \'Rabo Free\' adlı hesabı getirdi: yaş aralığı 18-28\'e yükseldi ve artık yalnızca öğrencilere değil aynı yaş grubundaki herkese (çalışanlar dahil) açık — yani Rabobank\'ta hesap açmak için öğrencilik şartı aranmıyor. Çoğu öğrenci/genç hesabında, çocuk hesaplarında olduğu gibi sabit bir aylık işletim ücreti alınmıyor.',
                    ],
                    2 => [
                        'tip' => 'baslik',
                        'metin' => 'Başvuru Şartları ve Hesap Türü Değişimi',
                    ],
                    3 => [
                        'tip' => 'paragraf',
                        'metin' => 'Consumentenbond\'a göre ABN AMRO, ASN Bank, ING ve bunq\'ta öğrenci hesabı açabilmenin genel şartı, DUO\'dan (Dienst Uitvoering Onderwijs – Hollanda Eğitim Yürütme Kurumu) öğrenim finansmanı/bursu almak ve/veya tam zamanlı bir mbo, hbo ya da wo (üniversite) programına kayıtlı olmaktır (Rabobank\'ın \'Rabo Free\' hesabı bu kuralın istisnasıdır ve öğrencilik şartı aramaz). Öğrencilik durumu sona erdiğinde ya da yaş sınırı dolduğunda hesap otomatik olarak standart bir yetişkin hesabına dönüşüyor; Consumentenbond\'un hesaplamalarına göre bu durumda maliyetler 2-3 kata kadar artabiliyor, bu nedenle mezuniyet ya da okulu bırakma sonrasında hesap türünü kontrol etmekte fayda var.',
                    ],
                    4 => [
                        'tip' => 'paragraf',
                        'metin' => 'Hollanda Hükümeti\'nin (Rijksoverheid.nl) resmi açıklamasına göre, Hollanda\'da vergi mükellefiyseniz banka hesabı için BSN\'nizi (burgerservicenummer / yurttaş hizmet numarası) bankaya bildirmeniz gerekiyor; banka bu numarayı Vergi Dairesi\'ne (Belastingdienst) raporlamakla yükümlü. Erasmus Üniversitesi Rotterdam\'ın uluslararası öğrencilere yönelik resmi rehberine göre, henüz BSN\'niz olmasa bile ABN AMRO, ING, Rabobank ve bunq gibi bankalarda hesap açabiliyorsunuz; BSN\'nizi genellikle hesap açıldıktan sonraki yaklaşık 90 gün içinde (bankaya göre değişebilir) bankaya bildirmeniz isteniyor.',
                    ],
                    5 => [
                        'tip' => 'madde',
                        'metin' => 'Hesap açarken tipik olarak istenen belgeler: geçerli bir pasaport veya kimlik kartı, (varsa) oturum izni, Hollanda\'da bir ikamet adresi; bazı bankalar ayrıca eğitim kurumuna kayıt belgesi de talep edebiliyor.',
                    ],
                ],
                'kaynak_url' => 'https://www.consumentenbond.nl/betaalrekening/betaalrekening-voor-studenten — Ana kaynak: Consumentenbond, bankalara göre öğrenci/genç ödeme hesabı karşılaştırması (29 Ocak 2026 güncel; ABN AMRO 16-30, ASN Bank 17-29, ING 18-30 yaş aralıkları, DUO/tam-zamanlı-eğitim şartı, sabit ücret olmaması, "2 tot 3 keer" (2-3 kat) maliyet artışı bu sayfadan birebir teyit edildi). DÜZELTME kaynağı: Rabobank\'ın kendi resmi sayfaları — https://www.rabobank.nl/particulieren/betalen/bankrekening/rabo-free ve İngilizce sürümü https://www.rabobank.nl/en/personal/payments/banking-account/rabo-free (başlıkları: "de gratis betaalrekening voor 18 tot 28 jaar" / "Free banking account for everyone aged 18 to 28") — Rabobank\'ın Şubat 2026\'da eski StudentenPakket\'i (18-25, yalnız öğrenciler) kaldırıp yerine Rabo Free\'yi (18-28, öğrencilik şartı olmadan herkese açık) getirdiğini gösteriyor; Consumentenbond\'un 29 Ocak 2026 güncellemesi bu değişiklikten önce olduğu için ana kaynakta bu tek noktada eski rakam kalmış. Destekleyici resmi kaynaklar (değişmedi): Rijksoverheid.nl — BSN gerekliliği: https://www.rijksoverheid.nl/onderwerpen/privacy-en-persoonsgegevens/vraag-en-antwoord/heb-ik-een-burgerservicenummer-bsn-nodig-om-een-bankrekening-te-openen — ve Erasmus University Rotterdam (eur.nl) — BSN\'siz hesap açma rehberi: https://www.eur.nl/en/education/practical-matters/orientation-arrival/dutch-bank-account',
                'kaynak_aciklama' => 'Ana kaynak Consumentenbond büyük ölçüde doğrulandı ve güncel (29 Ocak 2026), ancak Rabobank\'ın Şubat 2026\'da yaptığı bir ürün değişikliğini yakalamamış: eski "Rabo StudentenPakket" (18-25 yaş, yalnızca öğrenciler) kaldırılıp yerine "Rabo Free" (18-28 yaş, öğrencilik şartı olmadan herkese açık) getirildi. Bu değişiklik Rabobank\'ın kendi resmi Hollandaca ve İngilizce sayfalarından doğrulandı. Diğer tüm banka yaş aralıkları (ABN AMRO 16-30, ASN Bank 17-29, ING 18-30), bunq fiyatlandırması, BSN/Belastingdienst kuralı, EUR.nl\'nin 90 günlük BSN bildirim penceresi ve "2-3 kat" maliyet artışı iddiası bağımsız olarak birebir doğrulandı, değişiklik gerekmedi.',
            ],
            [
                'konuSlug' => 'ogrenci-genc-hesabi',
                'country_code' => 'FR',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Fransa\'da banka hesabı açma hakkınız yasayla güvence altında',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Fransa\'da ikamet eden herkes, uyruk fark etmeksizin, banka hesabı açma hakkına sahiptir. Bir banka başvurunuzu reddederse (veya 15 gün içinde yanıt vermezse), resmi service-public.gouv.fr sitesinde anlatılan \'droit au compte\' (hesap açma hakkı) prosedürüyle Banque de France\'a başvurabilirsiniz: Banque de France, belgeleriniz elindeyken 1 iş günü içinde size temel bankacılık hizmetlerini (kart, çek defteri, havale/otomatik ödeme talimatı) sağlamakla yükümlü bir banka atar; atanan banka da gerekli belgeleri aldıktan sonra 3 iş günü içinde hesabınızı açmak zorundadır.',
                    ],
                    2 => [
                        'tip' => 'baslik',
                        'metin' => 'Uluslararası öğrenci olarak hangi belgeler isteniyor?',
                    ],
                    3 => [
                        'tip' => 'paragraf',
                        'metin' => 'Fransız devletinin resmi yurt dışı eğitim kurumu Campus France\'a göre hesap açmak için üç belge gerekiyor: geçerli kimlik belgesi (kimlik kartı, pasaport veya oturum izni — ehliyet kabul edilmiyor); Fransa\'da ikametgah belgesi (kira sözleşmesi, ev sahibi beyanı veya fatura — henüz konutunuz yoksa bazı durumlarda okulunuzun uluslararası ilişkiler ofisinin adresi kullanılabiliyor); ve okul kayıt belgesi (attestation de scolarité) veya öğrenci kartı. Önemli not: Hesap açmak için oturum izni yasal bir şart değildir (Fransız hukukunda bunu zorunlu kılan bir madde yok) — ama bankaların kendi resmi sayfalarına göre (örneğin Société Générale\'in öğrenci hesabı sayfası: \'bazı durumlarda vize veya oturum izni de istenebilir\'), özellikle Avrupa Birliği/AEA dışından gelen öğrencilerden pratikte vize (VLS-TS uzun süreli vize) veya oturum izni (titre de séjour) de istenebiliyor. Bu, bankadan bankaya değişen bir uygulamadır ve Campus France\'ın kendi sayfasında yer almaz.',
                    ],
                    4 => [
                        'tip' => 'baslik',
                        'metin' => 'Geleneksel bankalarda \'genç/öğrenci hesabı\' örnekleri ve online banka uyarısı',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'La Banque Postale\'nin resmi sitesine göre 18-25 yaş için Réalys kartlı \'Compte Jeune\' aylık 2,60 €\'dan, 26-29 yaş için ise 4,10 €\'dan başlıyor ve ilk 6 ay ücretsiz sunuluyor. Société Générale\'in resmi sitesine göre 18-24 yaş \'Compte Jeune\' hesabında hesap işletim ücreti ve hesap açılışında asgari para yatırma şartı bulunmuyor (kart ücreti seçilen karta göre değişir). BoursoBank gibi ücretsiz online bankaların resmi yardım sayfasına göre, Fransa\'da vergi mukimi olmayan (yabancı vergi mukimi sayılan) başvuru sahipleri standart online/uygulama süreciyle değil, bankanın ilgili biriminden (Service Commercial) doğrudan destek alarak ayrı bir süreçle değerlendiriliyor.',
                    ],
                ],
                'kaynak_url' => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F2417',
                'kaynak_aciklama' => 'Ana kaynak: Fransız hükümetinin resmi idari bilgi portalı service-public.gouv.fr\'nin "droit au compte" (hesap açma hakkı) sayfası — https://www.service-public.gouv.fr/particuliers/vosdroits/F2417 (bu oturumda canlı WebFetch ile doğrulandı: ikamet şartı/uyruksuzluk, 15 gün, 1 iş günü, 3 iş günü rakamları birebir teyit edildi).

Ek doğrulanan resmi kaynaklar: (1) Campus France resmi sayfası, üç temel belge için — https://www.campusfrance.org/en/getting-a-bank-account (canlı WebFetch ile TAM METİN iki kez çekildi; üç belge listesi doğru, ANCAK bu sayfa AB dışı öğrenciler için vize/oturum izni şartından hiç bahsetmiyor — taslaktaki bu atıf hatalıydı ve düzeltildi). (2) La Banque Postale resmi "Compte Jeune" sayfası, fiyatlandırma için — https://www.labanquepostale.fr/particulier/comptes-et-cartes/comptes-bancaires/ouverture-de-compte-jeune.html (canlı WebFetch ile 18-25 yaş=2,60€/ay ve 26-29 yaş=4,10€/ay Réalys fiyatları ile "ilk 6 ay ücretsiz" birebir teyit edildi). (3) Société Générale resmi "compte jeune" sayfası — https://particuliers.sg.fr/ouvrir-compte-bancaire-en-ligne/ouverture-compte-jeunes (canlı WebFetch ile hesap işletim ücretsizliği ve asgari yatırma şartı olmadığı birebir teyit edildi). (4) YENİ EKLENEN kaynak — Société Générale\'in ayrı "öğrenci hesabı" sayfası: https://particuliers.sg.fr/nos-comptes-bancaires/ouverture-compte-jeune-etudiant (canlı WebFetch ile teyit edildi: "bazı durumlarda vize veya oturum izni de istenebilir" ifadesi burada geçiyor — bu resmi banka kaynağı, taslağın Campus France\'a yanlış atfettiği vize/oturum izni notunun doğru kaynağı olarak kullanıldı ve ifade "kesin şart" yerine "bazı durumlarda istenebilir" şeklinde yumuşatıldı). (5) BoursoBank resmi yardım merkezi sayfası — https://www.boursobank.com/aide-en-ligne/prospects/ouverture-de-compte/question/puis-je-ouvrir-un-compte-en-tant-que-resident-fiscal-etranger-20166926 (canlı WebFetch ile teyit edildi: "résident fiscal étranger" = Fransa\'da vergi mukimi OLMAYAN kişi demek; bu kişiler standart uygulama yerine "Service Commercial" ile özel bir süreçten geçiyor).

Banque de France\'ın kendi sitesi (banque-france.fr) bu oturumda da erişilemedi (403); onun yerine aynı bilgiyi resmi olarak yayınlayan service-public.gouv.fr ve Campus France\'ın "droit au compte" atıfı kullanıldı — orijinal taslağın da yaptığı doğru bir tercih, aynen korundu.',
            ],
            [
                'konuSlug' => 'ogrenci-genc-hesabi',
                'country_code' => 'BE',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Belçika\'da ayrı bir \'öğrenci hesabı\' değil, yaşa dayalı \'genç hesabı\' var',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belçika\'daki büyük bankaların çoğunda öğrenciye özel ayrı bir hesap türü yok; bunun yerine belirli bir yaşın altındaki herkese (öğrenci, çalışan veya işsiz fark etmeksizin) ücretsiz sunulan bir \'genç hesabı\' (Flamanca: jongerenrekening, Fransızca: compte jeunes) var. Yani üniversiteye kayıtlı olmak şart değildir, belirleyici olan yaştır.',
                    ],
                    2 => [
                        'tip' => 'baslik',
                        'metin' => 'KBC – Genç Hesabı (Young Person\'s Account)',
                    ],
                    3 => [
                        'tip' => 'paragraf',
                        'metin' => 'KBC\'nin resmi sitesine göre bu hesap 10-24 yaş arasındaki herkese tamamen ücretsizdir ve öğrenci olma şartı aranmaz. Hesaba iki adet kişiselleştirilmiş banka kartı ücretsiz olarak dahildir.',
                    ],
                    4 => [
                        'tip' => 'madde',
                        'metin' => '18 yaşından küçükler için haftalık harcama limiti 900 EUR\'dur (bu limit ayarlanabilir); 18 yaş üstü ve geliri olanlar isteğe bağlı olarak kredi kartı da ekleyebilir.',
                    ],
                    5 => [
                        'tip' => 'madde',
                        'metin' => 'Belçika vatandaşlığı veya kalıcı adresi olmayanlar da online başvuru başlatabilir; ancak banka kartını teslim almak için şubeye kimlik belgesi ve ikamet izniyle şahsen gidilmesi gerekir.',
                    ],
                    6 => [
                        'tip' => 'baslik',
                        'metin' => 'Belfius – Beats New Hesabı',
                    ],
                    7 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belfius\'un resmi sitesine göre Beats New hesabı 25. yaş gününe kadar ücretsizdir; normal fiyatı aylık 5,50 EUR\'dur ve 25 yaşından sonra otomatik olarak bu ücretli tarifeye geçilir. Bu hesapta da öğrenci şartı yoktur, ölçüt yalnızca yaştır.',
                    ],
                    8 => [
                        'tip' => 'madde',
                        'metin' => 'Hesaba iki adet Mastercard (banka/kredi) kartı dahildir; online hesap açma 12 yaşından itibaren mümkündür.',
                    ],
                    9 => [
                        'tip' => 'baslik',
                        'metin' => 'Reşit olmayanlar için resmi kurallar (Wikifin / FSMA)',
                    ],
                    10 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belçika\'nın resmi finansal piyasalar ve hizmetler otoritesi FSMA\'nın tüketici bilgilendirme sitesi Wikifin.be\'ye göre bankaların çoğu vadesiz hesap açmak için minimum yaşı 10 olarak belirler; 18 yaşından küçük biri hukuki işlem yapamayacağı için ebeveyn veya vasinin onayı ve imzası zorunludur.',
                    ],
                    11 => [
                        'tip' => 'madde',
                        'metin' => 'Kanunen, reşit olmayan bir kişiye kredi (kredi kartı dahil) verilmesi yasaktır.',
                    ],
                    12 => [
                        'tip' => 'madde',
                        'metin' => 'Hesaptaki paranın tam yasal sahipliği, kişinin 18. yaş gününde kendisine geçer.',
                    ],
                    13 => [
                        'tip' => 'baslik',
                        'metin' => 'Hangi bankalara bakılabilir?',
                    ],
                    14 => [
                        'tip' => 'paragraf',
                        'metin' => 'Gent Üniversitesi\'nin (Ghent University) uluslararası öğrenciler için resmi rehberine göre Belçika\'da yaygın olarak kullanılan ve öğrencilere önerilen bankalar arasında KBC, Belfius, BNP Paribas Fortis, ING, Argenta, AXA Bank Belgium, Beobank ve bpost Bank sayılabilir. Hangi banka seçilirse seçilsin, hesap açmadan önce vize/ikamet izni durumunun netleşmiş olması önerilir; kesin belge listesi bankaya göre değişebileceğinden başvurudan hemen önce ilgili bankanın kendi resmi sitesinden teyit edilmesi en sağlıklısıdır.',
                    ],
                ],
                'kaynak_url' => 'https://www.wikifin.be/fr/budget-payer-emprunter-et-assurer/compte-vue/partir-de-quel-age-votre-enfant-peut-il-ouvrir-un',
                'kaynak_aciklama' => 'Ana kaynak: Wikifin.be — Belçika\'nın resmi finansal piyasalar ve hizmetler otoritesi FSMA\'nın yürüttüğü tüketici bilgilendirme sitesi (vadesiz hesap açma yaşı ve reşit olmayanlar için kurallar). Banka bazlı somut bilgiler doğrudan resmi banka sitelerinden doğrulanmıştır: KBC Genç Hesabı — https://www.kbc.be/retail/en/products/payments/current-accounts/young-person-s-account.html ve https://www.kbc.be/retail/en/payments/open-an-account-online.html ; Belfius Beats New — https://www.belfius.be/retail/fr/produits/paiement/compte-bancaire/beats-new/index.aspx ve https://www.belfius.be/retail/fr/moments-cles/enfants/index.aspx ; önerilen banka listesi için Gent Üniversitesi resmi rehberi — https://www.ugent.be/en/research/globalsouth/exchange-phd/how-to-open-bankaccount-as-international-student-or-researcher',
            ],
            [
                'konuSlug' => 'ogrenci-genc-hesabi',
                'country_code' => 'AT',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'İki Farklı Ürün: Jugendkonto ve Studentenkonto',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Avusturya bankaları gençlere iki ayrı ürün sunar: okul öğrencileri ve çıraklar için \'Jugendkonto\' (gençlik hesabı) ile üniversite/yüksekokulda kayıtlı öğrenciler için \'Studentenkonto\' (öğrenci hesabı). Ne var ki yaş sınırları tüm bankalarda aynı değildir: Jugendkonto çoğunlukla 14 yaşından açılabilir ama üst sınır bankaya göre değişir (Raiffeisen ve Erste Bank\'ın spark7\'si 19. doğum gününe kadar; Bank Austria\'nın MegaCard\'ı 7-20 yaş aralığında; bank99\'un aktivkonto99 jugend\'i 14-20 yaş aralığında). Studentenkonto ise çoğu bankada (Erste, Raiffeisen, easybank, bank99) 27 yaşına kadar geçerliyken Bank Austria\'da bildirildiğine göre 30 yaşına kadar uzuyor. Buna rağmen Erste Bank (spark7), Raiffeisen, easybank, Bank Austria ve bank99 gibi büyük bankaların tamamı bu hesapları geçerlilik süreleri boyunca aylık işletim ücreti almadan sunuyor.',
                    ],
                    2 => [
                        'tip' => 'paragraf',
                        'metin' => 'Jugendkonto örneği (Raiffeisen): 14 yaşından itibaren açılabilir ve 19. doğum gününe kadar hesap işletimi, banka kartı ve internet bankacılığı tamamen ücretsizdir. Erste Bank\'ın spark7 hesabı da öğrenci/çırak statüsündeki gençler için aynı şekilde 19 yaşına kadar ücretsizdir. Ebeveyn onayı kuralı bankadan bankaya değişir: Raiffeisen\'de 18 yaşına kadar ebeveyn/yasal vasinin bankaya bizzat gelip onay vermesi istenir; Erste Bank\'ta 14 yaşından itibaren düzenli kendi geliri (maaş veya çıraklık ücreti) olan gençler ebeveyn onayı olmadan hesap açabilir; easybank ise 14 yaşından itibaren hesabın ebeveyn imzası gerekmeden tamamen bağımsız açılabildiğini kendi sitesinde belirtir. Bu fark, Avusturya Medeni Kanunu\'nda (ABGB) 14 yaşından itibaren kişinin kendi kazancı üzerinde serbestçe tasarruf edebilmesi kuralına dayanır.',
                    ],
                    3 => [
                        'tip' => 'madde',
                        'metin' => 'Raiffeisen Jugendkonto için istenen belgeler: geçerli fotoğraflı kimlik (nüfus cüzdanı veya pasaport) ve 6 aydan eski olmayan \'Meldezettel\' (Avusturya ikamet kayıt belgesi). Not: bu liste bankaya özgüdür — örneğin easybank\'ta Meldezettel yerine \'en az 1 yıldır Avusturya\'da ana ikametgah\' şartı aranır.',
                    ],
                    4 => [
                        'tip' => 'paragraf',
                        'metin' => 'Studentenkonto ise üniversiteye kayıtlı öğrenciler içindir ve açılış için geçerli kimliğin yanı sıra üniversiteden alınan güncel \'Inskriptionsbestätigung\' (kayıt/tescil belgesi) istenir (örn. Raiffeisen Studentenkonto, 27. doğum gününe kadar ücretsiz). Hesabın öğrenci statüsünü ve ücretsizliğini koruyabilmesi için bu belgenin düzenli aralıklarla — bankaya göre her akademik yıl veya her sömestr — yeniden bankaya ibraz edilmesi genellikle gerekir.',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'Avusturya\'nın resmi tüketici koruma kurumu Arbeiterkammer\'in uyarısına göre bu hesaplarda temel işlemler (hesap işletimi, ödemeler, bankamatik çekimi) ücretsiz görünse de gişe işlemleri, belgeli/kağıt işlemler ve hesabın eksiye düşmesi (borçlanma faizi ve ek masraflar) gibi kalemler ücretli olabilir; ayrıca yaş sınırı aşıldığında hesap genellikle otomatik olarak normal (ücretli) bir hesaba dönüşür — bu yüzden başlangıç bonuslarına değil, toplam maliyete ve dahil hizmetlere bakılması öneriliyor.',
                    ],
                ],
                'kaynak_url' => 'https://www.arbeiterkammer.at/studentenkonto',
                'kaynak_aciklama' => 'Genel tüketici uyarısı (gizli ücretler, gişe/ekstre masrafları, yaş sınırı aşımında otomatik dönüşüm — son paragraf) için: Avusturya İşçi Odası / Arbeiterkammer, https://www.arbeiterkammer.at/studentenkonto (doğrudan okundu; bu sayfa yalnızca genel uyarılar içerir, spesifik yaş/banka rakamları içermez — taslaktaki "ana kaynak" ilişkilendirmesi bu nedenle yanıltıcıydı). Banka bazlı yaş sınırı/ücret/belge/ebeveyn-onayı detayları şu resmi banka sayfalarından doğrulandı: Raiffeisen Jugendkonto — https://www.raiffeisen.at/noew/rlb/de/privatkunden/konto-karten/jugendkonto.html (14-19 yaş, Meldezettel, 18 yaşına kadar ebeveyn onayı); Raiffeisen Studentenkonto — https://www.raiffeisen.at/de/privatkunden/konto/studentenkonto.html (27 yaşına kadar, Inskriptionsbestätigung); Erste Bank/Sparkasse spark7 — https://www.sparkasse.at/erstebank/privatkunden/konto-karten/jugendkonto (19 yaşına kadar, 14+ kendi geliriyle ebeveynsiz açılış); easybank easy youth — https://www.easybank.at/easybank/konto/easy-youth (14-<18 yaş, ebeveyn imzası gerekmez, 1 yıllık ikamet şartı); Bank Austria MegaCard/Jugendkonto — https://www.bankaustria.at/megacard/index.jsp (7-20 yaş); bank99 Spezialkonten — https://bank99.at/spezialkonten/aktivkonto99jugend ve https://bank99.at/spezialkonten/aktivkonto99bildung (jugend 14-20 yaş, bildung eğitim süresi+12 ay, en fazla 27 yaşına kadar).',
            ],
            [
                'konuSlug' => 'online-banka-mi-geleneksel-mi',
                'country_code' => 'DE',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Yasal hak: Herkes temel banka hesabı açtırabilir',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Almanya\'da 2016\'dan beri yürürlükte olan Ödeme Hesapları Kanunu (Zahlungskontengesetz) sayesinde, Avrupa Birliği\'nde yasal olarak ikamet eden herkes -sığınmacılar dahil- kredi geçmişinden bağımsız olarak "Basiskonto" adı verilen temel bir hesap açtırma hakkına sahiptir; ödeme hesabı sunan her banka, ister online ister geleneksel, bu hesabı açmakla yükümlüdür.',
                    ],
                    2 => [
                        'tip' => 'baslik',
                        'metin' => 'Online mi, geleneksel mi: temel fark',
                    ],
                    3 => [
                        'tip' => 'madde',
                        'metin' => 'Online banka (örn. N26): N26\'nın resmi bilgilerine göre sadece geçerli pasaport veya kimlik kartı yeterli, Almanya\'da kayıtlı adres belgesi (Meldebescheinigung) istenmiyor; kimlik doğrulama birkaç dakikalık görüntülü görüşmeyle tamamlanıyor',
                    ],
                    4 => [
                        'tip' => 'madde',
                        'metin' => 'Geleneksel/şube bankası (örn. Sparkasse, Postbank): hesap açmak genellikle şubeye randevu almayı ve güncel bir adres kayıt belgesi (Meldebescheinigung) ibraz etmeyi gerektiriyor; AB dışından gelenlerden ayrıca oturum izni (Aufenthaltstitel) istenebiliyor',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'Bu fark, henüz oturma kaydını (Anmeldung) tamamlayamamış yeni gelenler için online bankaları pratikte daha erişilebilir kılıyor: büyük şehirlerde Anmeldung/Bürgeramt randevusu birkaç haftadan birkaç aya kadar sürebiliyor -örneğin Berlin\'de güncel raporlara göre ortalama bekleme süresi 8-12 hafta olup yoğun dönemlerde 14 haftayı aşabiliyor- ama bu süre boyunca bile online bankada hesap açıp IBAN almak mümkün oluyor.',
                    ],
                ],
                'kaynak_url' => 'https://www.verbraucherzentrale.de/wissen/geld-versicherungen/sparen-und-anlegen/das-recht-auf-ein-basiskonto-fuer-neu-in-deutschland-angekommene-12224',
                'kaynak_aciklama' => 'Almanya\'nın resmi tüketici koruma kuruluşu Verbraucherzentrale\'nin "Almanya\'ya yeni gelenler için Basiskonto hakkı" sayfası (güncelleme: 11 Temmuz 2025) — Zahlungskontengesetz\'e dayalı Basiskonto hakkını, hak sahiplerini (Asylsuchende, Geduldete dahil) ve kredi geçmişinden bağımsızlığı doğruluyor. Almanya\'nın finansal denetim kurumu BaFin\'in resmi Basiskonto sayfası (bafin.de/DE/Verbraucher/Bank/Produkte/Basiskonto/basiskonto_node.html) ile çapraz doğrulandı: "Alle Verbraucherinnen und Verbraucher, die sich rechtmäßig in der Europäischen Union aufhalten, haben grundsätzlich Anspruch auf ein Basiskonto" ve "Jede Bank, die Zahlungskonten für Verbraucherinnen und Verbraucher anbietet, muss auch Basiskonten bereitstellen" ifadeleriyle her banka (online dahil) yükümlülüğünü teyit ediyor. N26\'nın resmi sayfaları (n26.com/de-de/konto-eroeffnen, n26.com/de-de/blog/konto-eroeffnen-ohne-wohnsitz-in-deutschland, support.n26.com) hesap açma şartlarını (sadece kimlik, Meldebescheinigung istenmiyor, birkaç dakikalık video doğrulama) doğruladı. NOT: Bu üç kaynak da Berlin\'deki Anmeldung/Bürgeramt randevu bekleme süresine dair hiçbir rakam vermiyor — o istatistik ayrı ve güncel bir kaynakla (örn. service.berlin.de resmi bekleme süresi verileri veya güncel basın raporları) desteklenmeli; taslaktaki "ortalama bir ay" rakamı 2025 ortası verisine dayanıyor gibi görünüyor ve 2026 raporlarıyla (8-12 hafta) çelişiyor, bu yüzden ilgili blokta güncellendi.',
            ],
            [
                'konuSlug' => 'online-banka-mi-geleneksel-mi',
                'country_code' => 'NL',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'BSN olmadan hesap açılabilir, ama 90 gün süre var',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Hollanda\'da hesap açarken bankalar BSN (burgerservicenummer / vatandaş hizmet numarası) ister, fakat yeni gelenlerin çoğu bu numarayı henüz almamış olur. Hem online banka bunq hem de geleneksel banka ING, hesabı BSN olmadan açmanıza izin veriyor; karşılığında BSN\'nizi en geç 90 gün içinde bildirmeniz gerekiyor, aksi halde hesap kısıtlanıyor.',
                    ],
                    2 => [
                        'tip' => 'baslik',
                        'metin' => 'Online banka bunq: yeni gelen için en hızlı yol',
                    ],
                    3 => [
                        'tip' => 'paragraf',
                        'metin' => 'bunq, Hollanda merkez bankası De Nederlandsche Bank\'tan (DNB) tam bankacılık lisansına sahip, tamamen dijital bir banka. Hesap uygulama üzerinden yaklaşık 5 dakikada sadece kimlik/pasaportla açılıyor, arayüz İngilizce ve halihazırda AB/AEA bölgesinde ikamet edenler Hollanda\'ya taşınmadan önce bile başvurabiliyor. Mevduat güvencesi açısından geleneksel bankalardan farkı yok: Hollanda\'da lisanslı her banka gibi bunq\'taki paranız da devletin mevduat güvence sistemi (depositogarantiestelsel) kapsamında kişi başı 100.000 avroya kadar korunuyor.',
                    ],
                    4 => [
                        'tip' => 'baslik',
                        'metin' => 'Geleneksel bankada fark büyük: ING kolay, ABN AMRO adres istiyor',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'ING\'in uygulamasından hesap açmak için de sadece pasaport veya oturum izni yeterli; Hollanda adresi ya da BSN baştan şart değil, BSN yine 90 gün içinde bildirilebiliyor. ABN AMRO\'da ise durum farklı: bankanın kendi resmî sitesine göre BSN\'siz hesap açma seçeneğinde bile başvuru sahibinin Hollanda\'da kayıtlı bir adresi olması şart — banka bunu "Hollanda\'ya taşındığınız andan itibaren hesap açabilirsiniz" diye özetliyor, yani henüz adresi olmayan biri ABN AMRO\'da hesap açamıyor. Bankanın "Uluslararası Müşteriler Masası" (International Clients Desk) adında bir destek hattı var, fakat bu hat adres şartını atlatan bir yol değil: ABN AMRO\'nun kendi açıklamasına göre bu masa zaten Hollanda\'da yaşayan expat\'lara, kalıcı olarak yurt dışında adresi bulunan "non-resident" müşterilere ve ABD vatandaşlarına yönelik hizmet veriyor; henüz taşınmamış bir yeni gelenin bu yolla adres şartını atlatabileceğine dair bir bilgi yok.',
                    ],
                ],
                'kaynak_url' => 'https://dutchreview.com/financial/dutch-bank-accounts-without-a-bsn/',
                'kaynak_aciklama' => 'Ana kaynak yine DutchReview\'ın "Dutch Bank Accounts Without a BSN" rehberi (dutchreview.com/financial/dutch-bank-accounts-without-a-bsn/) — bağımsızca yeniden çekildi ve doğrulandı, ayrıca bu makalenin ABN AMRO\'nun International Clients Desk\'ini HİÇ zikretmediği ve "taşınmadan hesap açamazsınız" dediği teyit edildi. Buna ek olarak şu birincil kaynaklara DOĞRUDAN erişilip metin düzeyinde doğrulama yapıldı: bunq\'ın resmi expat sayfası (bunq.com/en-nl/personal-account/banking-use-cases/expats — "provide it within 90 days", "5 minutes", taşınmadan başvuru); ABN AMRO\'nun resmi "Open a bank account" sayfası (abnamro.nl/en/personal/specially-for/expats/welcome-to-the-netherlands/open-bank-account.html — BSN\'siz akışta bile Hollanda adresi şartını doğrudan doğruluyor, WebFetch aracı 503 ile engellendiği için tarayıcı user-agent\'lı curl ile erişildi); ABN AMRO\'nun resmi International Clients Desk sayfası (abnamro.nl/en/personal/specially-for/expats/international-clients-desk.html — masanın hedef kitlesinin "adres şartını atlatmak isteyen yeni gelenler" değil, zaten Hollanda\'da yaşayan expat\'lar/kalıcı non-resident\'lar/ABD vatandaşları olduğunu gösteriyor); DutchReview\'ın ING\'e özel makalesi (dutchreview.com/expat/ing-bank-account-without-bsn/); ve Hollanda hükümetinin resmi mevduat güvence sistemi sayfası (rijksoverheid.nl/onderwerpen/financiele-sector/vraag-en-antwoord/valt-mijn-geld-bij-de-bank-onder-het-depositogarantiestelsel — "€ 100.000 per rekeninghouder, per bank" ifadesiyle birebir doğrulandı). Taslağın orijinal kaynak açıklamasında adı geçen abnamro.nl sayfaları bu doğrulamada gerçekten kullanıldı, ancak bu sayfaların içeriği taslağın ICD iddiasını DESTEKLEMİYOR — düzeltmenin gerekçesi budur.',
            ],
            [
                'konuSlug' => 'online-banka-mi-geleneksel-mi',
                'country_code' => 'FR',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Önce temel gerçek: Fransa\'da herkesin banka hesabı açma hakkı var (droit au compte)',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Fransa\'da yasal olarak ikamet eden herkes -uyruğu veya oturum statüsü ne olursa olsun- \'droit au compte\' adı verilen yasal güvence sayesinde bir banka hesabına erişme hakkına sahiptir. Başvurduğunuz banka reddederse yazılı bir ret yazısı istemelisiniz (başvurudan 15 gün sonra hâlâ cevap gelmemesi de zımni ret sayılır); bu ret belgesiyle Banque de France\'a (Fransa Merkez Bankası) başvurduğunuzda, kurum 24 saat (1 iş günü) içinde sizin adınıza bir banka belirler ve belirlenen banka en geç 3 iş günü içinde temel bir hesap açmak zorundadır. Banque de France\'ın yönlendirme mektubu 6 ay geçerlidir.',
                    ],
                    2 => [
                        'tip' => 'madde',
                        'metin' => 'Başvuru için gereken belgeler: geçerli kimlik belgesi (pasaport, kimlik kartı veya oturma izni/ikamet belgesi), 3 aydan eski olmayan bir adres kanıtı (fatura, kira makbuzu vb.) ve droit au compte başvurusunda ayrıca bankanın verdiği yazılı ret belgesi (veya başvurudan en az 15 gün geçtiğini gösteren kanıt).',
                    ],
                    3 => [
                        'tip' => 'baslik',
                        'metin' => '\'Banque en ligne\' (online banka) ile halk arasında \'néobanque\' denen uygulamalar aynı şey değil',
                    ],
                    4 => [
                        'tip' => 'paragraf',
                        'metin' => 'Boursorama/BoursoBank, Fortuneo, BforBank, Monabanq ve Hello Bank gibi \'banque en ligne\'lar hukuken tam yetkili bir \'établissement de crédit\' (banka) statüsündedir; geleneksel bankalarla aynı denetime tabidir ve çek defteri, kredili mevduat gibi tam hizmet sunarlar. (Orange Bank da yakın zamana kadar bu grupta sayılırdı, ama ACPR 16 Aralık 2025\'te bankacılık yetkisini resmen sona erdirdi; müşterileri 2024\'ten beri kademeli olarak Hello Bank!\'a aktarıldı — artık yeni hesap açılabilecek bir banka değil, bu yüzden bu listeden çıkarılmalı.) Buna karşılık Nickel ile Qonto/Shine gibi birçok uygulama hukuken banka değil, daha hafif bir rejime tabi \'établissement de paiement\' (ödeme kuruluşu)dur; eskiden \'Lydia\' adıyla bilinen uygulama ise 2024\'te \'Sumeria\' markasına geçti ve teknik olarak biraz farklı bir statüde, \'établissement de monnaie électronique\' (elektronik para kuruluşu)dur. Fransız düzenleyicisi ACPR\'ye göre banka olmayan bir kuruluşun kendini \'banka\' diye tanıtması zaten yasaktır. Bu fark önemlidir çünkü gerçek banka statüsündeki kuruluşlarda mevduatınız Fransız mevduat garanti fonu FGDR tarafından kuruluş başına 100.000 €\'ya kadar güvence altındadır (N26-Almanya, Revolut-Litvanya gibi başka bir AB ülkesinde lisanslı dijital bankalarda o ülkenin eşdeğer garantisi geçerlidir); ödeme kuruluşu veya elektronik para kuruluşu statüsündeki uygulamalarda ise bu banka mevduat garantisi yoktur.',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'Yeni gelen biri için pratik özet şu: ödeme/e-para kuruluşu statüsündeki uygulamalar (örn. Nickel, anlaşmalı bir tütüncüde 5 dakikada hesap açabiliyor; 198\'den fazla uyruğa ve gelir şartı aramadan) genelde çok az belgeyle dakikalar içinde hesap açtırdığından ilk günlerde en hızlı seçenektir; ama çek defteri ve kredili mevduat gibi hizmetleri yoktur. Ayrıca bazı dijital sağlayıcılar -ister bu tür bir ödeme/e-para kuruluşu olsun ister N26 (Almanya) veya Revolut (Litvanya) gibi başka bir AB ülkesinde lisanslı bir banka olsun- Fransa dışından bir IBAN verebilir. AB\'nin SEPA düzenlemesi gereği yabancı bir IBAN\'ın her Fransız kurum tarafından kabul edilmesi zorunludur; işveren, CAF veya sigorta şirketinin sadece IBAN \'FR\' ile başlamadığı için ödemeyi reddetmesi yasa dışı bir ayrımcılıktır (şikâyet: signal.conso.gouv.fr) — ama pratikte bazı kurumlar yine de güçlük çıkarabiliyor. Bu yüzden hesap açmadan önce kuruluşun gerçek bir banka mı yoksa ödeme/e-para kuruluşu mu olduğunu ve Fransız (FR) IBAN verip vermediğini sormak, şüpheye düşülürse lisansı ACPR/Banque de France\'ın ücretsiz kaydı regafi.fr üzerinden doğrulamak faydalı olur.',
                    ],
                ],
                'kaynak_url' => 'https://www.inc-conso.fr/content/banque/les-neobanques-ce-quil-faut-savoir',
                'kaynak_aciklama' => 'Ana kaynak: Institut National de la Consommation (INC) — Fransa\'nın resmi tüketici enstitüsü — "néobanque"ların geleneksel/online bankalardan hukuki farkını anlatan rehberi: https://www.inc-conso.fr/content/banque/les-neobanques-ce-quil-faut-savoir . Destekleyici resmi kaynaklar: (1) Service-Public.gouv.fr F2417 — droit au compte\'un asıl prosedür sayfası, süreler dahil (taslakta kaynak gösterilen F2413 sayfası yabancılar için genel hesap açma şartlarını doğru anlatıyor ama süreç sürelerini içermiyor; süreler için asıl kaynak F2417): https://www.service-public.gouv.fr/particuliers/vosdroits/F2417 — ayrıca genel şartlar için F2413: https://www.service-public.gouv.fr/particuliers/vosdroits/F2413 ; (2) FGDR\'nin Eylül 2024 tarihli "Néobanques et Garantie des dépôts" raporu — hangi dijital bankaların gerçek banka lisansına (100.000 € güvenceye) sahip olduğunu isim isim listeliyor; DİKKAT: rapor Eylül 2024 itibarıyla doğruydu ama Orange Bank satırı artık güncel değil (bkz. düzeltme notu): https://www.garantiedesdepots.fr/sites/default/files/2024-09/NeoBanques_Fintech_GarantieFGDR%20update_2024_09.pdf ; (3) La Finance Pour Tous — droit au compte süreç süreleri (24 saat/1 iş günü, 3 iş günü, 6 ay birebir doğrulandı): https://www.lafinancepourtous.com/pratique/banque/le-compte-bancaire/le-droit-au-compte/demande-de-droit-au-compte/ ; (4) INC — yabancı IBAN reddi/ayrımcılık: https://www.inc-conso.fr/content/banque/peut-refuser-un-iban-europeen-lors-de-la-mise-en-place-dun-virement-ou-dun-prelevement ; (5) ACPR/Banque de France\'ın ücretsiz lisans sorgulama kaydı: https://www.regafi.fr ; (6) EK DOĞRULAMA — Orange Bank\'ın bankacılık yetkisinin 16 Aralık 2025\'te ACPR tarafından sona erdirildiğinin ve müşterilerin 2024\'ten beri Hello Bank!\'a aktarıldığının kanıtı: https://fr.wikipedia.org/wiki/Orange_Bank ; (7) EK DOĞRULAMA — Lydia\'nın 2024\'te "Sumeria" markasına geçtiğinin kanıtı (lydia-app.com artık sumeria.eu\'ya yönleniyor): https://sumeria.eu/ ; (8) EK DOĞRULAMA — Nickel\'in hızlı/gelir-şartsız hesap açma iddiası: https://nickel.eu/',
            ],
            [
                'konuSlug' => 'online-banka-mi-geleneksel-mi',
                'country_code' => 'BE',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Kısa cevap: Duruma göre değişir, ikisini birlikte düşünmek en pratiği',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belçika\'ya yeni gelen biri için net bir kazanan yok: online (dijital) bankalar hesabı dakikalar içinde ve uzaktan açtırdığı için henüz Belçika\'da adresiniz yokken bile işe yarar; geleneksel bankalar ise şube desteği ve Bancontact kartıyla günlük hayatta (markette, otomatlarda, park ödeme noktalarında) daha az sürtünme yaratır. Pratik yaklaşım, yola çıkmadan önce dijital bir hesapla başlayıp Belçika\'ya yerleştikten sonra Bancontact\'lı geleneksel bir hesap da açmaktır.',
                    ],
                    2 => [
                        'tip' => 'baslik',
                        'metin' => 'Online/dijital bankalar (N26, Revolut, bunq): Belçika\'ya varmadan hesap açma avantajı',
                    ],
                    3 => [
                        'tip' => 'paragraf',
                        'metin' => 'Dijital bankalar başvuruyu tamamen mobil uygulama üzerinden alır; kimlik pasaport/kimlik kartı taramasıyla doğrulanır ve Belçika\'da henüz kayıtlı bir adresiniz olmasa da başvurabilirsiniz, bu yüzden ülkeye taşınmadan önce hesap hazırlamak isteyenler için avantajlıdır (Expatica\'nın Belçika bankacılık rehberi).',
                    ],
                    4 => [
                        'tip' => 'madde',
                        'metin' => 'Verilen kart Belçika\'ya özgü Bancontact kartı değil, genelde Mastercard\'dır (N26\'nın Belçika için resmî sayfası kartın Mastercard olduğunu doğruluyor); bu yüzden yalnızca Bancontact kabul eden yerlerde işe yaramayabilir.',
                    ],
                    5 => [
                        'tip' => 'madde',
                        'metin' => 'Bancontact, Belçika\'da günlük ödemelerde en yaygın kullanılan yerli sistem kabul ediliyor; bu yüzden yalnızca Bancontact kabul eden küçük işletme, otomat veya park ödeme noktalarında dijital banka kartı yetersiz kalabiliyor.',
                    ],
                    6 => [
                        'tip' => 'baslik',
                        'metin' => 'Geleneksel bankalar (KBC, BNP Paribas Fortis, ING Belgium, Belfius): Günlük hayatta ve şubede kolaylık',
                    ],
                    7 => [
                        'tip' => 'paragraf',
                        'metin' => 'Büyük geleneksel bankaların çoğu artık yurt dışından online ön-başvuru kabul ediyor, ama hesabı fiilen kullanabilmek için Belçika\'ya vardıktan sonra bir kez şubeye gidip imza atmanız gerekiyor; karşılığında Bancontact kartı ve genelde İngilizce dahil çok dilli müşteri hizmeti alıyorsunuz (KBC Brussels\'in resmi expat hesabı sayfası; Expatica).',
                    ],
                    8 => [
                        'tip' => 'madde',
                        'metin' => 'Örnek: KBC Brussels\'in yurt dışından yaşayanlar için tasarladığı Plusrekening (expat hesabı) 2026 itibarıyla aylık 4,25 avro; başvuru için kimlik belgesi ve ikamet izni isteniyor (kbcbrussels.be).',
                    ],
                    9 => [
                        'tip' => 'madde',
                        'metin' => 'Expatica\'nın rehberine göre KBC, BNP Paribas Fortis ve ING gibi bankalarda standart hesap ücretleri aylık yaklaşık 2-7 avro arasında değişiyor.',
                    ],
                    10 => [
                        'tip' => 'baslik',
                        'metin' => 'Normal hesap bir hak değil, ama \'temel banka hizmeti\' öyle',
                    ],
                    11 => [
                        'tip' => 'paragraf',
                        'metin' => 'Belçika\'da normal bir banka hesabı açmak bankanın kararına bağlıdır ve banka başvurunuzu reddedebilir. Ancak Avrupa Birliği\'nde yasal olarak ikamet eden herkes (mülteciler ve sığınmacılar dahil) \'temel banka hizmeti\' (Felemenkçe: basisbankdienst, Fransızca: service bancaire de base) talep etme hakkına sahiptir; banka bunun için gelir kanıtı, kefil ya da sigorta satın almanızı şart koşamaz (Wikifin.be; Febelfin.be).',
                    ],
                    12 => [
                        'tip' => 'madde',
                        'metin' => '2026 itibarıyla temel banka hizmetinin yıllık maliyeti en fazla 20,34 avro ile sınırlı (her yıl enflasyona göre güncelleniyor); elektronik işlemler sınırsız, gişede/şubede yılda 36 işlem ücretsiz (Wikifin.be).',
                    ],
                    13 => [
                        'tip' => 'madde',
                        'metin' => 'Banka haksız yere reddederse önce bankanın kendi şikâyet servisine başvurulabiliyor; sonuç alınamazsa bağımsız arabuluculuk kurumu Ombudsfin\'e (ombudsfin.be) taşınabiliyor. Ombudsfin kendini \'finansal konularda arabuluculuk hizmeti\' olarak tanımlıyor; kararlarının bankalar için yasal olarak bağlayıcı olduğuna dair bir kaynak bulunamadı (Wikifin.be).',
                    ],
                    14 => [
                        'tip' => 'baslik',
                        'metin' => 'Hesap açarken hangi belgeler isteniyor?',
                    ],
                    15 => [
                        'tip' => 'madde',
                        'metin' => 'Kimlik: AB vatandaşları için kimlik kartı; AB dışından gelenler için pasaport artı elektronik ikamet kartı veya immatrikülasyon belgesi (attestation d\'immatriculation) gibi Belçika\'ya özgü geçici belgeler (Vreemdelingenrecht.be).',
                    ],
                    16 => [
                        'tip' => 'madde',
                        'metin' => 'Belçika göçmenlik idaresinin verdiği Bijlage 15, 25, 26 veya 35 gibi ek belgeler de kimlik kanıtı olarak kabul edilebiliyor; belgenin süresi dolmuşsa banka hesabı reddedebiliyor (Vreemdelingenrecht.be).',
                    ],
                    17 => [
                        'tip' => 'madde',
                        'metin' => 'Sığınmacı ve mülteciler temel banka hizmetine başvururken \'carte orange\', sığınma başvurusu kaydı veya geçici ikamet sertifikası gibi alternatif belgeleri kullanabiliyor (Febelfin.be).',
                    ],
                ],
                'kaynak_url' => 'https://www.wikifin.be/fr/budget-payer-emprunter-et-assurer/compte-vue/le-service-bancaire-de-base',
                'kaynak_aciklama' => 'Bu özet, bağımsız doğrulama sırasında tek tek fetch edilerek teyit edilen şu kaynaklardan derlendi: Wikifin.be — Belçika finansal düzenleyicisi FSMA\'ya bağlı resmi tüketici bilgilendirme sitesi, temel banka hizmeti sayfası canlı olarak doğrulandı (20,34 avro tavan/2026, 36 ücretsiz gişe işlemi, gelir-kefil-sigorta yasağı, şikâyet süreci): https://www.wikifin.be/fr/budget-payer-emprunter-et-assurer/compte-vue/le-service-bancaire-de-base; Febelfin — Belçika Bankacılık Federasyonu resmi sayfası, sığınmacı/mülteci uygunluğu ve kabul edilen belgeler doğrulandı: https://febelfin.be/en/services/request-a-basic-banking-service-for-individuals; Myria — Belçika federal göç merkezi, normal hesap/temel banka hizmeti ayrımı ve uygunluk kriterleri doğrulandı: https://www.myria.be/nl/grondrechten/sociale-en-economische-rechten/een-gewone-bankrekening-of-een-basisbankrekening-openen; Vreemdelingenrecht.be — Bijlage 15/25/25quinquies/26/26quinquies/35 dahil kabul edilen kimlik belgeleri doğrulandı: https://www.vreemdelingenrecht.be/sociale-rechten/bankdiensten/een-gewone-bankrekening/wie-kan-een-gewone-bankrekening-openen; KBC Brussels\'in resmi expat hesabı sayfası — Plusrekening\'in 2026 itibarıyla aylık 4,25 avro olduğu ve varıştan sonra şubeye gidilmesi gerektiği doğrulandı: https://www.kbcbrussels.be/particulieren/nl/product/betalen/zichtrekeningen/plusrekening-expat-openen.html; Expatica\'nın iki bankacılık rehberi — KBC/BNP Paribas Fortis/ING için aylık ücretlerin 2-7 avro aralığında olduğu ve Bancontact\'ın en yaygın kart olduğu doğrulandı: https://www.expatica.com/be/finance/banking/banking-in-belgium-100079/ ve https://www.expatica.com/be/finance/banking/opening-a-bank-account-in-belgium-741553/; N26\'nın Belçika sayfası — kartın Mastercard olduğu doğrulandı: https://n26.com/en-be; Ombudsfin — kurumun kendini "arabuluculuk hizmeti" (bemiddeling) olarak tanımladığı doğrulandı, "bağlayıcı karar" iddiasını destekleyen hiçbir metin bulunamadı: https://www.ombudsfin.be. DOĞRULANAMADIĞI İÇİN KAYNAK LİSTESİNDEN ÇIKARILDI: businessam.be (site bu doğrulama sırasında erişilemez durumdaydı, "%73" istatistiği teyit edilemedi); N26\'nın destek sitesindeki (support.n26.com) Bancontact top-up sayfası (URL bulunamadı, iddia doğrulanamadı).',
            ],
            [
                'konuSlug' => 'online-banka-mi-geleneksel-mi',
                'country_code' => 'AT',
                'icerik' => [
                    0 => [
                        'tip' => 'baslik',
                        'metin' => 'Avusturya\'da yeni gelen için: Online banka mı, şubeli banka mı?',
                    ],
                    1 => [
                        'tip' => 'paragraf',
                        'metin' => 'Avusturya\'da hesap açarken iki temel yol vardır: Erste Bank ve Raiffeisen gibi şubesi olan geleneksel bankalar ile N26 gibi tamamen dijital online bankalar; hangisinin daha kolay olacağı büyük ölçüde henüz bir ikametgah kaydınızın (Meldezettel) olup olmamasına bağlıdır.',
                    ],
                    2 => [
                        'tip' => 'paragraf',
                        'metin' => 'Şubeden hesap açarken geleneksel bankalar kimlik belgesiyle birlikte genellikle güncel bir Meldezettel ister; bu yüzden ülkeye yeni gelip henüz ikamet kaydını tamamlamamış biri için şube süreci daha yavaş ilerleyebilir. Buna karşın Erste Bank/Sparkasse gibi bazı geleneksel bankalar artık kendi George uygulaması üzerinden \'Konto online eröffnen\' seçeneğiyle, şubeye hiç gitmeden ve kimlik doğrulamasını akıllı telefon üzerinden tamamlayarak dijital hesap açılışı da sunuyor; kimlik doğrulamasının tam olarak hangi teknikle (video görüşme, otomatik belge/yüz taraması vb.) yapıldığı değişebileceğinden başvurmadan önce güncel süreci bankanın kendi sayfasından teyit etmekte fayda var.',
                    ],
                    3 => [
                        'tip' => 'paragraf',
                        'metin' => 'N26 gibi tamamen dijital online bankalarda hesap açma işlemi telefondan kimlik doğrulamasıyla dakikalar içinde tamamlanır, şube ziyareti gerekmez ve fiziksel kart gelene kadar Apple Pay veya Google Pay üzerinden hemen kullanılabilir; bu da henüz yerleşmemiş kişiler için pratik bir başlangıç sunar.',
                    ],
                    4 => [
                        'tip' => 'paragraf',
                        'metin' => 'Avusturya\'da yasal olarak ikamet eden herkes, çek hesabı sunan bir Avusturya bankasından \'Basiskonto\' adı verilen temel bir ödeme hesabı isteme hakkına sahiptir ve banka -başvuran zaten işlevsel bir hesaba sahip değilse- bu talebi reddedemez; yıllık hesap ücreti standart kullanıcılar için en fazla 83,45 avro, ekonomik açıdan dezavantajlı kişiler için ise en fazla 41,73 avro ile sınırlıdır.',
                    ],
                    5 => [
                        'tip' => 'paragraf',
                        'metin' => 'Özetle: Henüz Meldezettel\'iniz yoksa veya hızlıca maaş ya da kira ödemesi yapacak bir hesaba ihtiyacınız varsa online banka daha hızlı bir çözüm olabilir; yerleştikten ve nakit yatırma ya da kredi gibi şube hizmetlerine ihtiyaç duyduğunuzda ise geleneksel bir bankaya geçmek veya ek hesap açmak faydalı olur.',
                    ],
                ],
                'kaynak_url' => 'https://www.fma.gv.at/konto/basiskonto/',
                'kaynak_aciklama' => 'Avusturya Finansal Piyasalar Otoritesi (FMA) resmi Basiskonto sayfası — herkesin banka hesabı açma hakkını, uygunluk şartlarını ve yasal ücret tavanlarını (83,45 € / 41,73 €) düzenliyor. Bu oturumda FMA sayfasına doğrudan erişim bot-engeli (HTTP 403) nedeniyle mümkün olmadı; rakamlar bunun yerine konsumentenfragen.at (Avusturya resmi tüketici bilgilendirme portalı, https://www.konsumentenfragen.at/konsumentenfragen/FAQ/FAQ_1/Basiskonto.html) üzerinden bağımsız olarak birebir doğrulandı. Online/geleneksel banka karşılaştırması için N26\'nın resmi blogu (n26.com/en-eu/blog/how-to-open-a-bank-account-in-austria) ve Erste Bank/Sparkasse\'nin resmi sitesi (sparkasse.at/erstebank) fetch edilerek teyit edildi; ancak George uygulamasının kimlik doğrulama tekniğine dair "NFC çip + selfie" detayı hiçbir kaynakta doğrulanamadığından metinden çıkarıldı.',
            ],
        ];

        foreach ($icerikler as $oge) {
            $konu = $konuHaritasi->get($oge['konuSlug']);
            if (! $konu) {
                continue;
            }

            YasamKonuIcerigi::query()->firstOrCreate(
                ['yasam_konusu_id' => $konu->id, 'country_code' => $oge['country_code']],
                [
                    'icerik' => $oge['icerik'],
                    'kaynak_url' => $oge['kaynak_url'],
                    'kaynak_aciklama' => $oge['kaynak_aciklama'],
                    'status' => YasamKonuIcerigi::STATUS_TASLAK,
                    'yazan_tur' => YasamKonuIcerigi::YAZAN_AI,
                ],
            );
        }
    }
}
