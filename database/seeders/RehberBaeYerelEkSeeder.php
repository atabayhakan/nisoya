<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Birleşik Arap Emirlikleri'nin Abu Dabi Büyükelçiliği'ne "yerel ek" işler —
 * Almanya/ABD ile AYNI mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 * DOMAIN NOTU: Doğru domain abudhabi-be.mfa.gov.tr ("abudabi-be" DEĞİL, DNS
 * hatası veriyor).
 *
 *     php artisan db:seed --class=RehberBaeYerelEkSeeder --force
 *
 * JURISDICTION NETLİĞİ: Abu Dabi Büyükelçiliği'nin Konsolosluk Şubesi
 * Abu Dabi Emirliği + Al Ain'e bakıyor; Dubai + Kuzey Emirlikleri (Şarika,
 * Acman, Ümmü'l-Kayveyn, Resü'l-Hayme, Füceyre) Dubai Başkonsolosluğu'na
 * (dubai-bk.mfa.gov.tr) ait — büyükelçiliğin kendi duyurusu bu ayrımı
 * açıkça teyit ediyor.
 *
 * VERİ KALİTESİ RİSKİ: Abu Dabi'nin kendi sayfaları ESKİ (2008-2016) —
 * Dubai'nin çok daha güncel (2022-2026) duyuruları farklı/daha katı kurallar
 * gösteriyor (randevu zorunluluğu, yeni cezalar). Bu yüzden Dubai'nin
 * GÜNCEL ama farklı-bölgeye-ait verisi buraya AKTARILMADI — yalnız Abu
 * Dabi'nin kendi (muhtemelen güncelliğini yitirmiş) sayfaları kullanıldı,
 * bu açıkça notlarda belirtildi.
 *
 * ÖNEMLİ: BAE 1961 Lahey Apostil Sözleşmesi'ne TARAF DEĞİL (HCCH resmi
 * tablosuyla doğrulandı) — apostil yerine konsolosluk tasdik zinciri geçerli.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberBaeYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'AE')->get()->keyBy('slug');
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

        $this->command?->info("BAE yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['abu-dabi', 'adres-kaydi', 'https://abudhabi-be.mfa.gov.tr/Mission/ShowAnnouncement/401622', 'Randevu GEREKMİYOR — şahsen başvuruyla yapılabiliyor (TC Kimlik/Pasaport/Ehliyet yeterli). ⚠️ Kaynak 2023 tarihli; Dubai Başkonsolosluğu\'nun çok daha yeni (29.12.2025) duyurusu artık randevu ZORUNLU diyor ve 01.01.2026\'dan itibaren geç/yanlış beyan için idari para cezası getirmiş (814 TL / 17.051 TL) — bu güncelleme Abu Dabi\'nin kendi sitesinde henüz yok, teyit edilmeli.'],
            ['abu-dabi', 'ehliyet', 'https://abudhabi-be.mfa.gov.tr/Mission/ShowAnnouncement/248854', 'ÖNEMLİ BULGU: Abu Dabi Büyükelçiliği ehliyet YENİLEME işlemi YAPMIYOR — vatandaşların EGM\'nin randevu sisteminden (surucurandevu.egm.gov.tr) randevu alıp Türkiye\'deki Trafik Şube Müdürlüklerine bizzat başvurması gerekiyor. ⚠️ Kaynak 2016 tarihli, güncelliği doğrulanamadı; ama Dubai\'nin (farklı bölge!) bu hizmeti aktif sunduğu görüldü — Abu Dabi sakinlerinin Dubai\'ye başvurup başvuramayacağı doğrulanamadı, büyükelçilikle teyit gerekir.'],
            ['abu-dabi', 'noter-tasdik', 'https://abudhabi-be.mfa.gov.tr/Mission/ShowAnnouncement/115763', 'Yalnız VEKALETNAME süreci belgeli (genel imza/suret tasdiki sayfası yok): önce vekaletname konusu + kimlik bilgisi tcadbe@eim.ae adresine e-postayla gönderiliyor, taslak onaylandıktan sonra randevu veriliyor. ⚠️ Kaynak 2008 tarihli, güncelliği doğrulanamadı.'],
        ];
    }
}
