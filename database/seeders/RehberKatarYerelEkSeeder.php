<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Katar'ın Doha Büyükelçiliği'ne "yerel ek" işler — Almanya/ABD ile AYNI
 * mimari, 2026-08-27 paralel araştırma turunda toplandı.
 *
 *     php artisan db:seed --class=RehberKatarYerelEkSeeder --force
 *
 * BERLİN'DEN FARKLI: Doha Katar'daki TEK T.C. temsilciliği ve konsolosluk
 * hizmetlerini doğrudan kendisi veriyor (ayrı başkonsolosluk yok). DİKKAT:
 * `doha.mfa.gov.ct.tr` diye bir site KKTC'nin Doha temsilciliğidir, T.C.
 * ile karıştırılmamalı.
 *
 * VERİ KALİTESİ NOTU: Büyükelçiliğin resmi "Bilgi Notları" listesi çok dar
 * (yalnız 7 başlık) — 8 kategoriden yalnız Evlenme Bildirimi tam ve güncel
 * (2016) işlenmiş. Sürücü Belgesi notu 2012 tarihli ve dar kapsamlı. Diğer
 * 5 kategori için Doha'ya özel sayfa bulunamadı.
 *
 * ÖNEMLİ: Katar 1961 Lahey Apostil Sözleşmesi'ne TARAF DEĞİL (İçişleri
 * Bakanlığı + HCCH resmi kaynaklarıyla çapraz doğrulandı) — apostil yerine
 * konsolosluk tasdik zinciri geçerli.
 *
 * STATUS BİLİNÇLİ OLARAK TASLAK BIRAKILDI (Almanya turunun yöntemi):
 * bu 23 ülkelik yeni tur için ABD turundaki gibi açık bir "doğrudan yayına
 * al" kararı alınmadı, bu yüzden güvenli varsayılan seçildi — panelden
 * gözden geçirilip onaylanmayı bekliyor.
 */
class RehberKatarYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'QA')->get()->keyBy('slug');
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

        $this->command?->info("Katar yerel ek (taslak): {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            ['doha', 'ehliyet', 'https://doha-be.mfa.gov.tr/Mission/ShowAnnouncement/163932', 'Bu not "yenileme" değil, Katar ehliyetinin Türk ehliyetine DÖNÜŞTÜRÜLMESİ sürecini anlatıyor. Katar makamlarıyla varılan mutabakat sonrası doğrulama sorgusu artık yalnız aslın zayi/tahrif/sahte şüphesi durumunda yapılıyor. ⚠️ Kaynak 2012 tarihli, güncelliği doğrulanamadı.'],
            ['doha', 'noter-tasdik', 'https://doha-be.mfa.gov.tr/Mission/Contact', 'Büyükelçiliğin ayrı bir "Tasdik/Attestation Hizmetleri" çalışma saati bandı var (08:00-12:00, genel Konsolosluk Hizmetleri saatinden — 08:00-14:00 — farklı ve dar). Randevu genel kuralla gerekli.'],
            ['doha', 'evlilik-tescili', 'https://doha-be.mfa.gov.tr/Mission/ShowInfoNote/207256', 'ÜLKEYE ÖZGÜ SOMUT FARK: evlilik belgesinin Katar Dışişleri Bakanlığı\'ndan ONAYLI ASLI ve yeminli tercümanca yapılmış tercümesi şart — apostil yerine tasdik zincirinin somut uygulaması. Türk kadın vatandaş boşanmanın kesinleşmesinden itibaren 300 gün (iddet müddeti) içinde yeniden evlenecekse, bu süreyi kaldıran mahkeme kararı gerekiyor.'],
        ];
    }
}
