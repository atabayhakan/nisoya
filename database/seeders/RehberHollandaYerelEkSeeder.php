<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Hollanda'nın Amsterdam/Rotterdam/Deventer Başkonsoloslukları'na "yerel ek"
 * işler + Lahey Büyükelçiliği'nin YANLIŞ MUHATAP olduğunu düzeltir —
 * Almanya/ABD ile AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 * ÖNCE RehberHollandaSeeder çalıştırılmış olmalı (3 yeni temsilciliği kurar).
 *
 *     php artisan db:seed --class=RehberHollandaYerelEkSeeder --force
 *
 * BERLİN İLE BİREBİR AYNI DESEN: Lahey Büyükelçiliği'nin kendi "Hakkında"
 * sayfası açıkça "Büyükelçilik doğrudan konsolosluk hizmeti sunmaz" diyor.
 * Görev bölgesi paylaşımı (temsilciliklerin kendi sitelerinden doğrulandı):
 * - Amsterdam: Kuzey Hollanda, Flevoland, Utrecht
 * - Rotterdam: Güney Hollanda, Zeeland, Kuzey Brabant
 * - Deventer: Groningen, Friesland, Drenthe, Overijssel, Gelderland, Limburg
 *
 * VERİ KALİTESİ: Rotterdam ve Deventer 8/8 kategori tam bulundu. Amsterdam
 * 6/8 tam + 2 kısmi (Suret Tasdiki bulunamadı, Tercüme Tasdiki'nin varlığı
 * kesin ama detay teyit edilemedi — bir web aramasında dolaşan ücret rakamı
 * gerçekte Bordeaux'a aitti, Amsterdam'a mal edilmedi). Hiçbir şehirde nakit
 * kabul edilmiyor, tüm ücretler vezne.konsolosluk.gov.tr üzerinden online.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberHollandaYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'NL')->get()->keyBy('slug');
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
            $kayit->dogrulanma_tarihi = now();
            $kayit->save();
            $guncellenen++;
        }

        $this->command?->info("Hollanda yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        $laheyYonlendirme = 'ÖNEMLİ: Lahey Büyükelçiliği bu hizmeti SUNMUYOR — kendi resmî sayfasında "Büyükelçilik doğrudan konsolosluk hizmeti sunmaz" diyor. Doğru muhatap ikamet bölgesine göre Amsterdam, Rotterdam veya Deventer Başkonsolosluğu\'dur.';

        return [
            // ============ Lahey Büyükelçiliği — yönlendirme notu (Berlin deseni) ============
            ['lahey', 'adres-kaydi', null, $laheyYonlendirme],
            ['lahey', 'ehliyet', null, $laheyYonlendirme],
            ['lahey', 'noter-tasdik', null, $laheyYonlendirme],
            ['lahey', 'tercume-tasdiki', null, $laheyYonlendirme],
            ['lahey', 'vatandaslik', null, $laheyYonlendirme],
            ['lahey', 'bosanma-tescili', null, $laheyYonlendirme],
            ['lahey', 'adli-sicil', null, $laheyYonlendirme],
            ['lahey', 'evlilik-tescili', null, $laheyYonlendirme],

            // ============ Amsterdam Başkonsolosluğu (Kuzey Hollanda/Flevoland/Utrecht) ============
            ['amsterdam-bk', 'adres-kaydi', 'https://amsterdam-bk.mfa.gov.tr/Mission/ShowAnnouncement/400777', 'Randevu GEREKMİYOR — hafta içi 09:00-12:00/14:00-16:30 randevusuz, şahsen. Posta kabul edilmiyor. Ücretsiz, ama 20 iş günü geçince idari para cezası var (tutar belirtilmemiş).'],
            ['amsterdam-bk', 'ehliyet', 'https://amsterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/396142', 'Randevu zorunlu, posta yok. Ücret: 31,40 EUR yenileme + 3,00 EUR posta = 34,40 EUR, yalnız online ödeme. Yalnız Noord-Holland/Flevoland/Utrecht ikametçilerine bakılıyor; G sınıfı hariç tüm sınıflar yenilenebiliyor.'],
            ['amsterdam-bk', 'noter-tasdik', 'https://amsterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/355548', 'İmza-Mühür Tasdiki: randevu zorunlu, 26,76 EUR/sayfa, yalnız online ödeme. Vekaletname/Azilname de aynı sayfada, ücret belge uzunluğuna göre değişken. "Suret Tasdiki" (aslı gibidir onayı) için Amsterdam\'a özel sayfa bulunamadı.'],
            ['amsterdam-bk', 'tercume-tasdiki', 'https://amsterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/353630', 'Hizmetin var olduğu kesin ama randevu/posta/ücret detayı Amsterdam\'a özel doğrulanamadı. ⚠️ İnternette dolaşan "17,84 EUR/sayfa" rakamı gerçekte Bordeaux Başkonsolosluğu\'na ait — Amsterdam\'a mal edilmedi.'],
            ['amsterdam-bk', 'vatandaslik', 'https://amsterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/355549', 'Üçü de randevu zorunlu. Yeniden kazanma: 403 TL\'nin EUR karşılığı + 8 EUR posta. Evlilik yoluyla: 762,82 TL\'nin EUR karşılığı + 8 EUR posta. İzinle çıkmada erkekler için askerlik durumunun netleşmiş olması gerekiyor.'],
            ['amsterdam-bk', 'bosanma-tescili', 'https://amsterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/353629', 'Randevu zorunlu. Başvuru sahibinin Noord-Holland/Utrecht/Flevoland\'da ikamet etmesi şart; karar Apostilli olmalı + 2 nüsha yeminli tercüme. Taraflar birlikte veya en fazla 90 gün arayla ayrı başvurabiliyor. Mavi Kart sahiplerinin boşanma tescili "yabancı statüsünde" kabul edilmiyor.'],
            ['amsterdam-bk', 'adli-sicil', 'https://amsterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/355551', 'Randevu zorunlu, POSTA AÇIKÇA KABUL EDİLMİYOR, ücretsiz. Hollanda Apostil Sözleşmesi\'ne taraf olduğundan, e-Devlet ile alınan belgeye Türkiye\'de Apostil aldırma alternatifi de var.'],
            ['amsterdam-bk', 'evlilik-tescili', 'https://amsterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/356325', 'Randevu zorunlu, POSTA AÇIKÇA KABUL EDİLMİYOR. Formül B (Uluslararası Evlilik Cüzdanı, son 6 ay içinde alınmış) + yabancı eş için Formül A gerekiyor; yabancı eş pasaport yerine Hollanda kimlik kartıyla da başvurabiliyor.'],

            // ============ Rotterdam Başkonsolosluğu (Güney Hollanda/Zeeland/Kuzey Brabant) ============
            ['rotterdam-bk', 'adres-kaydi', 'https://rotterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/417052', 'POSTA İLE BAŞVURU KABUL EDİLMİYOR ("posta ile gönderilen formlar işleme alınmayacaktır"), yalnız şahsen. TEK İSTİSNA: adres.nvi.gov.tr üzerinden mobil/e-imza ile tamamen ONLİNE beyan mümkün — konsolosluğa hiç gitmeden.'],
            ['rotterdam-bk', 'ehliyet', 'https://rotterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/390119', 'Randevu zorunlu, şahsen; yalnız TESLİMAT posta ile mümkün (+3,00 EUR). Ücretler (2022, güncelliği teyide açık): eski tip 2,00 EUR, yeni tip 18,60 EUR. KAYIP/YIPRANMA nedeniyle yenileme KABUL EDİLMİYOR — bu durumda Türkiye\'deki trafik tescil şubesine (surucurandevu.egm.gov.tr) gidilmeli.'],
            ['rotterdam-bk', 'noter-tasdik', 'https://rotterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/391014', 'Her başvuru sahibi için AYRI randevu gerekiyor; randevu saati diğer işlemlerden farklı ve dar: 09:00-12:00/14:00-15:00. Posta KABUL EDİLMİYOR. Ücret 16,85 EUR (2022 tarihli, teyide açık).'],
            ['rotterdam-bk', 'tercume-tasdiki', 'https://rotterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/417050', 'Yalnız başkonsolosluğun ONAYLADIĞI yeminli tercüman listesindeki kişilerin tercümeleri kabul ediliyor, listede olmayanlar KESİNLİKLE reddediliyor. Aynı ücret/posta kuralı noter tasdikiyle aynı (16,85 EUR, posta yok). Liste düzenli güncelleniyor (en son 25.03.2026).'],
            ['rotterdam-bk', 'vatandaslik', 'https://rotterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/390115', 'Randevu zorunlu. İzinle çıkmada Hollanda vatandaşlığının hangi tarihte kazanıldığını gösteren belediye kaydı + (varsa) IND\'nin 3 aylık bekleme sürecini teyit eden belge + "bekendmaking" belgesi gerekiyor — Hollanda\'ya özgü bir prosedür.'],
            ['rotterdam-bk', 'bosanma-tescili', 'https://rotterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/417062', 'Randevu zorunlu. Apostilli boşanma kararı aslı + Türkçe tercüme + kesinleşme belgesi (Verklaring in kracht van gewijsde) gerekiyor. Taraflar birlikte veya 90 gün içinde ayrı başvurabiliyor; Türkiye\'de dava açmaya gerek kalmadan doğrudan konsolosluk üzerinden tescil mümkün.'],
            ['rotterdam-bk', 'adli-sicil', 'https://rotterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/391014', 'Randevu zorunlu, POSTA KABUL EDİLMİYOR. Ayrı bir konsolosluk harcı yok. Belge e-Devlet/e-Apostil (eapostil.gov.tr) üzerinden tamamen elektronik ve apostilli şekilde de alınabiliyor.'],
            ['rotterdam-bk', 'evlilik-tescili', 'https://rotterdam-bk.mfa.gov.tr/Mission/ShowInfoNote/390126', 'Belgeler ibraz edildikten sonra ayrı bir tarih belirleniyor (fiilen randevu zorunlu). Formül B (düzenlenme tarihinden itibaren 6 AY GEÇERLİ) + yabancı eş için Formül A gerekiyor. Evlilik Hollanda\'da gerçekleşmişse bildirim mutlaka Hollanda\'daki bir Türk temsilciliğine yapılmalı.'],

            // ============ Deventer Başkonsolosluğu (Groningen/Friesland/Drenthe/Overijssel/Gelderland/Limburg) ============
            ['deventer-bk', 'adres-kaydi', 'https://deventer-bk.mfa.gov.tr/Mission/ShowAnnouncement/119526', 'Şahsen başvuru zorunlu, POSTA KABUL EDİLMİYOR ("posta ile gönderilen formlar işleme alınmayacaktır"). Ücretsiz, 20 iş günü içinde bildirim şart.'],
            ['deventer-bk', 'ehliyet', 'https://deventer-bk.mfa.gov.tr/Mission/ShowInfoNote/381853', 'Randevu zorunlu. Başvurudan en az 6 ay önce Deventer\'a yurtdışı adres beyanı yapılmış olması şart; adres beyanı olmayanın başvurusu alınmıyor. Adli sicil belgesi başvuru sırasında bizzat konsolosluktan temin edilebiliyor.'],
            ['deventer-bk', 'noter-tasdik', 'https://deventer-bk.mfa.gov.tr/Mission/ShowAnnouncement/384599', 'Randevu zorunlu (2021\'den beri randevusuz kabul yok). "Tercüme ve İmza Tasdik İşlemleri" tek başlık altında birleştirilmiş. Ücret Deventer\'ın kendi sitesinde yayınlanmamış.'],
            ['deventer-bk', 'tercume-tasdiki', 'https://deventer-bk.mfa.gov.tr/Mission/ShowAnnouncement/384599', 'Aynı kaynak/koşullar (noter tasdikiyle birleşik). Tercümenin Türkçe düzenlenmiş veya sitede kayıtlı yeminli tercümanlarca yapılmış olması şart.'],
            ['deventer-bk', 'vatandaslik', 'https://deventer-bk.mfa.gov.tr/Mission/ShowInfoNote/416188', 'Üçü de (izinle çıkma, yeniden kazanma, evlilik yoluyla) randevu zorunlu — "farklı bölümden alınmış randevuyla işlem yapılamaz". Evlilik yoluyla: her iki eş şahsen gelmeli, en az 3 yıl evlilik + fiilen birlikte yaşama şartı, "iyi hal kağıdı" isteniyor. Bu 3 sayfa Ocak 2026\'da tamamen yenilenmiş, çok güncel.'],
            ['deventer-bk', 'bosanma-tescili', 'https://deventer-bk.mfa.gov.tr/Mission/ShowInfoNote/416187', 'ÖNEMLİ FARK: bu işlem için web sayfasında normal randevu seçeneği YOK — özel randevu yalnız e-posta ile (consulate.deventer@mfa.gov.tr) veriliyor. Önce belgeler ön kontrol için e-postayla gönderiliyor, eksiksizse özel randevu veriliyor. Taraflar 90 gün içinde ayrı başvurabiliyor.'],
            ['deventer-bk', 'adli-sicil', 'https://deventer-bk.mfa.gov.tr/Mission/ShowAnnouncement/410009', 'RANDEVUYA GEREK YOK — belge E-Devlet portalı üzerinden talep ediliyor, imza karşılığı taahhütlü POSTA ile ev adresine gönderiliyor. Tek fiziki ziyaret gerektirmeyen kategori.'],
            ['deventer-bk', 'evlilik-tescili', 'https://deventer-bk.mfa.gov.tr/Mission/ShowInfoNote/416186', 'Randevu zorunlu ama POSTA İLE BAŞVURU DA KABUL EDİLİYOR (iadeli taahhütlü). Formül B (belediye veriliş tarihinden 6 ay geçerli) gerekiyor. Evlilik soyadı yanında kızlık soyadı kullanmak isteyen eşin başvurusu YALNIZ ŞAHSEN yapılabiliyor.'],
        ];
    }
}
