<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * İtalya'nın Roma Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile AYNI
 * mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberItalyaYerelEkSeeder --force
 *
 * BERLİN'DEN FARKLI: Roma bu 8 hizmeti kendisi sunuyor (yönlendirme yok).
 * İtalya'da ayrıca Milano Başkonsolosluğu var; Roma'nın görev bölgesi Lazio,
 * Toscana, Umbria, Campania, Puglia, Abruzzo, Molise, Basilicata, Calabria,
 * Sardegna, Sicilia (11 bölge) — Milano geri kalan 9 bölge + San Marino'ya
 * bakıyor. Malta'nın hâlâ Roma'ya mı bağlı yoksa 2009'dan beri kendi
 * büyükelçiliğine mi (valletta-be.mfa.gov.tr) geçtiği DOĞRULANAMADI.
 *
 * VERİ KALİTESİ NOTU: konsolosluk.gov.tr'nin oturum-bazlı mimarisi yüzünden
 * (aynı URL farklı denemelerde farklı ülke içeriği gösterdi) yalnız 3
 * kategori (adres kaydı, sürücü belgesi/tercüme, evlenme bildirimi) için
 * Roma'ya özel, güvenilir ücret/randevu/posta detayı doğrulanabildi. Diğer
 * 5 kategori Roma'nın menüsünde İSİM OLARAK doğrulandı (yani Berlin tarzı
 * bir "bu hizmet burada yok" sorunu YOK) ama ücret/posta detayı bu turda
 * güvenilir şekilde teyit edilemedi — bu yüzden onlara URL eklenmedi.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberItalyaYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'IT')->get()->keyBy('slug');
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

        $this->command?->info("İtalya yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['roma', 'adres-kaydi', 'https://roma-be.mfa.gov.tr/Mission/ShowInfoNote/345275', 'Randevu zorunlu, posta/e-posta ile başvuru KABUL EDİLMİYOR — bildirim şahsen yapılmalı. 20 iş günü içinde bildirilmezse idari para cezası (5490 sayılı Kanun).'],
            ['roma', 'ehliyet', 'https://roma-be.mfa.gov.tr/Mission/ShowAnnouncement/404149', 'İTALYA\'YA ÖZGÜ FARK: Roma sitesinde "ehliyet yenileme" değil iki farklı hizmet var — (a) Türk ehliyetinin İtalya\'da geçerliliği için resmi tercümesi (27 EUR nakit), (b) 01.01.2016 sonrası düzenlenmiş ehliyetler için İtalyan ehliyetine dönüştürme sertifikası (belediye ikamet kaydından itibaren 6 yıldan az süre geçmiş olmalı, 27 EUR nakit, ayrıca). Salt "süresi dolan ehliyeti yenileme" hizmeti Roma sitesinde bulunamadı. Randevu zorunlu.'],
            ['roma', 'noter-tasdik', null, 'Roma\'nın kendi menüsünde "Noterlik İşlemi" ve "Yabancı Makamlardan Alınan Belgelerin İmza-Mühür Tasdiki" doğrulandı (yönlendirme yok). Ücret/posta detayı bu turda güvenilir şekilde teyit edilemedi.'],
            ['roma', 'tercume-tasdiki', null, 'Roma\'nın kendi menüsünde "Yeminli Tercümanlarca Yapılan Tercümelerin Tasdiki" doğrulandı (yönlendirme yok). Genel tercüme tasdiki ücreti teyit edilemedi (yalnız sürücü belgesi tercümesi için 27 EUR doğrulandı, bkz. ehliyet kategorisi).'],
            ['roma', 'vatandaslik', null, 'Roma\'nın kendi menüsünde izinle çıkma, yeniden kazanma ve evlilik yoluyla kazanma üç alt kalem olarak doğrulandı (yönlendirme yok). Ücret/posta detayı bu turda güvenilir şekilde teyit edilemedi.'],
            ['roma', 'bosanma-tescili', null, 'Roma\'nın kendi menüsünde "Yabancı Ülke Makamlarınca Verilen Boşanma Kararlarının Tescili" doğrulandı (yönlendirme yok). Ücret/posta detayı bu turda güvenilir şekilde teyit edilemedi.'],
            ['roma', 'adli-sicil', null, 'Roma\'nın kendi menüsünde "Adli Sicil Belgesi Başvurusu" doğrulandı (yönlendirme yok). Ücret/posta detayı bu turda güvenilir şekilde teyit edilemedi; birçok vatandaş bu belgeyi e-Devlet üzerinden konsolosluğa hiç gitmeden alabiliyor.'],
            ['roma', 'evlilik-tescili', 'https://roma-be.mfa.gov.tr/Mission/ShowInfoNote/344761', 'Randevu zorunlu, ŞAHSEN VEYA POSTA ile başvurulabiliyor (8 kategori içinde bunu açıkça belirten tek Roma sayfası). Ücret 9 EUR nakit (posta ile ödemede makbuz eklenmeli). Kadın taraf daha önce boşanmışsa 300 günlük iddet süresinin kaldırılmasına dair mahkeme kararı gerekiyor.'],
        ];
    }
}
