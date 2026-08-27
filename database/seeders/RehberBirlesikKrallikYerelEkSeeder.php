<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Birleşik Krallık'ın Londra Başkonsolosluğu'na "yerel ek" işler + Londra
 * Büyükelçiliği'nin YANLIŞ MUHATAP olduğunu düzeltir — Almanya/ABD ile AYNI
 * mimari, 2026-08-27 paralel araştırma turunda toplandı. ÖNCE
 * RehberBirlesikKrallikSeeder çalıştırılmış olmalı.
 *
 *     php artisan db:seed --class=RehberBirlesikKrallikYerelEkSeeder --force
 *
 * BERLİN İLE BİREBİR AYNI DESEN: Londra Büyükelçiliği'nin kendi "Hakkında"
 * sayfası büyükelçiliğin konsolosluk hizmeti (pasaport/vekaletname/nüfus)
 * VERMEDİĞİNİ, bunun bağlı Başkonsolosluklarca (Londra, Edinburgh,
 * Manchester + Belfast Fahri Konsolosluk) yürütüldüğünü doğruluyor.
 *
 * VERİ KALİTESİ: Bu tur yalnız Londra Başkonsolosluğu'nu detaylı araştırdı
 * (8 kategoriden 6'sı tam, 2'si — İzinle Çıkma, ayrı adlandırılmış Suret
 * Tasdiki/Adli Sicil — kısmi/bulunamadı). Edinburgh ve Manchester
 * Başkonsoloslukları henüz araştırılmadı, genel taslak içerikle bekliyor.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberBirlesikKrallikYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'GB')->get()->keyBy('slug');
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

        $this->command?->info("Birleşik Krallık yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        $londraYonlendirme = 'ÖNEMLİ: Londra Büyükelçiliği bu hizmeti SUNMUYOR — kendi resmî sayfası günlük konsolosluk hizmeti (pasaport/vekaletname/nüfus) vermediğini belirtiyor. Doğru muhatap Londra Başkonsolosluğu (İskoçya/Kuzey İrlanda\'da ikamet edenler için Edinburgh Başkonsolosluğu veya Belfast Fahri Konsolosluğu).';

        return [
            // ============ Londra Büyükelçiliği — yönlendirme notu (Berlin deseni) ============
            ['londra', 'adres-kaydi', null, $londraYonlendirme],
            ['londra', 'ehliyet', null, $londraYonlendirme],
            ['londra', 'noter-tasdik', null, $londraYonlendirme],
            ['londra', 'tercume-tasdiki', null, $londraYonlendirme],
            ['londra', 'vatandaslik', null, $londraYonlendirme],
            ['londra', 'bosanma-tescili', null, $londraYonlendirme],
            ['londra', 'adli-sicil', null, $londraYonlendirme],
            ['londra', 'evlilik-tescili', null, $londraYonlendirme],

            // ============ Londra Başkonsolosluğu (gerçek muhatap) ============
            ['londra-bk', 'adres-kaydi', 'https://londra-bk.mfa.gov.tr/Mission/ShowInfoNote/396330', 'Randevu gerekmiyor, şahsen veya POSTA ile (taahhütlü) yapılabiliyor. Ücretsiz, ama 20 iş günü geçince 2026 için 814 TL karşılığı GBP idari para cezası (peşin indirimli). NHS kaydı da "proof of address" olarak kabul ediliyor.'],
            ['londra-bk', 'ehliyet', 'https://londra-bk.mfa.gov.tr/Mission/ShowInfoNote/396330', 'Yenileme hizmeti (yeni ehliyet verilmiyor), en az 6 ay önceden adres beyanı şart. 2026 ücretleri: A sınıfı £38,52, B sınıfı £116,17, diğer sınıflar £193,86 + £33 değerli kağıt/posta. Ayrıca 425 TL vakıf payı Türkiye\'den yatırılıyor.'],
            ['londra-bk', 'noter-tasdik', 'https://londra-bk.mfa.gov.tr/Mission/ShowInfoNote/390869', 'Randevu ZORUNLU (bir randevuda tek işlem), POSTA KABUL EDİLMİYOR (yalnız resmî kurum imza/mühür teyidi e-posta ile mümkün). Ortalama ücret bir A4 sayfa için £30-40 (sabit değil). "Suret tasdiki" ayrı bir kalem olarak listede yok; mirasçılık belgesi düzenlenmiyor.'],
            ['londra-bk', 'tercume-tasdiki', 'https://londra-bk.mfa.gov.tr/Mission/ShowInfoNote/390869', 'Başkonsolosluk yalnız yeminli tercümanın İMZASINI onaylıyor, metnin içeriğini değil. Süreç: akredite tercümana çevirt (liste: konsolosluk.gov.tr/TranslatorSearch), sonra "noter" randevusu al. İngiltere\'den alınan resmî belgeler için (pasaport/doğum/evlilik) dışarıdan tercüme istenmiyor, çeviriyi Başkonsolosluk kendisi yapıyor.'],
            ['londra-bk', 'vatandaslik', 'https://londra-bk.mfa.gov.tr/Mission/ShowInfoNote/406826', 'Yeniden kazanma ve evlilik yoluyla bulundu, randevu+şahsen zorunlu, posta yok. İzinle çıkma için Londra\'ya özel sayfa bulunamadı. Evlilik yoluyla: yabancı eşin DBS (İngiltere\'ye özgü sabıka kaydı) + son 3 yıl birlikte yaşama kanıtı isteniyor.'],
            ['londra-bk', 'bosanma-tescili', 'https://londra-bk.mfa.gov.tr/Mission/ShowInfoNote/396330', 'POSTA İLE BAŞVURU KABUL EDİLMİYOR ("posta ile başvurular kabul edilememektedir"), yalnız Birleşik Krallık mahkemelerinin kararı (Decree Absolute) kabul ediliyor, apostil şart, karar Türkçeye çevrilmesine GEREK YOK.'],
            ['londra-bk', 'adli-sicil', 'https://londra-bk.mfa.gov.tr/Mission/ShowInfoNote/390869', 'Ayrı, isimlendirilmiş bir sayfa yok — Noterlik hizmet listesinde bir kalem olarak geçiyor, muhtemelen aynı randevu sistemine tabi. Adli sicil raporu e-Devlet üzerinden de alınabiliyor.'],
            ['londra-bk', 'evlilik-tescili', 'https://londra-bk.mfa.gov.tr/Mission/ShowInfoNote/399782', 'Şahsen başvuruda randevu zorunlu; POSTA İLE BAŞVURUDA RANDEVU GEREKMİYOR. Posta şartları: zarfta gönderici adı/adresi yazılı olmalı, £10 posta çeki "Turkish Consulate General" adına, önceden ödenmiş dönüş zarfı eklenmeli. Ücret £10. Boşanmuş kadın için 300 gün iddet süresi şartı.'],
        ];
    }
}
