<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Polonya'nın Varşova Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile
 * AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberPolonyaYerelEkSeeder --force
 *
 * BERLİN'DEN FARKLI: Büyükelçiliğin kendi "Hakkında" sayfası açıkça
 * "Konsolosluk Hizmetleri tüm Polonya için Büyükelçiliğimiz Konsolosluk
 * Şubesi görev alanındadır" diyor — ayrı başkonsolosluk yok, Poznan/Gdansk/
 * Kraków/Wrocław/Łódź'de yalnız fahri başkonsolosluklar var.
 *
 * VERİ KALİTESİ NOTU: Vatandaşlık işlemlerinin izinle çıkma/yeniden kazanma
 * alt türleri, Boşanma Tescili ve Adli Sicil Kaydı için Varşova'ya özel
 * dedike bir sayfa bulunamadı (merkezi portalın oturum-bazlı mimarisi
 * otomatik erişimi engelliyor) — bu üçü seeder'a eklenmedi.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberPolonyaYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'PL')->get()->keyBy('slug');
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

        $this->command?->info("Polonya yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['varsova', 'adres-kaydi', 'https://varsova-be.mfa.gov.tr/Mission/ShowInfoNote/408580', 'Yerleşmeden itibaren 20 GÜN içinde bildirim şart (bazı ülkelerdeki 30 günlük standarttan daha kısa). Randevu şart, ceza tutarı belirtilmemiş.'],
            ['varsova', 'ehliyet', 'https://varsova-be.mfa.gov.tr/Mission/ShowAnnouncement/381763', 'Randevu zorunlu; e-Devlet\'ten adli sicil alınamıyorsa 2 AYRI randevu gerekiyor (ehliyet + adli sicil). Ücret: 116 PLN değerli kağıt + 20 PLN kargo = 136 PLN nakit + ayrı vakıf payı. Adres beyanının en az 6 ay önce yapılmış olması şart. ⚠️ Kaynak 2021 tarihli, güncelliği doğrulanamadı.'],
            ['varsova', 'noter-tasdik', 'https://varsova-be.mfa.gov.tr/Mission/ShowInfoNote/408466', 'Randevu+şahsen zorunlu. Somut PLN rakamı yok, "her biri için farklı harç ücreti" deniyor. Sayfada bağımsız bir "imza tasdiki" kalemi ayrıca maddelenmemiş, vekaletname/azilname/muvafakatname genel başlığı altında.'],
            ['varsova', 'tercume-tasdiki', 'https://varsova-be.mfa.gov.tr/Mission/ShowInfoNote/408466', '"Metne şamil olmamak kaydıyla" yalnız tercümanın imzası/kimliği tasdik ediliyor. Randevu+şahsen zorunlu. Büyükelçilik kendi yeminli tercüman listesini yayınlıyor: Varşova\'da 12, ayrıca Krakow/Katowice/Przemyśl\'de birer kişi.'],
            ['varsova', 'vatandaslik', 'https://varsova-be.mfa.gov.tr/Mission/ShowInfoNote/408466', 'Yalnız EVLİLİK YOLUYLA KAZANMA için veri bulundu: aynı gün için en az 5 ayrı randevu gerekiyor (~1,5 saat: evrak tasdiki + mülakat + kayıt). En az 3 yıl evlilik + birlikte yaşama şartı. Ücret: ~200 PLN vatandaşlık bedeli + 50 PLN posta + ~300 PLN tercüme/noter tasdiki (nakit). İzinle çıkma ve yeniden kazanma için Varşova\'ya özel sayfa bulunamadı (hizmet var, prosedür detayı yok).'],
            ['varsova', 'evlilik-tescili', 'https://varsova-be.mfa.gov.tr/Mission/ShowInfoNote/408466', 'Yasal süre: evlilik tarihinden itibaren 30 GÜN içinde tescil zorunlu. 30 gün dolmadan POSTA İLE BAŞVURU MÜMKÜN (asıllar + 2\'şer fotokopi, zarfa 50 PLN posta ücreti, "Evlilik Tescili İçindir" yazılmalı). 30 gün geçtiyse şahsen zorunlu + ~250 PLN idari para cezası + 50 PLN posta. Posta adresi: Ambasada Turcji, Dzial Konsularny, Ul. Rakowiecka 19, 02-517 Warszawa.'],
        ];
    }
}
