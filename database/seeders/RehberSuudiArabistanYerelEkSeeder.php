<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Suudi Arabistan'ın Riyad Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD
 * ile AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberSuudiArabistanYerelEkSeeder --force
 *
 * JURISDICTION NETLİĞİ: Riyad Büyükelçiliği'nin kendi bünyesinde Konsolosluk
 * Şubesi var (ayrı başkonsolosluk değil). Cidde Başkonsolosluğu'nun kendi
 * sitesi görev bölgesini "Tebuk, Medine, Mekke, Baha, Asir, Cizan, Necran"
 * olarak tanımlıyor — eleme yoluyla Riyad'ın bölgesi: Riyad, Doğu Bölge/
 * Dammam, Kasım, Hail, Cevf, Kuzey Sınır (Dammam'a gezici konsolosluk
 * ziyaretiyle doğrulandı).
 *
 * ÖNEMLİ GÜNCEL BULGU: Suudi Arabistan 8 Nisan 2022'de Lahey Apostil
 * Sözleşmesi'ne KATILDI, sözleşme 7 Aralık 2022'de yürürlüğe girdi (HCCH
 * resmi tablosuyla + Wikipedia çapraz doğrulandı). Bazı Türkçe tercüme
 * bürosu siteleri hâlâ "taraf değil" diye YANLIŞ/eski bilgi veriyor —
 * bu bilgiye itibar edilmemeli.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberSuudiArabistanYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'SA')->get()->keyBy('slug');
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

        $this->command?->info("Suudi Arabistan yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['riyad', 'adres-kaydi', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5056', 'Randevu+şahsen zorunlu, posta kabul edilmiyor (Suudi Arabistan geniş-ülke istisnasında yok). Dammam gibi başkentten uzak bölge sakinleri için Büyükelçilik dönemsel "gezici konsolosluk" hizmeti sunuyor.'],
            ['riyad', 'ehliyet', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=16&procedureDetailId=5067', 'Randevu+şahsen zorunlu. Suudi Arabistan\'dan alınmış sağlık raporu + Riyad\'da yeminli tercümanca Türkçe tercümesi, adli sicil raporu, biyometrik fotoğraf gerekiyor; adres beyanının en az 6 ay önce yapılmış olması şart.'],
            ['riyad', 'noter-tasdik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=5041', 'Randevu+şahsen zorunlu, ücret sabit değil. ÖNEMLİ: Suudi Arabistan Aralık 2022\'den beri Apostil Sözleşmesi\'ne taraf — Suudi makamlarınca düzenlenmiş bir belge için artık büyükelçilikten imza-mühür tasdiki almaya gerek YOK, yetkili Suudi makamından apostil şerhi yeterli.'],
            ['riyad', 'tercume-tasdiki', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=5042', 'Randevu+şahsen zorunlu. Başvuru yalnız tercümeyi yapan yeminli tercümanın yemin ettiği temsilciliğe (Riyad\'da yeminliyse Riyad\'da) yapılabiliyor. Büyükelçilik kendi yeminli tercümanlık sınavını da düzenliyor.'],
            ['riyad', 'vatandaslik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=7&procedureDetailId=5000', 'İzinle çıkmada posta AÇIKÇA YASAK, yalnız ikamet bölgesinden sorumlu temsilciliğe. Yeniden kazanma için referans ücret $8,00 (USD); apostil kuralı burada da geçerli.'],
            ['riyad', 'bosanma-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5075', 'Randevu ile başvuru, ücretsiz. Suudi Arabistan federal/eyalet sistemiyle yönetilmediğinden, genel kuraldan çıkarımla Riyad VEYA Cidde\'den herhangi birine başvurulabileceği düşünülüyor (Suudi Arabistan\'a özel ayrı bir teyit yok). Apostil şerhli karar için ayrıca noter/temsilcilik onayına gerek kalmıyor.'],
            ['riyad', 'adli-sicil', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=12&procedureDetailId=5059', 'Randevu+şahsen (veya vekil), ücretsiz. Online başvuru + adrese gönderim seçeneği teorik olarak var (online.konsolosluk.gov.tr) ama Riyad için fiilen aktif olup olmadığı doğrulanamadı. 44 dilde (Arapça dahil) düzenlenebiliyor.'],
            ['riyad', 'evlilik-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=3&procedureDetailId=5018', '8 kategori içinde POSTA İLE BAŞVURULABİLEN TEK işlem. Referans ücret $28,00 (USD, Uluslararası Aile Cüzdanı). Yerel nitelikte belge ise yalnız evliliğin gerçekleştiği bölgeden sorumlu temsilciliğe (Riyad/Dammam → Riyad; Cidde/Mekke/Medine → Cidde), uluslararası nitelikte (Formül B) ise herhangi bir temsilciliğe başvurulabiliyor.'],
        ];
    }
}
