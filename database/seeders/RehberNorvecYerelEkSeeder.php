<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Norveç'in Oslo Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile AYNI
 * mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberNorvecYerelEkSeeder --force
 *
 * BERLİN'DEN FARKLI: Oslo bu hizmetleri kendisi sunuyor — konsolosluk.gov.tr
 * dropdown'unda Norveç için tek seçenek (value=291, doğrulandı).
 *
 * DİKKAT: Sayfalarda çok tekrarlayan "Toplam Masraf: 10.00" para birimsiz
 * rakam muhtemelen sistemin varsayılan/dolmamış alanı — güvenilir bir ücret
 * olarak SUNULMADI. Yalnız NOK etiketli tutarlar (450, 50) gerçek kabul
 * edildi.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberNorvecYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'NO')->get()->keyBy('slug');
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

        $this->command?->info("Norveç yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['oslo', 'adres-kaydi', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5056', 'POSTA İLE BAŞVURU KABUL EDİLİYOR — Norveç\'e özgü bir istisna: Skatteetaten/Folkeregisteret\'ten alınan imzalı-mühürlü "Bostedsattest" belgesi + adres beyan formu + kimlik fotokopisi postalanarak başvurulabiliyor. Posta adresi: Turkish Embassy in Oslo, Halvdan Svartes Gate 5, 0244 Oslo.'],
            ['oslo', 'ehliyet', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=16&procedureDetailId=5067', 'Randevu zorunlu, posta yok. Asıl mali yük ehliyet harcı değil, Türkiye\'den önceden yatırılması gereken "Türk Polis Teşkilatını Güçlendirme Vakfı" bağışı.'],
            ['oslo', 'noter-tasdik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=79', 'Randevu zorunlu, posta KABUL EDİLMİYOR — kanun gereği bizzat gidilerek yapılması zorunlu. Ücret sabit değil. Norveç Apostil Sözleşmesi\'ne taraf olduğundan, bu hizmet esas olarak Apostil kapsamayan durumlar içindir.'],
            ['oslo', 'tercume-tasdiki', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=5042', 'Randevu zorunlu. Tercümenin MUTLAKA Oslo Büyükelçiliği nezdinde yemin etmiş bir tercüman tarafından yapılmış olması şart — başka temsilcilikteki yeminli tercümanın çevirisi Oslo\'da tasdik ettirilemiyor.'],
            ['oslo', 'vatandaslik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=7&procedureDetailId=5048', 'İzinle çıkmada posta AÇIKÇA REDDEDİLİYOR. Yeniden kazanma ve evlilik yoluyla için ücret NOK 450 + NOK 10 (etiketli, doğrulanmış); ikisinde de posta reddediliyor.'],
            ['oslo', 'bosanma-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5075', 'Randevu var; posta için net bir ret/kabul ifadesi yok. Taraflardan biri Türk diğeri yabancıysa veya biri vefat etmişse tek taraflı başvuru kabul ediliyor; ayrı başvurularda aradaki süre 90 günü geçemez.'],
            ['oslo', 'adli-sicil', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=12&procedureDetailId=5059', 'Randevu zorunlu, şahsen. Norveç\'e özgü fark: belge 44 dilde düzenlenebiliyor ama listede NORVEÇÇE YOK (Danca/İsveççe var) — yerel kullanım için ayrıca yeminli tercüme gerekebilir.'],
            ['oslo', 'evlilik-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=3&procedureDetailId=5018', 'POSTA İLE BAŞVURU AÇIKÇA KABUL EDİLİYOR (dilekçe + evlenme belgesi aslı + kimlik). Ücret NOK 10 + posta NOK 50 (etiketli). Norveç, T.C. temsilciliklerinin nikah KIYAMADIĞI 13 ülke listesinde YOK — yani Oslo\'da bizzat nikah da kıyılabiliyor (ayrı bir süreç, "Dış Temsilcilikte Evlilik İçin Ön Başvuru").'],
        ];
    }
}
