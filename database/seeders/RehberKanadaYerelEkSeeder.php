<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Kanada'nın Ottava Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile
 * AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberKanadaYerelEkSeeder --force
 *
 * BERLİN'DEN FARKLI: Ottava bu hizmetleri GERÇEKTEN kendisi sunuyor —
 * ABD'deki Washington gibi. Görev bölgesi kendi Contact sayfasında dar
 * tanımlanmış: "Ottava-Gatineau, Ulusal Başkent Bölgesi ve Ontario'da
 * Kingston-Pembroke ekseninin doğusu" (bazı ikincil kaynakların iddia
 * ettiği "Toronto/Vancouver dışındaki tüm Kanada" tanımından DAHA DAR).
 * Kanada'da ayrıca Toronto, Montreal, Vancouver Başkonsoloslukları var.
 *
 * DOMAIN NOTU: "ottawa-be.mfa.gov.tr" ÇALIŞMIYOR (DNS hatası) — doğru
 * format Türkçe harf çevirisiyle "ottava-be.mfa.gov.tr".
 *
 * VERİ KALİTESİ NOTU: Sürücü belgesi yenileme ve vatandaşlık işlemleri
 * (izinle çıkma/yeniden kazanma/evlilik) için Ottava'ya özel hiçbir kaynak
 * bulunamadı — bu ikisi seeder'a eklenmedi.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberKanadaYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'CA')->get()->keyBy('slug');
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

        $this->command?->info("Kanada yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['ottawa', 'adres-kaydi', 'https://ottava-be.mfa.gov.tr/Mission/ShowInfoNote/225892', 'Bu temsilcilikte (Ottava) POSTA ile başvuru kabul ediliyor (SSS\'nin postayla yapılabilecek 10 işlem listesinde). Randevu şartı net değil, muhtemelen zorunlu değil.'],
            ['ottawa', 'noter-tasdik', 'https://ottava-be.mfa.gov.tr/Mission/ShowInfoNote/225892', 'Yabancı makamdan alınan belgenin imza-mühür tasdiki POSTA ile kabul ediliyor. Vekaletname gibi işlemler randevu GEREKTİRMİYOR. Yeni tip Mavi Kart tek başına kimlik belgesi olarak kabul ediliyor. Kabul saatleri 09:00-12:00 ve 13:00-16:00.'],
            ['ottawa', 'tercume-tasdiki', 'https://ottava-be.mfa.gov.tr/Mission/ShowInfoNote/414903', 'Yeminli tercümanın imza-mühür tasdiki POSTA ile kabul ediliyor. Önce Ottava\'da kayıtlı bir yeminli tercümana çevirtme, sonra tasdik mantığı geçerli.'],
            ['ottawa', 'bosanma-tescili', 'https://ottava-be.mfa.gov.tr/Mission/ShowInfoNote/225892', 'İki yol sunuluyor: (a) Türkiye\'de barolu bir avukata vekalet verip "tenfiz" davası açtırmak, (b) temsilciliğin doğrudan tanıma işlemi yapması. SSS hangi durumda hangi yolun geçerli olduğunu netleştirmiyor — muhtemelen çekişmesiz/ortak boşanmalar (b) şıkkına giriyor.'],
            ['ottawa', 'adli-sicil', 'https://ottava-be.mfa.gov.tr/Mission/ShowInfoNote/225892', 'RANDEVULU VE ŞAHSEN başvuru gerekiyor, posta KABUL EDİLMİYOR. Herhangi bir işlem harcı YOK — ücretsiz. T.C. Kimlik Kartı yeterli; Mavi Kart sahipleri ve Türkiye\'de 6+ ay yasal ikamet etmiş yabancılar da başvurabiliyor.'],
            ['ottawa', 'evlilik-tescili', 'https://ottava-be.mfa.gov.tr/Mission/ShowInfoNote/225892', 'POSTA ile başvuru kabul ediliyor. Evlilik tarihinden itibaren 30 gün içinde bildirilmesi gerekiyor; geç bildirimlerde idari para cezası uygulanıyor (tutar belirtilmemiş).'],
        ];
    }
}
