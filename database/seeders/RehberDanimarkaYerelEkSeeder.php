<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Danimarka'nın Kopenhag Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile
 * AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberDanimarkaYerelEkSeeder --force
 *
 * BERLİN'DEN FARKLI: Kopenhag bu 8 hizmeti GERÇEKTEN kendisi sunuyor —
 * konsolosluk.gov.tr'nin Danimarka listesinde tek seçenek (Aarhus'ta yalnız
 * fahri başkonsolosluk var, tam yetkili değil).
 *
 * DİKKAT ÇEKİCİ ÜLKEYE ÖZGÜ BULGU: Danimarka, T.C. dış temsilciliklerinin
 * NİKAH KIYAMADIĞI ülkeler listesinde — Kopenhag'da iki taraf da Türk olsa
 * bile büyükelçilikte nikah kıyılamıyor, yalnız "sonradan tescil" mümkün.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberDanimarkaYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'DK')->get()->keyBy('slug');
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

        $this->command?->info("Danimarka yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['kopenhag', 'adres-kaydi', 'https://kopenhag-be.mfa.gov.tr/Mission/ShowInfoNote/401520', 'Randevu+şahsen zorunlu, posta yok. Ücretsiz, ama 20 iş günü içinde bildirilmezse 135 DKK idari para cezası (yalnız vezne.konsolosluk.gov.tr üzerinden online ödeme, nakit kabul edilmiyor). Danimarka\'ya özgü belge: yerel sağlık sigortası kartı (Sygesikringskort).'],
            ['kopenhag', 'ehliyet', 'https://kopenhag-be.mfa.gov.tr/Mission', 'Randevu+şahsen zorunlu, posta yok. Bu yalnız mevcut Türk ehliyetinin yenilenmesi (Danimarka ehliyetinin çevrilmesi değil). Adres beyanının Kopenhag\'da en az 6 ay önce yapılmış olması şart; Danimarka\'dan alınmış sağlık raporu isteniyor.'],
            ['kopenhag', 'noter-tasdik', 'https://kopenhag-be.mfa.gov.tr/Mission', 'Randevu+şahsen zorunlu, posta yok. Ücret sabit değil, işlem tipi/sayfa sayısına göre değişir. Yalnız belgenin düzenlendiği bölgeden sorumlu temsilciliğe (Danimarka için Kopenhag) başvurulabiliyor.'],
            ['kopenhag', 'tercume-tasdiki', 'https://kopenhag-be.mfa.gov.tr/Mission', 'Randevu+şahsen zorunlu, posta yok. Tercümanın Kopenhag Büyükelçiliği huzurunda yemin etmiş olması şart — Danimarka\'daki başka bir yeminli tercümanın çevirisi yetmiyor.'],
            ['kopenhag', 'vatandaslik', 'https://kopenhag-be.mfa.gov.tr/Mission', 'İzinle çıkmada POSTA AÇIKÇA YASAK. Yeniden kazanma ve evlilik yoluyla için ücret 250 DKK (posta/diğer tahsilat). Danimarka\'ya özgü belge: "Statsborgerretsbevis" (DK vatandaşlık belgesi), temin edileceği makam: Indfødsretskontoret, Slotsholmsgade 10, Kopenhag.'],
            ['kopenhag', 'bosanma-tescili', 'https://kopenhag-be.mfa.gov.tr/Mission', 'Randevu+şahsen zorunlu, ücretsiz. Danimarka\'nın apostilli boşanma kararı "Skilsmissebevilling" adıyla anılıyor; belgede Danimarka Kimlik Numarası (Personnummer) yazılı olmalı.'],
            ['kopenhag', 'adli-sicil', 'https://kopenhag-be.mfa.gov.tr/Mission', 'Randevu+şahsen (veya vekaletnameli vekil), ücretsiz. 44 dilde belge düzenlenebiliyor (Danca dahil).'],
            ['kopenhag', 'evlilik-tescili', 'https://kopenhag-be.mfa.gov.tr/Mission', 'ÖNEMLİ: Danimarka, T.C. temsilciliklerinin NİKAH KIYAMADIĞI ülkeler arasında — bu yüzden işlem her zaman "sonradan tescil" şeklinde. POSTA İLE BAŞVURU AÇIKÇA KABUL EDİLİYOR (posta ücreti 150 DKK, yalnız DKK kabul ediliyor); evlilik tarihinden itibaren 30 gün içinde bildirim şart. İsteğe bağlı Uluslararası Aile Cüzdanı 206 DKK. Danimarka\'ya özgü belge: "Vielseattest".'],
        ];
    }
}
