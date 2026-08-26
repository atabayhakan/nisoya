<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * İsveç'in Stockholm Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile
 * AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberIsvecYerelEkSeeder --force
 *
 * BERLİN'DEN FARKLI: Stockholm Büyükelçiliği Konsolosluk Şubesi bu 8
 * hizmeti GERÇEKTEN kendisi sunuyor. İsveç'teki diğer iki temsilcilik
 * (Göteborg, Malmö) FAHRİ başkonsolosluk — pasaport/vize/nüfus işlemi
 * yapamıyorlar, yalnız periyodik gezici konsolosluk ziyaretleri alıyorlar.
 * Yani Stockholm tek tam-hizmet noktası.
 *
 * PRATİK DETAY: Konsolosluk Şubesi'nin başvuru adresi (Laboratoriegatan 16)
 * büyükelçilik binasından (Dag Hammarskjölds Väg 20) FARKLI — rehberde
 * bu ayrım netleştirilmeli.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberIsvecYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'SE')->get()->keyBy('slug');
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

        $this->command?->info("İsveç yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['stockholm', 'adres-kaydi', 'https://stokholm-be.mfa.gov.tr/Mission/ShowInfoNote/339731', 'Bu temsilcilikte (Stockholm) POSTA ile başvuru kabul ediliyor (Form B ile). 20 iş günü içinde bildirilmezse 155 SEK gecikme ücreti; posta ile ödemede Bank-Giro 5505-5859, nakit kabul edilmiyor.'],
            ['stockholm', 'ehliyet', 'https://stokholm-be.mfa.gov.tr/Mission/ShowInfoNote/381577', 'MÜRACAATIN ŞAHSEN YAPILMASI MECBURİDİR, posta yok. Ücret 490 SEK (445 değerli kağıt + 45 taşıma) + Türkiye\'den ayrıca yatırılan vakıf payı. İsveç\'ten alınan sağlık raporu (kan grubu belirtilmeli) yeminli tercümanla çevrilmeli; adres beyanının başvurudan en az 6 ay önce yapılmış olması şart.'],
            ['stockholm', 'noter-tasdik', 'https://stokholm-be.mfa.gov.tr/Mission/ShowInfoNote/339733', 'Ücret vekaletin türü/sayfa sayısına göre değişiyor, sabit rakam yok. Apostilli İsveç noteri belgeleri ek konsolosluk onayına gerek duymuyor; apostilsiz olanlar önce İsveç Dışişleri Bakanlığınca tasdik edilmeli. Tapu/vasiyetname/evlat edinme gibi işlemler mutlaka Konsolosluğa bizzat gidilerek yapılmalı.'],
            ['stockholm', 'tercume-tasdiki', 'https://stokholm-be.mfa.gov.tr/Mission/ShowInfoNote/339736', 'POSTA İLE BAŞVURU AÇIKÇA KABUL EDİLİYOR. Ücret: ilk sayfa 210 SEK, ek sayfa 80 SEK; posta ile toplam 150 SEK (Bank-Giro 5505-5859). Yalnız büyükelçiliğe kayıtlı yeminli tercümanların çevirisi kabul ediliyor (Stockholm + Göteborg listesi mevcut).'],
            ['stockholm', 'vatandaslik', 'https://stokholm-be.mfa.gov.tr/Mission/ShowInfoNote/339730', 'İzinle çıkma ve yeniden alınmada "MÜRACAATIN ŞAHSEN YAPILMASI MECBURİDİR" (posta yok). Evlilik yoluyla kazanmada eşlerin BİRLİKTE mülakat için randevu alması gerekiyor (en az 3 yıl evlilik şartı, e-posta: konsolosluk.stokholm@mfa.gov.tr). Tüm İsveç belgeleri (Bevis om Svenskt Medborgarskap, Personbevis, adli sicil, evlilik belgesi) İngilizce Apostil taşımalı.'],
            ['stockholm', 'bosanma-tescili', 'https://stokholm-be.mfa.gov.tr/Mission/ShowInfoNote/353013', 'Başvurular ŞAHSEN kabul ediliyor, posta yok. Ücret yalnız tercüme tasdiki üzerinden: 210 SEK/sayfa + 80 SEK ek sayfa. Taraflardan biri ölmüş/yabancı/Mavi Kart sahibiyse diğer taraf tek başına başvurabiliyor; ayrı başvurularda iki müracaat arası en fazla 90 gün olabilir.'],
            ['stockholm', 'evlilik-tescili', 'https://stokholm-be.mfa.gov.tr/Mission/ShowInfoNote/339727', 'Başvuru ŞAHSEN VEYA POSTA ile yapılabiliyor (posta ücreti 150 SEK, Bank-Giro 5505-5859). Skatteverket\'ten alınan "Vigselbevis" (veya "Vigsel Intyg") ve "Personbevis-Utdrag" (120 ilişki türü) isteniyor; kadın taraf 300 gün iddet süresi dolmadan evlenmişse mahkeme kararı gerekiyor.'],
        ];
    }
}
