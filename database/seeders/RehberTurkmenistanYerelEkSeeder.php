<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Türkmenistan'ın Aşkabat Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD
 * ile AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberTurkmenistanYerelEkSeeder --force
 *
 * VERİ KALİTESİ NOTU: Merkezi konsolosluk.gov.tr portalının ülke/temsilcilik
 * oturum mekanizması otomatik erişimlerde kararsız çalıştığından (Aşkabat
 * yerine sürekli başka ülkeler gösterdi, bir denemede WAF engeli çıktı),
 * güncel ücret tutarları ve "Temsilciliğe Özel" ek notlar bu turda
 * doğrulanamadı — tüm kategoriler yapısal olarak (randevu/posta durumu)
 * doğrulandı ama ücretsiz bırakıldı, uydurulmadı.
 *
 * ÖNEMLİ: Türkmenistan 1961 Lahey Apostil Sözleşmesi'ne TARAF DEĞİL —
 * apostil yerine konsolosluk tasdik zinciri geçerli (bağımsız kaynaklarla
 * doğrulandı).
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberTurkmenistanYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'TM')->get()->keyBy('slug');
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

        $this->command?->info("Türkmenistan yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['askabat', 'adres-kaydi', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5056', 'Randevu+şahsen zorunlu — Türkmenistan, postayla başvuruya izin verilen ülke (ABD/Kanada/Avustralya/Rusya) listesinde yok.'],
            ['askabat', 'ehliyet', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=16&procedureDetailId=5067', 'Randevu+şahsen zorunlu, posta yok. Adres beyanının en az 6 ay önce yapılmış olması ve Türk Polis Teşkilatını Güçlendirme Vakfı payının önceden ödenmiş olması şart.'],
            ['askabat', 'noter-tasdik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=5041', 'Randevu+şahsen zorunlu, posta yok. ÖNEMLİ: Türkmenistan Apostil Sözleşmesi\'ne taraf değil — belge geçerliliği için apostil yerine konsolosluk tasdik zinciri (yerel makam → Türkmenistan Dışişleri → T.C. Aşkabat Büyükelçiliği) izleniyor.'],
            ['askabat', 'tercume-tasdiki', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=5042', 'Randevu+şahsen zorunlu, posta yok.'],
            ['askabat', 'vatandaslik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=7&procedureDetailId=5000', 'İzinle çıkma, yeniden kazanma ve evlilik yoluyla — üçü de randevu+şahsen zorunlu, posta yok.'],
            ['askabat', 'bosanma-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5075', 'Randevu+şahsen zorunlu.'],
            ['askabat', 'adli-sicil', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=12&procedureDetailId=5059', 'Randevu+şahsen zorunlu; ayrıca online.konsolosluk.gov.tr üzerinden bir elektronik başvuru kanalına işaret var (içeriği bu turda doğrulanamadı).'],
            ['askabat', 'evlilik-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=3&procedureDetailId=5018', '8 kategori arasındaki TEK POSTA İSTİSNASI: sayfa açıkça "Posta ile başvurabilirsiniz" diyor, randevu/şahsen şartı yok.'],
        ];
    }
}
