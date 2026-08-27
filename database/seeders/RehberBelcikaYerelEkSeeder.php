<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Belçika'nın Brüksel Başkonsolosluğu'na "yerel ek" işler — Almanya/ABD ile
 * AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberBelcikaYerelEkSeeder --force
 *
 * BERLİN'DEN FARKLI: Büyükelçiliğin kendi "Hakkında" sayfası (bruksel-be.
 * mfa.gov.tr/Mission/About) "konsolosluk hizmetleri verilmemektedir" DEMİYOR
 * — tam tersine kendisine bağlı iki başkonsolosluğu (Brüksel + Anvers)
 * olduğunu belirtiyor. Brüksel şehri Brüksel Başkonsolosluğu'nun (bruksel-
 * bk.mfa.gov.tr) kendi görev bölgesinde (Anvers geri kalan Flaman bölgesine
 * bakıyor). AB Nezdinde Türkiye Daimi Temsilciliği ayrı bir misyon, bu 8
 * hizmetle ilgisi yok.
 *
 * VERİ KALİTESİ NOTU: 8 kategoriden yalnız 3'ünde (İmza/Suret Tasdiki,
 * Tercüme Tasdiki, Evlenme Bildirimi) Brüksel Başkonsolosluğu'nun kendi
 * sitesinde içerik bulundu — diğer 5'i (Adres Kaydı, Sürücü Belgesi,
 * Vatandaşlık, Boşanma Tescili, Adli Sicil) için Brüksel'e özel hiçbir
 * sayfa yayınlanmamış. Noter/Tercüme ücretleri 2015 tarihli, güncelliği
 * ŞÜPHELİ — panelde bu şekilde işaretlenmeli.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberBelcikaYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'BE')->get()->keyBy('slug');
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

        $this->command?->info("Belçika yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['bruksel', 'noter-tasdik', 'https://bruksel-bk.mfa.gov.tr/Mission/ShowAnnouncement/225636', 'Randevu genel kuralla zorunlu (konsolosluk.gov.tr), ödemeler yalnız banka/kredi kartıyla, nakit kabul edilmiyor. ⚠️ Bulunan tek ücret rakamı 2015 tarihli duyurudan (imza tasdiki tek 11,83 EUR/çift 20,65 EUR, suret tasdiki 4,68 EUR) — 11 yıllık, güncelliği doğrulanamadı, yerini alan yeni bir tarife bulunamadı.'],
            ['bruksel', 'tercume-tasdiki', 'https://bruksel-bk.mfa.gov.tr/Mission/ShowInfoNote/411645', 'Tercümenin Başkonsolosluğa kayıtlı listedeki bir yeminli tercüman tarafından yapılmış olması şart. Randevu zorunlu. ⚠️ Bulunan tek ücret rakamı (7,03 EUR/sayfa) 2015 tarihli, güncelliği doğrulanamadı.'],
            ['bruksel', 'evlilik-tescili', 'https://bruksel-bk.mfa.gov.tr/Mission/ShowAnnouncement/397215', 'POSTA İLE BAŞVURU AÇIKÇA KABUL EDİLİYOR (iadeli taahhütlü). Gerekli belgeler: nüfusa tescil dilekçesi, Belçika belediyesinden "acte de mariage", eşlerin kimlik fotokopileri, yabancı eş varsa uluslararası doğum belgesi. Ücret belirtilmemiş (muhtemelen ücretsiz). Bu, 2022 tarihli güncel bir duyuru.'],
        ];
    }
}
