<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * ABD'nin 7 temsilciliğine (Washington Büyükelçiliği + 6 başkonsolosluk)
 * "yerel ek" işler — Almanya'daki RehberAlmanyaYerelEkSeeder ile AYNI
 * mimari, 7 paralel araştırma ajanıyla (2026-08-26) toplandı.
 *
 *     php artisan db:seed --class=RehberAbdYerelEkSeeder --force
 *
 * BERLİN'DEN FARKLI: Washington Büyükelçiliği bu hizmetleri GERÇEKTEN
 * kendisi sunuyor (konsolosluk.gov.tr'de "Vaşington Büyükelçiliği" ayrı,
 * bağımsız bir temsilcilik girişi — Berlin'in aksine, orada Büyükelçilik
 * hiç listelenmiyordu). Görev bölgesi: DC/Virginia/West Virginia/Maryland.
 * Bu yüzden Washington'a yönlendirme uyarısı EKLENMEDİ, diğer 6 şehir
 * gibi normal muamele gördü.
 *
 * SAHİBİN AÇIK KARARIYLA (2026-08-26): bu tur DOĞRUDAN YAYINA ALINIYOR
 * (status=yayin), Almanya turunun "önce taslak, sonra panelden onay"
 * sırasından FARKLI olarak. Araştırma kalitesi yüksek (çoğu bulgu gerçek
 * PDF/DOCX belgelerden, güncel USD tutarlarıyla okundu) ve sahip "küçük
 * hatalı bilgiler olsa da zamanla düzeltiriz" diyerek yayına alma hızını
 * doğruluk mükemmelliğine tercih etti — mevcut geri bildirim mekanizması
 * (RehberGeriBildirimi, her sayfada "Bu bilgi güncel mi?" formu) bu
 * felsefeyi zaten destekliyor.
 */
class RehberAbdYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'US')->get()->keyBy('slug');
        $turler = IslemTuru::query()->get()->keyBy('slug');

        $guncellenen = 0;
        foreach ($this->yerelEkler() as [$temsilcilikSlug, $turSlug, $kaynakUrl, $notEki]) {
            $temsilcilik = $temsilcilikler->get($temsilcilikSlug);
            $tur = $turler->get($turSlug);
            if ($temsilcilik === null || $tur === null) {
                $this->command?->warn("Atlandı: {$temsilcilikSlug} / {$turSlug} bulunamadı.");

                continue;
            }

            $kayit = TemsilcilikIslemi::query()
                ->where('temsilcilik_id', $temsilcilik->id)
                ->where('islem_turu_id', $tur->id)
                ->first();

            if ($kayit === null) {
                $this->command?->warn("Atlandı: {$temsilcilikSlug} / {$turSlug} kaydı yok.");

                continue;
            }

            if ($kaynakUrl !== null) {
                $kayit->resmi_kaynak_url = $kaynakUrl;
            }
            if ($notEki !== null && ! str_contains((string) $kayit->notlar, mb_substr($notEki, 0, 40))) {
                $kayit->notlar = trim((string) $kayit->notlar).' '.$notEki;
            }
            $kayit->status = TemsilcilikIslemi::STATUS_YAYIN;
            $kayit->dogrulanma_tarihi = now();
            $kayit->save();
            $guncellenen++;
        }

        $this->command?->info("ABD yerel ek + yayın: {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            // ============ New York Başkonsolosluğu (görev bölgesi: NY/NJ/CT/PA kısmen) ============
            ['new-york', 'adres-kaydi', 'https://newyork-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/cac8a2bd-5727-4c86-bea7-49a999eedbd7.pdf', 'Bu temsilcilikte (New York) randevu belirtilmemiş, POSTA ile başvuru açıkça kabul ediliyor. Süresinde (20 iş günü) yapılırsa ücretsiz, geç bildirimde $19.'],
            ['new-york', 'ehliyet', 'https://newyork-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/6199a55a-2e94-4a4f-9d45-869b11636727.pdf', 'Bu temsilcilikte (New York) randevu ZORUNLU, yalnız ŞAHSEN başvuru (posta yok, yalnız bitmiş belgenin teslimi postayla olabilir). Ücret: yeni tip $39,50+$5 posta, eski tip A/F/H $51,97, B $156,71. New York/New Jersey\'de Türk ehliyetinin turist olarak kullanım süresi farklı (NY 30 gün, NJ hiç) — bkz. temsilciliğin ayrı bilgi notu.'],
            ['new-york', 'noter-tasdik', 'https://newyork-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/af9e17a5-7db0-4457-80b1-e25cb603f0b2.pdf', 'Bu temsilcilikte (New York) İmza Sirküleri/Beyannamesi yalnız ŞAHSEN+randevu ($24,67); Suret Tasdiki ise şahsen VEYA POSTA ile de yapılabiliyor ($8,62/sayfa).'],
            ['new-york', 'tercume-tasdiki', 'https://newyork-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/60546743-2d14-4479-bb3c-a165afa03ff9.pdf', 'KRİTİK: Bu temsilcilik (New York) YALNIZ 3 belge türünü tercüme ediyor — nüfus kayıt örneği, sürücü belgesi, uluslararası aile cüzdanı. Genel bir "her belgeyi tercüme et" hizmeti YOK. Randevu+şahsen, $11,46.'],
            ['new-york', 'vatandaslik', 'https://newyork-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/1eb61e2e-6f13-4fd2-ab04-68ae0cbeebe2.pdf', 'Bu temsilcilikte (New York) üç alt süreç de randevu+şahsen, yalnız NAKİT ödeme. Evlilik yoluyla kazanmada ek olarak ÇİFT MÜLAKATA çağrılıyor ve son 3 yıl için FBI\'dan "Identity History Summary" (apostilli Türkçe tercümeli) isteniyor — bu ABD\'ye özgü, diğer ülkelerde yok.'],
            ['new-york', 'bosanma-tescili', 'https://newyork-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/e9d47672-53e9-4eb6-a7d1-4b732c5b9357.pdf', 'Bu temsilcilik (New York) YALNIZ New York, New Jersey, Delaware, Pennsylvania eyalet mahkemelerinin kararlarını tescil eder — başka eyaletten ise yanlış temsilcilik. Şahsen başvuru.'],
            ['new-york', 'adli-sicil', 'https://newyork-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/44acd75a-8592-4019-afd9-4eaba1fe186e.pdf', 'Bu temsilcilikte (New York) randevu+şahsen, ÜCRETSİZ, yalnız Türkçe ve İngilizce dillerinde düzenleniyor (Almanya\'daki 44 dil seçeneği burada yok).'],
            ['new-york', 'evlilik-tescili', 'https://newyork-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/db6be62d-3d74-42c3-b65d-394bdc48faef.pdf', 'Bu temsilcilikte (New York) randevu+şahsen VEYA POSTA kabul ediliyor (noter onaylı fotokopi yeterli). YALNIZ NY/NJ/PA/DE eyaletlerindeki evlilikler için geçerli. Ücret $31.'],

            // ============ Los Angeles Başkonsolosluğu (görev bölgesi: AK/AZ/CA/CO/ID/HI/MT/NV/OR/UT/WA/WY) ============
            ['los-angeles', 'adres-kaydi', 'https://losangeles-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/90b2bd87-a8f6-49df-9590-7b23ac063a39.pdf', 'Bu temsilcilikte (Los Angeles) randevu+şahsen VEYA POSTA kabul ediliyor. Süresinde yapılırsa ücretsiz, geç bildirimde $19, gerçeğe aykırı beyanda $396,10.'],
            ['los-angeles', 'ehliyet', 'https://losangeles-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/59646339-42f4-4dd0-83b5-2b5069efd5a2.pdf', 'Bu temsilcilikte (Los Angeles) başvurunun kendisi POSTAYLA YAPILAMAZ (yalnız bitmiş belgenin teslimi postayla olabilir) — randevu+şahsen zorunlu. Ücretler New York ile aynı ($39,50/$51,97/$156,71 + $5 posta).'],
            ['los-angeles', 'noter-tasdik', 'https://losangeles-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/9d914ffb-3821-4eed-9d72-8e9d15763a06.pdf', 'Bu temsilcilikte (Los Angeles) Suret Tasdiki şahsen VEYA POSTA ile yapılabiliyor (postada kimliğin ASLI gönderilmeli), $11,46/sayfa. İmza sirküleri (şirket temsili için) $60-400 arası değişken.'],
            ['los-angeles', 'vatandaslik', 'https://losangeles-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/cf4f76d7-31e0-43af-b65b-b9312140b1ab.pdf', 'KRİTİK — Bu temsilcilikte (Los Angeles) üç alt süreç için DOĞRUDAN RANDEVU ALINAMAZ: önce tüm evrak POSTAYLA gönderilir, inceleme tamamlandıktan SONRA randevu verilir. Kendi 8 kişilik yeminli tercüman listesi dışından çeviri kabul edilmiyor. Ücretler: çıkma $51,08, yeniden kazanma/evlilik $76,08.'],
            ['los-angeles', 'bosanma-tescili', 'https://losangeles-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/3e9cee6f-0b0b-436f-be6d-20ab6e180c0e.pdf', 'Bu temsilcilik (Los Angeles) yalnız AK/AZ/CA/CO/ID/MT/NV/OR/UT/WA/WY/HI eyalet mahkemesi kararlarını tescil eder. Posta seçeneği yok, şahsen/vekil. Ücret $51,08.'],
            ['los-angeles', 'evlilik-tescili', 'https://losangeles-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/2c17a9fd-812d-47ca-a1dd-38f3c6373ac0.pdf', 'Bu temsilcilikte (Los Angeles) randevu+şahsen VEYA POSTA kabul. "Marriage Certificate" gerekli, "Marriage Record" kabul edilmiyor. Ücret $51,08.'],

            // ============ Chicago (Şikago) Başkonsolosluğu (görev bölgesi: IL/IN/IA/KS/KY/MI/MN/MO/NE/ND/SD/OH/WI) ============
            ['chicago', 'adres-kaydi', 'https://sikago-bk.mfa.gov.tr/Mission/ShowAnnouncement/401812', 'Bu temsilcilikte (Chicago) randevu ŞART DEĞİL — şahsen VEYA POSTA. Geç bildirimde $19.'],
            ['chicago', 'noter-tasdik', 'https://sikago-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/f73ba656-6811-4ddc-8e89-5aa00b64fc86.pdf', 'KRİTİK — Bu temsilcilik (Chicago) ABD noterinde onaylanmış belgeler (vekaletname vb.) için imza/mühür tasdiki YAPMIYOR — belge Notary Public → County Clerk → Apostille (Secretary of State) zinciriyle DOĞRUDAN Türkiye\'ye gönderilebiliyor, konsolosluğa hiç gerek yok. Vekaletname düzenleme hizmeti randevu+şahsen, $27,57 (1 sayfa) / $40,94 (düzenleme) + $14,20/ek sayfa.'],
            ['chicago', 'vatandaslik', 'https://sikago-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/632e9c88-a4ef-45f6-aa5b-69e3efc17f64.docx', 'Bu temsilcilikte (Chicago) "Çok Vatandaşlık Başvurusu" randevu ŞART DEĞİL — şahsen VEYA POSTA (postada fotokopiler noter onaylı olmalı). Ücret: $25,68/yabancı belge + $31 posta = $56,68 (isim değişikliği de varsa $82,36).'],
            ['chicago', 'bosanma-tescili', 'https://sikago-bk.mfa.gov.tr/Mission/ShowInfoNote/407614', 'Bu temsilcilikte (Chicago) randevu ZORUNLU, şahsen/vekil. Ücret $72. Eksiksiz başvurudan 1 AY içinde tescil (bulunan en hızlı süre). Kendi yeminli tercüman listesi yayında.'],

            // ============ Houston Başkonsolosluğu (görev bölgesi: TX/AL/AR/LA/MS/NM/OK/TN) ============
            ['houston', 'adres-kaydi', 'https://houston-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/467d4d52-03ce-4076-be16-8bf0e308540a.pdf', 'Bu temsilcilikte (Houston) randevu şart değil — şahsen, POSTA (yalnız iadeli taahhütlü/kargo) VEYA e-Devlet e-imza. Geç bildirimde $16.'],
            ['houston', 'ehliyet', 'https://houston-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/2d902e86-1497-47ec-b903-49d62e48d332.pdf', 'Bu temsilcilikte (Houston) "Posta ile yapılan başvurular kabul edilmemektedir" — yalnız randevu+şahsen. Ücretler: A/F/H $51,97, B $156,71, diğer $261,49 + $5 posta.'],
            ['houston', 'noter-tasdik', null, 'Bu temsilcilikte (Houston) İmza-Mühür Tasdiki YALNIZ belgenin düzenlendiği/Houston\'ın görev bölgesindeki temsilciliğe yapılabilir — başka bölgeden ise kabul edilmez. Randevu+şahsen, posta yok.'],
            ['houston', 'tercume-tasdiki', null, 'KRİTİK — Bu temsilcilikte (Houston) tasdik YALNIZ tercümeyi yapan tercümanın Houston\'da yeminli olması hâlinde yapılabilir. Houston\'a kayıtlı yeminli tercümanlar arasında Dallas/Nashville/Austin\'de çalışanlar da var (aynı görev bölgesi).'],
            ['houston', 'vatandaslik', null, 'Bu temsilcilikte (Houston) posta kabul edilmiyor, yalnız ikamet edilen bölgeden sorumlu temsilciliğe başvurulabilir. Yeniden kazanma: $10 + $31 posta. İzinle çıkma belge teslimi: $10, şahsen alınmalı.'],
            ['houston', 'bosanma-tescili', 'https://houston-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/81b010ba-1bd3-4a7c-989b-5136133722e7.pdf', 'Bu temsilcilik (Houston) yalnız Alabama/Arkansas/Louisiana/Mississippi/New Mexico/Oklahoma/Teksas/Tennessee eyalet mahkemesi kararlarını tescil eder. Randevu+şahsen, posta yok. Harç yok, $31 posta bedeli (evrak gönderimi için).'],
            ['houston', 'adli-sicil', null, 'Bu temsilcilikte (Houston) randevu+şahsen (veya vekil), toplam $10. Belge postayla istenirse UPS/Ekspres $80 ek ücret.'],
            ['houston', 'evlilik-tescili', 'https://houston-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/27057c41-eec6-4356-ad68-c6c762ae148e.pdf', 'Bu temsilcilikte (Houston) randevu+şahsen VEYA POSTA (noter onaylı fotokopi yeterli). Yalnız görev bölgesi eyaletlerindeki (AL/AR/LA/MS/NM/OK/TX/TN) evlilikler. "Marriage Certificate" gerekli. Ücret $31.'],

            // ============ Boston Başkonsolosluğu (görev bölgesi: MA/NH/CT/ME/VT/RI) ============
            ['boston', 'adres-kaydi', 'https://boston-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/c873a4ef-e23c-4168-82e9-e6240db8bbca.pdf', 'Bu temsilcilikte (Boston) randevu+şahsen VEYA POSTA kabul. Süresinde ücretsiz, geç bildirimde ~$19, gerçeğe aykırı beyanda $319.'],
            ['boston', 'ehliyet', 'https://boston-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/c1bf2118-a77c-423c-b007-339db4b62dff.pdf', 'Bu temsilcilikte (Boston) YALNIZ ŞAHSEN+randevu (posta yok). Ücretler: $5 posta + $39,30 değerli kâğıt + sınıf harcı (A/C $52, B $157, E $261,50).'],
            ['boston', 'noter-tasdik', 'https://boston-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/3a00fddc-26ad-4479-9ad6-8aff9d6b830a.pdf', 'Bu temsilcilikte (Boston) Suret Tasdiki şahsen VEYA POSTA ($8,69/sayfa); İmza Sirküleri/Beyannamesi YALNIZ ŞAHSEN ($24,70).'],
            ['boston', 'tercume-tasdiki', 'https://boston-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/eb5b9142-6cf0-430f-b878-e63f07406782.pdf', 'KRİTİK — Bu temsilcilik (Boston) genel bir tercüme hizmeti sunmuyor, YALNIZ "Sürücü Belgesi Tercümesi" var (ABD makamlarına sunmak için). Şahsen veya posta, $11,46.'],
            ['boston', 'vatandaslik', 'https://boston-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/4897118e-93e6-49a9-9c8d-de239059773a.pdf', 'Bu temsilcilikte (Boston) üç alt süreç de YALNIZ ŞAHSEN, nakit. Ücretler: çıkma $90, yeniden kazanma $110, evlilik yoluyla $120. New England eyaletlerinin apostil ofis adresleri kendi PDF\'inde listelenmiş.'],
            ['boston', 'bosanma-tescili', 'https://boston-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/516a9e85-c572-4919-a426-e9e8d9cd59eb.pdf', 'Bu temsilcilik (Boston) yalnız MA/NH/CT/ME/VT/RI eyalet mahkemesi kararlarını tescil eder. Yalnız şahsen. Ücret $90 (2026, önceki yıl $80\'di — güncellendi).'],
            ['boston', 'adli-sicil', 'https://boston-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/2ebfd2a3-8319-4722-afb8-1be4027972cb.pdf', 'Bu temsilcilikte (Boston) yalnız şahsen, ÜCRETSİZ.'],
            ['boston', 'evlilik-tescili', 'https://boston-bk.mfa.gov.tr/Content/assets/consulate/images/localCache/1/0c143f11-1ac9-456e-8914-315585ce91cc.pdf', 'Bu temsilcilikte (Boston) şahsen VEYA POSTA. Yalnız MA/NH/CT/ME/VT/RI evlilikleri. "Marriage Certificate" gerekli, "Marriage Record" olmaz. Ücret $35.'],

            // ============ Miami Başkonsolosluğu (görev bölgesi: FL/GA/NC/SC/Porto Riko/USVI) ============
            ['miami', 'adres-kaydi', 'https://miami-bk.mfa.gov.tr/Mission/ShowInfoNote/241560', 'Bu temsilcilikte (Miami) randevu+şahsen VEYA POSTA kabul. Süresinde ücretsiz, geç bildirimde $19.'],
            ['miami', 'ehliyet', 'https://miami-bk.mfa.gov.tr/Mission/ShowInfoNote/241560', 'Bu temsilcilikte (Miami) RANDEVU KESİN ŞART, posta KABUL EDİLMİYOR. Ücretler: A/F/H $56,97, B $161,71, diğer $266,49.'],
            ['miami', 'noter-tasdik', 'https://miami-bk.mfa.gov.tr/Mission/ShowInfoNote/254178', 'Bu temsilcilikte (Miami) hem Beyan Tasdiki ($17,03) hem Suret Tasdiki ($8,62/sayfa) hem de posta ile belge onayı ($18,80) mümkün — postada ABD Notary Public + County Clerk (ya da doğrudan Apostil) zinciri gerekiyor.'],
            ['miami', 'tercume-tasdiki', 'https://miami-bk.mfa.gov.tr/Mission/ShowInfoNote/254178', 'KRİTİK — Bu temsilcilik (Miami) YALNIZ 3 belge türünü tercüme ediyor: nüfus kayıt örneği (şahsen ZORUNLU, $11,46/$22,92), sürücü belgesi ($11,46) ve aile cüzdanı ($18,92) — ikisi şahsen VEYA posta.'],
            ['miami', 'vatandaslik', 'https://miami-bk.mfa.gov.tr/Mission/ShowInfoNote/241562', 'Bu temsilcilikte (Miami) üç alt süreç de randevu+şahsen, nakit. Evlilik yoluyla kazanmada eşler mülakata çağrılıyor. Ücretler ayrı "Harç ve Ceza Bedelleri Listesi"nde.'],
            ['miami', 'bosanma-tescili', 'https://miami-bk.mfa.gov.tr/Mission/ShowInfoNote/241560', 'Bu temsilcilik (Miami) yalnız FL/GA/NC/SC/Porto Riko eyalet/bölge mahkemesi kararlarını tescil eder. Şahsen (veya vekil). Ücret $31.'],
            ['miami', 'adli-sicil', 'https://miami-bk.mfa.gov.tr/Mission/ShowInfoNote/254178', 'Bu temsilcilikte (Miami) yalnız şahsen, ÜCRETSİZ. e-Devlet üzerinden de alınabilir.'],
            ['miami', 'evlilik-tescili', 'https://miami-bk.mfa.gov.tr/Mission/ShowInfoNote/241560', 'Bu temsilcilikte (Miami) randevu+şahsen VEYA POSTA. Yalnız FL/GA/NC/SC/Porto Riko/USVI evlilikleri. Ücret $31.'],

            // ============ Washington Büyükelçiliği (görev bölgesi: DC/Virginia/West Virginia/Maryland) ============
            // Berlin'in AKSİNE: bu hizmetleri gerçekten kendisi sunuyor, yönlendirme uyarısı YOK.
            ['washington-buyukelciligi', 'adres-kaydi', 'https://vasington-be.mfa.gov.tr/Content/assets/consulate/images/localCache/1/5e8408f2-2f6e-4b97-98a5-d063b7b2205e.pdf', 'Bu temsilcilik (Washington Büyükelçiliği) DC/Virginia/West Virginia/Maryland\'e hizmet verir. Şahsen VEYA POSTA kabul ediliyor.'],
            ['washington-buyukelciligi', 'ehliyet', 'https://vasington-be.mfa.gov.tr/Content/assets/consulate/images/localCache/1/ed00f43e-2ac4-4552-b85a-73d106a26754.pdf', 'Bu temsilcilikte (Washington Büyükelçiliği) randevu+şahsen zorunlu.'],
            ['washington-buyukelciligi', 'vatandaslik', 'https://vasington-be.mfa.gov.tr/Content/assets/consulate/images/localCache/1/ca0dea7a-1088-4ac9-8f1f-acb61bf874d2.pdf', 'Bu temsilcilikte (Washington Büyükelçiliği) DC/Virginia/West Virginia/Maryland\'de ikamet edenler başvurabilir.'],
            ['washington-buyukelciligi', 'bosanma-tescili', 'https://vasington-be.mfa.gov.tr/Content/assets/consulate/images/localCache/1/805d52cc-4631-4202-9d8d-cf6028e04f92.pdf', 'Bu temsilcilik (Washington Büyükelçiliği) yalnız DC/Virginia/West Virginia/Maryland eyalet mahkemesi kararlarını tescil eder. Randevu+şahsen.'],
            ['washington-buyukelciligi', 'evlilik-tescili', 'https://vasington-be.mfa.gov.tr/Content/assets/consulate/images/localCache/1/62d01225-7b14-48ba-b678-f53904e8b2db.pdf', 'Bu temsilcilikte (Washington Büyükelçiliği) RANDEVU GEREKMEZ — postayla başvurulabiliyor. 8 kategori arasında bu özelliğe sahip tek işlem.'],
        ];
    }
}
