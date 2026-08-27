<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Avustralya'nın Canberra Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD
 * ile AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberAvustralyaYerelEkSeeder --force
 *
 * ÖNEMLİ JURISDICTION UYARISI (İtalya/Berlin'den bile farklı bir desen):
 * Sidney Başkonsolosluğu'nun kendi sitesi görev bölgesini "New South Wales,
 * Queensland, Northern Territory VE Australian Capital Territory (ACT)"
 * olarak tanımlıyor — yani CANBERRA'NIN KENDİSİ (ACT içinde) RESMEN Sidney
 * Başkonsolosluğu'nun görev bölgesinde! Melburn Başkonsolosluğu ise
 * Victoria/Güney Avustralya/Batı Avustralya/Tazmanya'ya bakıyor. Canberra
 * Büyükelçiliği'nin kendi resmi akreditasyon alanı Avustralya bile değil —
 * Kiribati, Marshall Adaları, Nauru, Solomon Adaları, Vanuatu.
 *
 * BUNA RAĞMEN Canberra'nın kendi randevu/işlem sistemi bu 8 kategorinin
 * çoğunu fiilen listeliyor — yani ACT'de yaşayan biri muhtemelen Canberra'ya
 * da başvurabiliyor, ama RESMİ/BİRİNCİL muhatap Sidney'dir. Nisoya'nın
 * Avustralya rehberinde bu ayrım netleştirilmeli; ideal olarak Sidney ve
 * Melburn Başkonsoloslukları da ayrıca araştırılıp seçenek olarak sunulmalı
 * (bu tur yalnız Canberra'yı kapsadı, henüz yapılmadı).
 *
 * VERİ KALİTESİ NOTU: Hiçbir kategoride AUD ücret bilgisine ulaşılamadı
 * (Masraflar sekmesi oturum/teknik kısıt nedeniyle açılamadı).
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberAvustralyaYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'AU')->get()->keyBy('slug');
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

        $this->command?->info("Avustralya yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        $jurisdiksiyonUyarisi = 'JURISDICTION UYARISI: Sidney Başkonsolosluğu\'nun kendi sitesi görev bölgesini NSW/QLD/NT/ACT olarak tanımlıyor — yani Canberra\'nın kendisi bile resmen Sidney\'in görev bölgesinde. Canberra Büyükelçiliği\'nin resmi akreditasyonu Avustralya değil Pasifik ada devletleri (Kiribati, Marshall Adaları, Nauru, Solomon Adaları, Vanuatu). Buna rağmen Canberra\'nın kendi randevu sistemi bu kategoriyi fiilen listeliyor.';

        return [
            ['canberra', 'adres-kaydi', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5056', "Posta ile başvuru mümkün (Avustralya, ABD/Kanada/Rusya ile birlikte \"geniş ülke\" istisnasında açıkça sayılıyor). {$jurisdiksiyonUyarisi}"],
            ['canberra', 'ehliyet', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=16&procedureDetailId=5067', "Randevu zorunlu, posta yok. Adres beyanının en az 6 ay önce yapılmış olması ve vakıf payının önceden ödenmiş olması şart. {$jurisdiksiyonUyarisi}"],
            ['canberra', 'tercume-tasdiki', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=5042', "Randevu zorunlu, posta yok. {$jurisdiksiyonUyarisi}"],
            ['canberra', 'vatandaslik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=7&procedureDetailId=5000', "İzinle çıkma, yeniden kazanma ve evlilik yoluyla — üçü de Canberra'nın listesinde randevulu-şahsi kalem olarak mevcut, posta yok. {$jurisdiksiyonUyarisi}"],
            ['canberra', 'bosanma-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5075', "Randevu zorunlu, posta yok. {$jurisdiksiyonUyarisi}"],
            ['canberra', 'adli-sicil', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=12&procedureDetailId=5059', "Randevu zorunlu görünüyor ama ayrıca online.konsolosluk.gov.tr üzerinden çevrimiçi başvuru seçeneği de var. {$jurisdiksiyonUyarisi}"],
            ['canberra', 'evlilik-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=3&procedureDetailId=5018', "8 kategori içinde POSTAYI AÇIKÇA KABUL EDEN TEK kalem — sayfa \"Posta ile başvurabilirsiniz\" diyor, randevu/şahsen şartı yok. {$jurisdiksiyonUyarisi}"],
        ];
    }
}
