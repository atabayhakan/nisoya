<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Fransa'nın Paris Başkonsolosluğu'na "yerel ek" işler + Paris
 * Büyükelçiliği'nin YANLIŞ MUHATAP olduğunu düzeltir — Almanya/ABD ile AYNI
 * mimari, 2026-08-27 paralel araştırma turunda toplandı. ÖNCE
 * RehberFransaSeeder çalıştırılmış olmalı.
 *
 *     php artisan db:seed --class=RehberFransaYerelEkSeeder --force
 *
 * BERLİN İLE BİREBİR AYNI DESEN (hatta daha keskin): konsolosluk.gov.tr'nin
 * Fransa "Temsilcilik Seçiniz" listesinde "Büyükelçilik" seçeneği HİÇ YOK —
 * yalnız 6 başkonsolosluk var. Paris Büyükelçiliği'nin kendi "Bilgi
 * Notları" sayfasında bu 8 kategoriden hiçbiri yok (tek kayıt: 2016 tarihli
 * ikili ilişkiler notu).
 *
 * VERİ KALİTESİ: Bu tur yalnız Paris Başkonsolosluğu'nu detaylı araştırdı
 * (8 kategoriden 6'sı tam, 2'si kısmi — Tercüme Tasdiki'nin ayrı sayfası
 * yok, Vatandaşlık\'ta izinle çıkma/yeniden kazanma bulunamadı). Lyon/
 * Marsilya/Strazburg/Bordo/Nant Başkonsoloslukları henüz araştırılmadı.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberFransaYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'FR')->get()->keyBy('slug');
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

        $this->command?->info("Fransa yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        $parisYonlendirme = 'ÖNEMLİ: Paris Büyükelçiliği bu hizmeti SUNMUYOR — konsolosluk.gov.tr\'nin Fransa listesinde "Büyükelçilik" seçeneği bile yok, yalnız 6 başkonsolosluk var. Doğru muhatap ikamet edilen departmana göre Paris, Lyon, Marsilya, Strazburg, Bordo veya Nant Başkonsolosluğu\'dur.';

        return [
            // ============ Paris Büyükelçiliği — yönlendirme notu (Berlin deseni) ============
            ['paris', 'adres-kaydi', null, $parisYonlendirme],
            ['paris', 'ehliyet', null, $parisYonlendirme],
            ['paris', 'noter-tasdik', null, $parisYonlendirme],
            ['paris', 'tercume-tasdiki', null, $parisYonlendirme],
            ['paris', 'vatandaslik', null, $parisYonlendirme],
            ['paris', 'bosanma-tescili', null, $parisYonlendirme],
            ['paris', 'adli-sicil', null, $parisYonlendirme],
            ['paris', 'evlilik-tescili', null, $parisYonlendirme],

            // ============ Paris Başkonsolosluğu (gerçek muhatap, 32 departman/bölge) ============
            ['paris-bk', 'adres-kaydi', 'https://paris-bk.mfa.gov.tr/Mission/ShowInfoNote/399461', 'Randevusuz — hafta içi 09:00-13:00/14:00-17:00 şahsen. Posta ile başvuru belirtilmemiş (muhtemelen kabul edilmiyor). Ücret belirtilmemiş.'],
            ['paris-bk', 'ehliyet', 'https://paris-bk.mfa.gov.tr/Mission/ShowInfoNote/384601', 'Randevu ZORUNLU, POSTA KABUL EDİLMİYOR. Ücretler: yeni tip 37 EUR, eski tip B sınıfı 171 EUR, C-D-E sınıfı 261 EUR. Adres beyanının en az 6 ay önce yapılmış olması şart.'],
            ['paris-bk', 'noter-tasdik', 'https://paris-bk.mfa.gov.tr/Mission/ShowInfoNote/380635', 'Randevu ZORUNLU, POSTA KABUL EDİLMİYOR. Ücret sabit değil, vekaletname/azilname konusuna ve sayfa sayısına göre değişir. Fransız noterlerince apostille ile düzenlenmiş belgeler çoğu durumda ek konsolosluk tasdikine gerek duymuyor; ancak gayrimenkul işlemleri ve bazı vekaletname türleri mutlaka temsilcilikte düzenlenmeli.'],
            ['paris-bk', 'tercume-tasdiki', 'https://paris-bk.mfa.gov.tr/Mission/ShowInfoNote/376282', 'Ayrı/bağımsız bir bilgi notu yok — süreç fiilen Noter İşlemleri kapsamında yürüyor (kayıtlı yeminli tercüman listesi 18 kişi). Somut ücret yalnız Boşanma Tescili sayfasında rastlantısal bulundu: tercüme tasdiki 10 EUR/sayfa.'],
            ['paris-bk', 'vatandaslik', 'https://paris-bk.mfa.gov.tr/Mission/ShowInfoNote/375960', 'Yalnız EVLİLİK YOLUYLA (en az 3 yıl evlilik şartı) ve çok vatandaşlık bildirimi (8 EUR, nakit) bulundu. İZİNLE ÇIKMA ve YENİDEN KAZANMA için Paris\'e özgü ayrı bir sayfa bulunamadı (sayfa 2022 tarihli, en eski güncellenmemiş sayfalardan biri).'],
            ['paris-bk', 'bosanma-tescili', 'https://paris-bk.mfa.gov.tr/Mission/ShowInfoNote/375958', 'Başvurular YALNIZ RANDEVU İLE ŞAHSEN kabul ediliyor, posta yok. İlk başvurudan itibaren 90 gün içinde tamamlanmalı. Ücretler: 6 EUR (kararın geri gönderim masrafı) + 10 EUR/sayfa (tercüme tasdiki). E-posta: paris.tanimatescil@mfa.gov.tr.'],
            ['paris-bk', 'adli-sicil', 'https://paris-bk.mfa.gov.tr/Mission/ShowInfoNote/375960', 'Randevusuz — hafta içi 09:00-13:00 şahsen. POSTA KABUL EDİLMİYOR. Ücret belirtilmemiş.'],
            ['paris-bk', 'evlilik-tescili', 'https://paris-bk.mfa.gov.tr/Mission/ShowInfoNote/376137', 'İki ayrı süreç: (a) Başkonsoloslukta nikah kıyma — randevu zorunlu, yalnız görev bölgesinde ikamet edenler; (b) Fransız belediyesinde yapılan evliliğin bildirimi/tescili — POSTA İLE BAŞVURU KABUL EDİLİYOR (Fransa\'ya özgü önemli fark), ücret 7 EUR (adına yazılmış çekle). Boşanmış kadınlar için 300 gün bekleme süresi şartı.'],
        ];
    }
}
