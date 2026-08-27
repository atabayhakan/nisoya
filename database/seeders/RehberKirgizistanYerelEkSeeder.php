<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Kırgızistan'ın Bişkek Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile
 * AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberKirgizistanYerelEkSeeder --force
 *
 * DOMAIN NOTU: Doğru domain biskek-be.mfa.gov.tr (tireli); noktalı
 * "biskek.be.mfa.gov.tr" artık çözümlenmiyor.
 *
 * VERİ KALİTESİ NOTU — bu tur en zayıf sonucu verdi: Bişkek'in kendi
 * "Konsolosluk İşlemleri" ve "Randevu Al" menüleri kendi içerik barındırmıyor,
 * merkezi konsolosluk.gov.tr'ye yönlendiriyor; o portal da ülke/temsilcilik
 * seçimini JavaScript ile yaptığından otomatik araçlarla Bişkek'e özel alt
 * sayfaya inilemedi. Yalnız Adres Beyanı için (form Bişkek'in kendi
 * sunucusunda barındırıldığı için) somut bilgi bulundu. Diğer 7 kategori
 * için Bişkek'e özel hiçbir sayfa/ücret/randevu bilgisi doğrulanamadı.
 *
 * DOĞRULANAN EK GERÇEK: Kırgızistan'da Oş (Fahri Başkonsolosluk) ve
 * Celalabad/Jalal-Abad (Fahri Konsolosluk) adında iki bağlı temsilcilik var
 * — bunlar büyükelçilik üzerinden yönetiliyor, ayrı hizmet noktası değil.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberKirgizistanYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'KG')->get()->keyBy('slug');
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

        $this->command?->info("Kırgızistan yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['biskek-buyukelciligi', 'adres-kaydi', 'https://biskek-be.mfa.gov.tr/Mission/ShowAnnouncement/119473', 'Büyükelçiliğin kendi barındırdığı "Yurtdışı Adres Beyan Formu"na göre POSTA İLE BAŞVURU MÜMKÜN ama yalnız taahhütlü posta/kargo ile (normal posta kabul edilmiyor), kimlik fotokopisi eklenmeli. Randevu şartı belirtilmemiş, ücret bilgisi yok. Konsolosluk Şubesi çalışma saati: 10:00-12:30.'],
        ];
    }
}
