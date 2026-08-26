<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Azerbaycan'ın Bakü Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile
 * AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberAzerbaycanYerelEkSeeder --force
 *
 * JURISDICTION NOTU: Azerbaycan'da T.C.'nin 4 temsilciliği var — Bakü
 * Büyükelçiliği, Gence Başkonsolosluğu (tam teşekküllü), Nahçıvan
 * Başkonsolosluğu (tam teşekküllü), Lenkeran Fahri Konsolosluğu (sınırlı
 * yetki). Bir bilgi notunda "başvurularda görev bölgesi ayrımı olduğundan,
 * vatandaşlarımızın yalnızca bağlı oldukları temsilciliklerce işlemleri
 * gerçekleştirilebilecektir" ibaresi geçiyor — yani Gence/Nahçıvan
 * bölgesine kayıtlı vatandaşlar Bakü'ye değil kendi şehirlerine gitmeli.
 * Bu Berlin'deki "yanlış muhatap" değil, Almanya/ABD tarzı coğrafi bölünme.
 *
 * VERİ KALİTESİ NOTU: Kategori 3/4/5/6 (noter tasdik, tercüme tasdik,
 * vatandaşlık, boşanma tescili) için Bakü'ye özel dedike sayfa/PDF
 * bulunamadı — genel konsolosluk.gov.tr prosedür sayfalarına ve Bakü'nün
 * kendi iletişim hattına dayanıyor. Hiçbir kategoride AZN cinsinden kesin
 * ücret doğrulanamadı (yalnız ehliyet için ülke-geneli 2026 EUR tarifesi).
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberAzerbaycanYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'AZ')->get()->keyBy('slug');
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

        $this->command?->info("Azerbaycan yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['baku', 'adres-kaydi', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedure/2', 'Randevu+şahsen zorunlu — Azerbaycan, postayla başvuruya izin verilen ülke (ABD/Kanada/Avustralya/Rusya) listesinde YOK. AYRICA (farklı bir konu): Azerbaycan\'ın kendi kuralı gereği 15 günden fazla kalacak herkes Azerbaycan Devlet Göç İdaresi\'ne de adres bildirmek zorunda — yapılmazsa çıkışta 400 Manat ceza + ~1 yıl giriş yasağı.'],
            ['baku', 'ehliyet', 'https://baku-be.mfa.gov.tr/Mission/ShowAnnouncement/408422', 'Yeni başvuru için randevu şart, ama BELGE TESLİMİ randevusuz yapılabiliyor (09:00-12:00/14:00-17:00). Ücret: 34 EUR (2026 ülke-geneli tarife, 2016 sonrası düzenlenen belgeler için) — başvurudan en az 6 ay önce adres beyanında bulunmuş olma şartı var.'],
            ['baku', 'noter-tasdik', null, 'Vekaletname/azilname/muvafakatname/tercüme tasdiki dahil üç alt kalem randevu+şahsen gerektiriyor. Bakü irtibat hattı: +994 12 444 73 46 (Pasaport-Noter İşlemleri, 14:00-17:00). Ücret Bakü\'ye özel olarak doğrulanamadı.'],
            ['baku', 'tercume-tasdiki', null, 'Yeminli tercümanca yapılmış çevirilerin tasdiki randevu+şahsen gerektiriyor. Bakü\'ye özel ücret bulunamadı; bölgede kayıtlı tercüman aramak için https://www.konsolosluk.gov.tr/TranslatorSearch/Index kullanılabiliyor.'],
            ['baku', 'vatandaslik', null, 'İzinle çıkma, yeniden kazanma, evlilik yoluyla kazanma dahil tüm alt süreçler randevu+şahsen. Bakü irtibat hattı: +994 12 444 73 44 (Vatandaşlık İşlemleri, 14:00-17:00). Ücret güvenilir şekilde doğrulanamadı.'],
            ['baku', 'bosanma-tescili', null, 'Randevu+şahsen gerekiyor. Bakü\'ye özel ayrı bir duyuru bulunamadı; genel nüfus/konsolosluk hattı +994 12 444 73 43 üzerinden bilgi alınabilir.'],
            ['baku', 'adli-sicil', 'https://baku-be.mfa.gov.tr/Mission/ShowAnnouncement/412956', 'ÖNEMLİ FARK: Büyükelçilikte FİZİKSEL APOSTİL HİZMETİ YOK — "apostil hizmeti büyükelçilikte verilmiyor, bu işlem Türkiye\'deki kaymakamlıklarca yapılıyor." Alternatif: www.eapostil.gov.tr veya turkiye.gov.tr üzerinden E-APOSTİL başvurusu, büyükelçiliğe hiç gitmeden.'],
            ['baku', 'evlilik-tescili', 'https://baku-be.mfa.gov.tr/Mission/ShowAnnouncement/412956', 'POSTA İLE BAŞVURU KABUL EDİLİYOR. Bakü\'nün kendi SSS\'sine göre: büyükelçilikte yalnız İKİ Türk vatandaşı arasında nikah merasimi yapılıyor; Azerbaycan vatandaşı veya üçüncü ülke vatandaşıyla evlilikte YALNIZ TESCİL (bildirim) yapılıyor. Uluslararası Evlenme Cüzdanı teslimi randevusuz (09:00-12:00/14:00-17:00).'],
        ];
    }
}
