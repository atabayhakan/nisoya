<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Kazakistan'ın Astana Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile
 * AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberKazakistanYerelEkSeeder --force
 *
 * JURISDICTION NOTU: Kazakistan'da Astana'nın yanında Almatı, Aktau ve
 * (çok yeni, 15.09.2025'ten beri aktif) Türkistan Başkonsoloslukları var.
 * Astana'nın kendi Contact sayfası "Almatı ve Aktau'nun görev bölgeleri
 * dışında kalan tüm Kazakistan" diyor ama Türkistan'dan hiç bahsetmiyor —
 * sayfa muhtemelen Türkistan açılışından sonra güncellenmemiş. Astana'nın
 * kesin il listesi bu yüzden netleşmedi, ama Dammam tarzı bir kanıtla
 * (Astana'nın Doğu Bölge/Dammam'a gezici konsolosluk göndermesi gibi)
 * Astana'nın başkent + kuzey-orta-doğu Kazakistan'a baktığı doğrulandı.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberKazakistanYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'KZ')->get()->keyBy('slug');
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

        $this->command?->info("Kazakistan yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['astana', 'adres-kaydi', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5056', 'Randevu+şahsen esas. Kazakistan\'ın ABD/Kanada/Avustralya/Rusya gibi "geniş ülke" posta istisnasına dahil olup olmadığı teyit edilemedi. Ücretsiz görünüyor.'],
            ['astana', 'ehliyet', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=16&procedureDetailId=5067', 'Randevu+şahsen zorunlu. Bu yalnız YENİLEME — Kazakistan ehliyetinin Türk ehliyetine dönüştürülmesi (tebdil) Astana\'da ayrı bir kalem olarak bulunamadı. Adres beyanının en az 6 ay önce yapılmış olması şart, ücret sabit değil.'],
            ['astana', 'noter-tasdik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=5041', 'Randevu+şahsen zorunlu, ücret sabit değil (işlem tipi/sayfa sayısına göre). Kazakistan Lahey Apostil Sözleşmesi\'ne taraf — yabancı belgenin Türkiye\'de geçerliliği için önce Kazakistan\'ın kendi yetkili makamınca Apostil alınması gerekiyor, büyükelçilik yalnız sonraki imza-mühür silsilesini tasdikliyor.'],
            ['astana', 'tercume-tasdiki', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=5042', 'Randevu+şahsen zorunlu. Tercümanın Astana Büyükelçiliği\'ne kayıtlı ve büyükelçilik huzurunda yemin etmiş olması şart.'],
            ['astana', 'vatandaslik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=7&procedureDetailId=5048', 'Yeniden kazanma ve evlilik yoluyla için ücret $8,00 (USD, iade-posta/kargo bedeli — asıl harç değil). İzinle çıkmada posta kabul edilmiyor.'],
            ['astana', 'bosanma-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5075', 'Randevu+şahsen zorunlu, ücretsiz. Taraflar aynı anda hazır olmak zorunda değil; ayrı başvurularda aradaki süre 90 günü geçemez.'],
            ['astana', 'adli-sicil', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=12&procedureDetailId=5059', 'Randevu+şahsen zorunlu, ücretsiz. Kazakistan\'a özgü avantaj: belge 44 dilde düzenlenebiliyor ve listede Kazakça AÇIKÇA VAR (Kırgızca/Özbekçe/Rusça da var).'],
            ['astana', 'evlilik-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=3&procedureDetailId=5018', 'POSTA İLE BAŞVURU AÇIKÇA KABUL EDİLİYOR, evlilik tarihinden itibaren en geç 30 gün içinde bildirim şart. Ücret: $28,00 (USD, Uluslararası Aile Cüzdanı bedeli). Evliliğin yapıldığı ülke Çok Dilde Nüfus Kayıt Sözleşmesi\'ne (CIEC No. XVI) taraf değilse evliliğin gerçekleştiği ülkedeki temsilciliğe yönlendiriliyor.'],
        ];
    }
}
