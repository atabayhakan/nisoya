<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Avusturya'nın Viyana Başkonsolosluğu'na "yerel ek" işler + Viyana
 * Büyükelçiliği'nin YANLIŞ MUHATAP olduğunu düzeltir — Almanya/ABD ile AYNI
 * mimari, 2026-08-27 paralel araştırma turunda toplandı. ÖNCE
 * RehberAvusturyaSeeder çalıştırılmış olmalı.
 *
 *     php artisan db:seed --class=RehberAvusturyaYerelEkSeeder --force
 *
 * BERLİN İLE BİREBİR AYNI DESEN: Viyana Büyükelçiliği'nin kendi sitesinde
 * ("Mission/ShowInfoNote/354379") doğrudan "Büyükelçiliğimizde konsolosluk
 * hizmetleri verilmemektedir" yazıyor. Avusturya'da Viyana'nın yanında
 * Salzburg VE Bregenz Başkonsoloslukları var (kullanıcının "Salzburg gibi"
 * varsayımının cevabı "evet, hatta iki tane").
 *
 * VERİ KALİTESİ: Bu tur yalnız Viyana Başkonsolosluğu'nu detaylı araştırdı
 * (8 kategoriden 6'sı tam bilgi notlarıyla doğrulandı, Vatandaşlık\'ta
 * yalnız izinle çıkma bulundu — yeniden kazanma/evlilik yoluyla
 * bulunamadı). Salzburg ve Bregenz Başkonsoloslukları henüz araştırılmadı.
 * HİÇBİR kategoride EUR ücret rakamı bulunamadı (Viyana'nın sistemli
 * tutumu — hepsi "belirtilmemiş" olarak işaretlenmeli).
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberAvusturyaYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'AT')->get()->keyBy('slug');
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

        $this->command?->info("Avusturya yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        $viyanaYonlendirme = 'ÖNEMLİ: Viyana Büyükelçiliği bu hizmeti SUNMUYOR — kendi sitesinde doğrudan "Büyükelçiliğimizde konsolosluk hizmetleri verilmemektedir" yazıyor. Doğru muhatap ikamet edilen eyalete göre Viyana, Salzburg veya Bregenz Başkonsolosluğu\'dur.';

        return [
            // ============ Viyana Büyükelçiliği — yönlendirme notu (Berlin deseni) ============
            ['viyana', 'adres-kaydi', null, $viyanaYonlendirme],
            ['viyana', 'ehliyet', null, $viyanaYonlendirme],
            ['viyana', 'noter-tasdik', null, $viyanaYonlendirme],
            ['viyana', 'tercume-tasdiki', null, $viyanaYonlendirme],
            ['viyana', 'vatandaslik', null, $viyanaYonlendirme],
            ['viyana', 'bosanma-tescili', null, $viyanaYonlendirme],
            ['viyana', 'adli-sicil', null, $viyanaYonlendirme],
            ['viyana', 'evlilik-tescili', null, $viyanaYonlendirme],

            // ============ Viyana Başkonsolosluğu (gerçek muhatap: Viyana/Aşağı Avusturya/Burgenland/Steiermark) ============
            ['viyana-bk', 'adres-kaydi', 'https://viyana-bk.mfa.gov.tr/Mission/ShowAnnouncement/402241', 'Genel T.C. kuralına göre (Viyana\'ya özgü doğrulanamadı) e-Devlet, şahsen veya posta ile yapılabiliyor; 20 iş günü zorunlu, geç bildirimde idari para cezası riski var. Ücret: nüfus işlemi olduğu için harca tabi değil.'],
            ['viyana-bk', 'ehliyet', 'https://viyana-bk.mfa.gov.tr/Mission/ShowInfoNote/415473', 'Randevu ŞART — "Randevusuz işlem kabul edilmemektedir." Başvuru sahibinin en az 6 ay önce bu temsilcilikte adres beyanında bulunmuş olması şart; yalnız kayıtlı olduğu temsilcilikte işlem yapılabiliyor.'],
            ['viyana-bk', 'noter-tasdik', 'https://viyana-bk.mfa.gov.tr/Mission/ShowInfoNote/415476', 'Randevu şart. Yabancı kimlik/pasaportla işlem için yeminli tercüme isteniyor; okuma-yazma bilmeyenler için vekaletnamede 2 tanık şartı var; evlilik nedeniyle ad/soyad değişmişse önce nüfusa tescil gerekiyor.'],
            ['viyana-bk', 'tercume-tasdiki', 'https://viyana-bk.mfa.gov.tr/Mission/ShowInfoNote/415476', 'Viyana bu işlemi ayrı sayfa yapmamış, noterlik notuyla birleştirmiş. Belge, Başkonsolosluğa KAYITLI yeminli tercümanlardan biri tarafından çevrilmiş olmalı; kayıtsız tercümanın çevirisi tasdik edilmiyor. Her belgeden 2 nüsha isteniyor.'],
            ['viyana-bk', 'vatandaslik', 'https://viyana-bk.mfa.gov.tr/Mission/ShowInfoNote/415509', 'Yalnız İZİNLE ÇIKMA bulundu (2 ayrı not: "Avusturya Vatandaşlığı Bulunanlar" ve "Teminat Belgesi (Bescheid) Alanlar" için ayrı süreç var; erkeklerden askerlik belgesi de isteniyor). YENİDEN KAZANMA ve EVLİLİK YOLUYLA için Viyana\'ya özgü sayfa bulunamadı.'],
            ['viyana-bk', 'bosanma-tescili', 'https://viyana-bk.mfa.gov.tr/Mission/ShowInfoNote/415513', 'ÖNEMLİ ÜLKEYE ÖZGÜ FARK: "Başkonsolosluğumuza sadece Avusturya mahkemelerinde boşanması kesinleşmiş vatandaşlarımız müracaat edebilir." Her iki taraf 90 gün içinde başvurmalı; yabancı taraf Türkçe bilmiyorsa resmî tercüman + noter tasdikli tercüme şart.'],
            ['viyana-bk', 'adli-sicil', 'https://viyana-bk.mfa.gov.tr/Mission/ShowInfoNote/415474', 'Randevu şart. Gerekli belgeler: TC kimlik kartı (Türk vatandaşları), Mavi Kart (Mavi Kart hamilleri), vekaletname (temsilci ile başvuruda).'],
            ['viyana-bk', 'evlilik-tescili', 'https://viyana-bk.mfa.gov.tr/Mission/ShowInfoNote/415512', 'Randevu şart. Avusturya\'ya özgü belge adları: İkamet Belgesi (Meldezettel) ve Evlenme Belgesi (Heiratsurkunde).'],
        ];
    }
}
