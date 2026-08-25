<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * "Ana şablon + yerel ek" mimarisinin (plan §3.1) YEREL EK kısmı — Almanya'nın
 * 14 temsilciliğine bugüne kadar hep AYNI içerik gitmişti (RehberAlmanyaDogrulamaSeeder,
 * tek $veri, foreach ile hepsine). Oysa 2026-08-26'daki 7 paralel araştırma
 * SIRASINDA bazı temsilciliklere özel somut bilgiler de bulunmuştu (hangi
 * şehrin kendi resmî bilgi notu, randevu gerekip gerekmediği gibi) — bunlar
 * o zaman kullanılmadı, genel $veri'ye gömülmedi. Bu seeder SADECE o zaten
 * doğrulanmış, somut bilgileri ilgili (temsilcilik, işlem türü) çiftine işler.
 *
 *     php artisan db:seed --class=RehberAlmanyaYerelEkSeeder --force
 *
 * KASITLI SINIRLAMA: yalnız BUGÜNKÜ araştırmadan zaten elde somut kaynağı
 * olan (temsilcilik, tür) çiftlerine dokunuyor — Frankfurt/Karlsruhe/Essen/
 * Mainz/Münih/Münster/Nürnberg/Büyükelçilik için o gün özel bir şehir sayfası
 * bulunmamıştı, YENİ ARAŞTIRMA OLMADAN buraya bir şey uydurulmadı.
 *
 * STATUS'A DOKUNULMAZ: kayıtlar artık (2026-08-26, sahibin kararıyla) yayında
 * — bu tur içeriği İYİLEŞTİRİYOR, geri taslağa düşürmüyor.
 */
class RehberAlmanyaYerelEkSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = Temsilcilik::query()->where('country_code', 'DE')->get()->keyBy('slug');
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

            $kayit->resmi_kaynak_url = $kaynakUrl;
            if ($notEki !== null && ! str_contains((string) $kayit->notlar, $notEki)) {
                $kayit->notlar = trim((string) $kayit->notlar).' '.$notEki;
            }
            $kayit->save();
            $guncellenen++;
        }

        $this->command?->info("Yerel ek işlendi: {$guncellenen} kayıt.");
    }

    /**
     * [temsilcilik_slug, islem_turu_slug, resmi_kaynak_url, notlara_eklenecek_cumle|null]
     *
     * Kaynak: 2026-08-26'daki 7 paralel araştırmanın agent raporlarında geçen,
     * o günden beri hiç kullanılmamış şehir-özel bulgular. Her URL o araştırma
     * turunda gerçekten ziyaret edilip okunmuştu (bkz. RehberAlmanyaDogrulamaSeeder
     * commit mesajları).
     *
     * @return list<array{0: string, 1: string, 2: string, 3: ?string}>
     */
    private function yerelEkler(): array
    {
        return [
            // Berlin Başkonsolosluğu
            ['berlin', 'adres-kaydi', 'https://berlin-bk.mfa.gov.tr/Mission/ShowInfoNote/414042', null],
            ['berlin', 'ehliyet', 'https://berlin-bk.mfa.gov.tr/Mission/ShowInfoNote/413722', null],
            ['berlin', 'vatandaslik', 'https://berlin-bk.mfa.gov.tr/Mission/ShowInfoNote/413729', 'Bu URL özellikle "evlilik yoluyla" alt sürecinin Berlin bilgi notudur.'],
            ['berlin', 'bosanma-tescili', 'https://berlin-bk.mfa.gov.tr/Mission/ShowInfoNote/413733', null],
            ['berlin', 'tercume-tasdiki', 'https://berlin-bk.mfa.gov.tr/Mission/ShowInfoNote/413735', null],

            // Köln Başkonsolosluğu — randevu GEREKMEZ (noter-tasdik + tercüme tasdiki)
            ['koeln', 'adres-kaydi', 'https://koln-bk.mfa.gov.tr/Mission/ShowAnnouncement/401480', null],
            ['koeln', 'noter-tasdik', 'https://koln-bk.mfa.gov.tr/Mission/ShowInfoNote/374151', 'Bu temsilcilikte (Köln) randevu GEREKMEZ — kendi sayfası bunu açıkça belirtiyor.'],
            ['koeln', 'tercume-tasdiki', 'https://koln-bk.mfa.gov.tr/Mission/ShowInfoNote/374011', 'Bu temsilcilikte (Köln) randevu GEREKMEZ.'],
            ['koeln', 'adli-sicil', 'https://koln-bk.mfa.gov.tr/Mission/ShowInfoNote/374010', null],

            // Düsseldorf Başkonsolosluğu — randevu GEREKMEZ (aynı ikisi)
            ['duesseldorf', 'ehliyet', 'https://dusseldorf-bk.mfa.gov.tr/Mission/ShowInfoNote/395036', null],
            ['duesseldorf', 'noter-tasdik', 'https://dusseldorf-bk.mfa.gov.tr/Mission/ShowInfoNote/363475', 'Bu temsilcilikte (Düsseldorf) randevu GEREKMEZ.'],
            ['duesseldorf', 'tercume-tasdiki', 'https://dusseldorf-bk.mfa.gov.tr/Mission/ShowInfoNote/363475', 'Bu temsilcilikte (Düsseldorf) randevu GEREKMEZ.'],

            // Hannover Başkonsolosluğu
            ['hannover', 'vatandaslik', 'https://hannover-bk.mfa.gov.tr/Mission/ShowInfoNote/335029', 'Bu URL özellikle "izinle çıkma" alt sürecinin Hannover bilgi notudur.'],
            ['hannover', 'bosanma-tescili', 'https://hannover-bk.mfa.gov.tr/Mission/ShowInfoNote/369270', null],
            ['hannover', 'tercume-tasdiki', 'https://hannover-bk.mfa.gov.tr/Mission/ShowInfoNote/369270', 'Bu temsilciliğin yeminli tercüman listesi: hannover-bk.mfa.gov.tr/Mission/ShowInfoNote/341870.'],

            // Hamburg Başkonsolosluğu
            ['hamburg', 'noter-tasdik', 'https://hamburg-bk.mfa.gov.tr/Mission/ShowInfoNote/354083', null],
            ['hamburg', 'tercume-tasdiki', 'https://hamburg-bk.mfa.gov.tr/Mission/ShowInfoNote/354083', 'Bu temsilcilikte görülen örnek ücretler (kaynak tarihi eski olabilir, 2019 civarı): vekaletname 36€\'dan, tercüme tasdiki 17€/sayfadan başlıyor — güncel tutarı mutlaka bu sayfadan teyit edin.'],

            // Stuttgart Başkonsolosluğu
            ['stuttgart', 'ehliyet', 'https://stuttgart-bk.mfa.gov.tr/Mission/ShowInfoNote/409079', null],
        ];
    }
}
