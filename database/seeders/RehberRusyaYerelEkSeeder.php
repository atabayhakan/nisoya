<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Rusya'nın Moskova Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile
 * AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberRusyaYerelEkSeeder --force
 *
 * JURISDICTION NOTU (Moskova'nın kendi Contact sayfasından doğrulandı):
 * Rusya'da St. Petersburg, Kazan ve Krasnodar Başkonsoloslukları da var.
 * Moskova'nın görev bölgesi kendi başına tanımlı DEĞİL — bu üç
 * başkonsolosluğun görev çevreleri HARİÇ tüm Rusya Federasyonu (yani
 * Moskova şehri dahil Sibirya/Urallar/Uzak Doğu gibi çok geniş bir alan).
 *
 * ÜLKEYE ÖZGÜ TEKRARLAYAN DESEN: Noter/Tercüme tasdikinde başvuru yalnız
 * "belgenin/tercümanın bağlı olduğu bölgeden sorumlu temsilciliğe"
 * yapılabiliyor — Rusya 4 ayrı temsilciliğe bölündüğü için bu kısıt hangi
 * şehre gidileceğini belirleyen kritik bir kural.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberRusyaYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'RU')->get()->keyBy('slug');
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

        $this->command?->info("Rusya yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['moskova', 'adres-kaydi', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5056', 'POSTA İLE BAŞVURU AÇIKÇA KABUL EDİLİYOR — Rusya, sayfanın kendi başlığında "geniş yüzölçümlü ülke" istisnası olarak isimle sayılıyor (ABD/Kanada/Avustralya/Rusya). Uzak bölgede (örn. Sibirya) yaşayanlar Adres Beyan Formu (B)\'yi iadeli taahhütlü posta/kargo ile gönderebiliyor. Ücretsiz.'],
            ['moskova', 'ehliyet', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=16&procedureDetailId=5067', 'Randevu gerekli, posta kabul edilmiyor (yalnız şahsen). Rusya\'nın yerel makamlarından alınan sağlık raporunun Moskova\'da yemin etmiş bir tercüman tarafından Türkçeye çevrilmesi zorunlu. Ayrıca "Türk Polis Teşkilatını Güçlendirme Vakfı payı" var (tutar sayfada yok).'],
            ['moskova', 'noter-tasdik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=5041', 'Randevu gerekli, posta kabul edilmiyor. Ücret sabit değil (sayfa sayısı/işlem tipine göre). KISIT: başvuru yalnız belgenin düzenlendiği bölgeden sorumlu temsilciliğe yapılabiliyor — Moskova\'nın görev bölgesinde düzenlenmiş belgeler için değilse başka bir Rusya temsilciliğine (Kazan/Krasnodar/St. Petersburg) gitmek gerekiyor.'],
            ['moskova', 'tercume-tasdiki', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=5042', 'Randevu gerekli, posta kabul edilmiyor. KISIT: başvuru yalnız tercümeyi yapan yeminli tercümanın yemin ettiği temsilciliğe yapılabiliyor — tercüman Moskova\'da yeminliyse tasdik de sadece Moskova\'da yapılabiliyor.'],
            ['moskova', 'vatandaslik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=7&procedureDetailId=5048', 'İzinle çıkmada posta yok, ücretsiz, yalnız ikamet bölgesinden sorumlu temsilciliğe. Yeniden kazanma ve evlilik yoluyla için ücret $7,00 (USD). Evlilik yoluyla: en az 3 yıl evlilik + Türkiye nüfusuna tescil + fiilen birlikte yaşama şartı.'],
            ['moskova', 'bosanma-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5075', 'Randevu gerekli, posta kabul edilmiyor, ücretsiz. Rusya federal aile hukukuna sahip (ABD tarzı eyalet sistemi yok) olduğundan, genel kuraldan çıkarımla Rusya\'daki 4 temsilcilikten (Moskova/Kazan/Krasnodar/St. Petersburg) herhangi birine başvurulabileceği düşünülüyor — Moskova\'ya özel ayrı bir teyit yok.'],
            ['moskova', 'adli-sicil', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=12&procedureDetailId=5059', 'Randevu gerekli (ayrıca online.konsolosluk.gov.tr üzerinden çevrimiçi seçenek var), posta kabul edilmiyor, ücretsiz. Belgeler sekmesinde Moskova\'ya özel "Vatandaş Bilgi Formu" şablonu var. 44 dilde belge düzenlenebiliyor.'],
            ['moskova', 'evlilik-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=3&procedureDetailId=5018', 'POSTA İLE BAŞVURU AÇIKÇA KABUL EDİLİYOR. Ücret $29,00 (USD, Uluslararası Aile Cüzdanı bedeli — tescilin kendisi değil). Belge yerel nitelikte (Rusya ZAGS belgesi) ise sadece evliliğin gerçekleştiği bölgeden sorumlu temsilciliğe, uluslararası nitelikte (Formül B) ise Rusya\'daki TÜM temsilciliklere başvurulabiliyor.'],
        ];
    }
}
