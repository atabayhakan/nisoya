<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Özbekistan'ın Taşkent Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile
 * AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberOzbekistanYerelEkSeeder --force
 *
 * ÖNEMLİ JURISDICTION NOTU: Özbekistan'da T.C.'nin İKİ temsilciliği var —
 * konsolosluk.gov.tr'nin kendi dropdown'undan doğrulandı (Özbekistan
 * seçilince "Semerkant Başkonsolosluğu" ve "Taşkent Büyükelçiliği" olmak
 * üzere iki seçenek çıkıyor). Semerkant Başkonsolosluğu'nun görev bölgesi
 * (kendi Contact sayfasından): Semerkant, Buhara, Cizzah, Harezm,
 * Kaşkaderya, Nevai, Surhanderya, Karakalpakistan (8 vilayet). Taşkent
 * geri kalan vilayetlere (Taşkent, Andican, Fergana, Nemengan, Sirderya
 * vb.) bakıyor olmalı — bu Berlin'deki "yanlış muhatap" değil, Almanya/ABD
 * tarzı bir COĞRAFİ BÖLÜNME. Semerkant için ayrı araştırma henüz yapılmadı.
 *
 * VERİ KALİTESİ NOTU: Vatandaşlık işlemleri ve Adli Sicil Kaydı için
 * Taşkent'e özel dedike bir sayfa bulunamadı — bu ikisi seeder'a eklenmedi.
 * Tercüme Tasdiki için yalnız tercüman iletişim listesi bulundu, tasdik
 * sürecinin kendisi (randevu/ücret) doğrulanamadı.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberOzbekistanYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'UZ')->get()->keyBy('slug');
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

        $this->command?->info("Özbekistan yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['taskent', 'adres-kaydi', 'https://taskent-be.mfa.gov.tr/Mission/ShowInfoNote/393316', 'Randevu gerekmiyor, hafta içi randevusuz başvurulabiliyor. POSTA ile başvuru KABUL EDİLMİYOR, yalnız şahsen. 20 iş günü içinde yapılırsa ücretsiz, geç başvuruda idari para cezası (sayfada "2023 yılı için 16 USD" yazıyor, güncelliği teyide açık).'],
            ['taskent', 'ehliyet', 'https://taskent-be.mfa.gov.tr/Mission/ShowInfoNote/396041', 'Randevu GEREKLİ. Ücret: 34 USD harç (2023 tarihli, güncelliği teyide açık) + eski belgenin düzenlenme yılına göre değişen vakıf payı. Şartlar: en az 6 ay önceden adres beyanı + Özbekistan\'dan alınıp yeminli tercümanca çevrilmiş sağlık raporu. G sınıfı (iş makinesi) belgeler yenilenemiyor.'],
            ['taskent', 'noter-tasdik', 'https://taskent-be.mfa.gov.tr/Mission/ShowInfoNote/389927', 'Vekaletname/Azilname/Muvafakatname/Taahhütname\'yi kapsıyor. "Tüm noterlik işlemleri için müracaatların şahsen yapılması gerekmektedir" — POSTA KABUL EDİLMİYOR. Ücret ABD Doları cinsinden nakit tahsil ediliyor, sabit tutar belirtilmemiş. Çalışma saatleri: Pzt-Cuma 09:00-12:30/13:30-18:00.'],
            ['taskent', 'tercume-tasdiki', 'https://taskent-be.mfa.gov.tr/Mission/ShowInfoNote/399098', 'Bu sayfa yalnız kayıtlı yeminli tercümanların (Özbekçe 13, Rusça 5 kişi) iletişim listesini veriyor; tasdik süreci/ücret/randevu detayı Taşkent\'e özel olarak doğrulanamadı — muhtemelen Noterlik Şubesi kapsamında yürüyor.'],
            ['taskent', 'bosanma-tescili', 'https://taskent-be.mfa.gov.tr/Mission/ShowInfoNote/389916', 'ÖNEMLİ: Özbekistan 15.04.2012\'den beri 1961 Lahey Apostil Sözleşmesi\'ne taraf (HCCH kaynağından teyit edildi) — yani buradaki karar için doğrudan apostil süreci geçerli. Gerekli: apostil şerhli karar + Türkçe tercüme + kimlik. Randevu/posta bilgisi sayfada açık değil.'],
            ['taskent', 'evlilik-tescili', 'https://taskent-be.mfa.gov.tr/Mission/ShowInfoNote/412211', 'Şahsen başvuruda randevu gerekli, POSTA İLE BAŞVURUDA RANDEVU GEREKMİYOR — belge asılları + yeminli tercüman onaylı apostilli tercümeleri postayla gönderilebiliyor. Uluslararası Aile Cüzdanı bedeli yıllık değişiyor, sabit tutar yok. Özbekistan makamlarında yapılan evlilikte önce yerel tescilin tamamlanmış olması şart; kadın taraf için 300 günlük yasal bekleme süresi uygulanıyor.'],
        ];
    }
}
