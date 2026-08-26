<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * İspanya'nın Madrid Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile
 * AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberIspanyaYerelEkSeeder --force
 *
 * BERLİN'DEN FARKLI: Madrid bu 8 hizmeti kendisi sunuyor (Berlin'deki gibi
 * "büyükelçilikte konsolosluk şubesi yok" durumu YOK). İspanya'da ayrıca
 * Barselona Başkonsolosluğu var (konsolosluk.gov.tr dropdown'unda İspanya
 * için yalnızca bu 2 seçenek mevcut). Madrid'in görev bölgesi: Asturyas,
 * Bask, Ceuta ve Melila, Ekstremadura, Endülüs, Galisya, Kanarya Adaları,
 * Kantabria, Kastilla ve Leon, Kastilla-La Mancha, La Rioja, Madrid, Navara
 * + Andorra Prensliği (Madrid'in kendi Contact sayfası ayrıca Mursiya'yı da
 * sayıyor, ama About sayfası Mursiya'yı Barselona'ya veriyor — KAYNAKLAR
 * ÇELİŞİYOR, rehbere yazılmadan önce teyit edilmeli).
 *
 * VERİ KALİTESİ NOTU: Madrid'in kendi sitesinde bu 8 kategori için ayrı
 * sayfa yok — ProcedureList linki otomatik olarak merkezi konsolosluk.gov.tr
 * portalına yönleniyor. Aşağıdaki bulguların çoğu bu yüzden Madrid'e özgü
 * değil, TÜM temsilcilikler için geçerli ulusal/merkezi kurallardır (portal
 * "temsilcilik seçilmemiş" ve "Madrid seçili" durumunda aynı metni verdi).
 * Hiçbir kategoride EUR ücret bulunamadı.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberIspanyaYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'ES')->get()->keyBy('slug');
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

        $this->command?->info("İspanya yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['madrid', 'adres-kaydi', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedure/2', 'Randevu+şahsen gerekli — İspanya, postayla başvuruya izin verilen ülke (ABD/Kanada/Avustralya/Rusya) listesinde YOK. Ücret belirtilmemiş.'],
            ['madrid', 'ehliyet', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedure/16', 'İSPANYA\'YA ÖZGÜ ÖNEMLİ FARK: Türkiye-İspanya arasında 5 Nisan 2009 imzalı, 25 Nisan 2011 yürürlüğe giren "Ulusal Sürücü Belgelerinin Karşılıklı Tanınması ve Değişimine İlişkin Anlaşma" sayesinde Türk ehliyeti İspanyol trafik dairesinde (DGT) SINAVSIZ doğrudan değiştirilebiliyor — klasik konsolosluk yenilemesinden farklı bir süreç. Kaynak: Madrid Büyükelçiliği duyurusu https://madrid-be.mfa.gov.tr/Mission/ShowAnnouncement/119352 (02.03.2011).'],
            ['madrid', 'noter-tasdik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedure/6', 'Randevu+şahsen gerekli (İmza-Mühür Tasdiki). Posta ve ücret bilgisi Madrid\'e özgü olarak bulunamadı.'],
            ['madrid', 'tercume-tasdiki', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedure/6', 'Randevu+şahsen gerekli (Yeminli Tercümanlarca Yapılan Tercümelerin Tasdiki). Bölgede kayıtlı yeminli tercüman aramak için https://www.konsolosluk.gov.tr/TranslatorSearch/Index kullanılabiliyor.'],
            ['madrid', 'vatandaslik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedure/7', 'Dört alt kalem randevu gerektiriyor: ilk çıkış başvurusu, çıkma izin belgesinin teslimi, yeniden kazanma, evlilik yoluyla kazanma. Posta/ücret bilgisi Madrid\'e özgü olarak bulunamadı.'],
            ['madrid', 'bosanma-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedure/2', 'Randevu+şahsen gerekli (Yabancı Ülke Makamlarınca Verilen Boşanma Kararlarının Tescili). Posta/ücret bilgisi Madrid\'e özgü olarak bulunamadı.'],
            ['madrid', 'adli-sicil', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedure/12', '8 kategori içinde POSTAYI EN NET KABUL EDEN kalem: çevrimiçi başvuru yapılabiliyor ve belge adrese POSTAYLA gönderilebiliyor.'],
            ['madrid', 'evlilik-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedure/3', 'Portaldaki adı "Evlilik Tescili Başvurusu" (halk dilindeki "evlenme bildirimi" ile aynı işlem). POSTA YOLUYLA BAŞVURU MÜMKÜN — randevu/şahsen zorunlu değil.'],
        ];
    }
}
