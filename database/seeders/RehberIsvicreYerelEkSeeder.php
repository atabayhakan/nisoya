<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * İsviçre'nin Bern Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile AYNI
 * mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberIsvicreYerelEkSeeder --force
 *
 * BERLİN'DEN FARKLI: Bern bu 8 hizmeti GERÇEKTEN kendisi sunuyor — hiçbir
 * kategoride "bu hizmet burada yok, filanca başkonsolosluğa gidin" uyarısı
 * çıkmadı. İsviçre'de ayrıca Cenevre ve Zürih Başkonsoloslukları var
 * (konsolosluk.gov.tr dropdown'undan doğrulandı) ama onlar ayrı görev
 * bölgelerine bakıyor, Bern'i geçersiz kılmıyor.
 *
 * KAYNAK NOTU: Bern'in kendi sitesi (bern-be.mfa.gov.tr) konsolosluk işlemleri
 * için kendi sayfası yok, merkezi konsolosluk.gov.tr portalına yönlendiriyor
 * (oturumda İsviçre/Bern seçili haldeyken). Aşağıdaki URL'ler bu yüzden
 * genel portalın Bern-seçili adresleri; ziyaretçi doğrudan tıklarsa önce
 * sitede "İsviçre → Bern Büyükelçiliği" seçmesi gerekebilir.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberIsvicreYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'CH')->get()->keyBy('slug');
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

        $this->command?->info("İsviçre yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['bern', 'adres-kaydi', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5056', 'Bu temsilcilikte (Bern) randevu+şahsen ZORUNLU — posta yalnız ABD/Kanada/Avustralya/Rusya gibi geniş ülkelerde tanınıyor, İsviçre bu istisnada yok. 20 iş günü içinde bildirilmezse idari para cezası.'],
            ['bern', 'ehliyet', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=16&procedureDetailId=5067', 'Bu bir YENİLEME hizmeti (yeni ehliyet verilmiyor). Şart: Bern\'e kayıtlı adres beyanının başvurudan en az 6 ay önce yapılmış olması + İsviçre\'den alınıp yeminli tercümanca çevrilmiş sağlık raporu + adli sicil + kan grubu belgesi. Randevu zorunlu, posta yok. Vakıf payı yalnız Türkiye\'deki bankalardan T.C. kimlik no ile yatırılabiliyor.'],
            ['bern', 'noter-tasdik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=5041', 'Randevu+şahsen zorunlu, ücret sabit değil (işlem tipi/sayfa sayısına göre). KRİTİK: İsviçre 1961 Lahey Apostil Sözleşmesi\'ne taraf — İsviçre\'de düzenlenmiş bir belge için genelde kantonal apostil yeterli, konsolosluk tasdiki esas olarak apostil kapsamayan durumlar için. Ayrıca imza-mühür tasdiki YALNIZ belgenin düzenlendiği kantondan sorumlu temsilciliğe (Bern/Zürih/Cenevre) yapılabiliyor.'],
            ['bern', 'tercume-tasdiki', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=6&procedureDetailId=5042', 'Tasdik YALNIZ çevirmenin yemin ettiği temsilcilikte yapılabiliyor — tercüman Bern\'de yeminliyse tasdik de Bern\'de olmalı, Zürih/Cenevre\'de değil. Randevu zorunlu, ücret sabit değil.'],
            ['bern', 'vatandaslik', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=7&procedureDetailId=5048', 'Üç alt süreç de (izinle çıkma, yeniden kazanma, evlilik yoluyla) yalnız ikamet bölgesinden sorumlu temsilciliğe yapılabiliyor, POSTA KABUL EDİLMİYOR. Yeniden kazanma ve evlilik yoluyla için ücret CHF 10 + isteğe bağlı CHF 6 posta (kararın gönderimi için); evlilik yoluyla başvuruda eşlere ayrı ayrı ve birlikte mülakat yapılıyor, en az 3 yıl evlilik şartı var.'],
            ['bern', 'bosanma-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=2&procedureDetailId=5075', 'Randevu zorunlu, ücret 10 (CHF olduğu tahmin ediliyor, teyide açık). İsviçre boşanma hukuku federal (kantonal değil) olduğu için normal "eyalet sistemi" jurisdiction kısıtının uygulanmayabileceği, yani Bern/Zürih/Cenevre\'den herhangi birine başvurulabileceği değerlendiriliyor — bu bir çıkarımdır, teyit edilmeden kesin bilgi gibi sunulmamalı.'],
            ['bern', 'adli-sicil', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=12&procedureDetailId=5059', 'Belge 44 dilde (Almanca ve İngilizce dahil) düzenlenebiliyor. Randevu ile şahsen/vekil VEYA online.konsolosluk.gov.tr üzerinden çevrimiçi başvuru. Ücret 10 (para birimi teyide açık).'],
            ['bern', 'evlilik-tescili', 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedureDetail/?procedureId=3&procedureDetailId=5018', 'POSTA İLE BAŞVURU AÇIKÇA MÜMKÜN (diğer kategorilerin çoğunun aksine). Belge "yerel nitelikte" ise yalnız evliliğin geçtiği bölgeden sorumlu temsilciliğe, "uluslararası nitelikte" (çok dilli Formül B) ise ülkedeki tüm temsilciliklere başvurulabiliyor. Ücret CHF 10 + isteğe bağlı Uluslararası Aile Cüzdanı CHF 23. Bildirim süresi: yabancı makamdan belge alındıktan sonra 30 gün.'],
        ];
    }
}
