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
 * KASITLI SINIRLAMA: yalnız somut kaynağı olan (temsilcilik, tür) çiftlerine
 * dokunuyor. İKİNCİ TUR (aynı gün, 7 ayrı ajan): Frankfurt/Karlsruhe/Essen/
 * Mainz/Münih/Münster/Nürnberg de eklendi — yalnız Berlin Büyükelçiliği
 * (Başkonsolosluğun aksine) hâlâ araştırılmadı, kasıtlı olarak dışarıda.
 * Essen'in canlı randevu sisteminde bir oturum/cache hatası gözlendi (alt
 * sayfa tıklamaları başka bir temsilciliğin verisini gösterdi) — o yüzden
 * Essen için yalnız ANA LİSTEDEN doğrulanan randevu/posta bilgisi kullanıldı,
 * detay sayfalarındaki (muhtemelen yanlış) evrak/ücret bilgisi ALINMADI.
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

            if ($kaynakUrl !== null) {
                $kayit->resmi_kaynak_url = $kaynakUrl;
            }
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
     * @return list<array{0: string, 1: string, 2: ?string, 3: ?string}>
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

            // --- 2026-08-26, ikinci tur: kalan 7 temsilcilik (7 paralel ajan) ---

            // Frankfurt Başkonsolosluğu — adres kaydı randevusuz (Köln/Düsseldorf'a üçüncü katılan)
            ['frankfurt', 'adres-kaydi', 'https://frankfurt-bk.mfa.gov.tr/Mission/ShowInfoNote/366596', 'Bu temsilcilikte (Frankfurt) randevu GEREKMEZ — danışma masasında hafta içi 09:00-13:30 ve 14:00-16:30 arası.'],
            ['frankfurt', 'noter-tasdik', 'https://frankfurt-bk.mfa.gov.tr/Mission/ShowInfoNote/353277', null],
            ['frankfurt', 'tercume-tasdiki', 'https://frankfurt-bk.mfa.gov.tr/Mission/ShowInfoNote/364237', null],
            ['frankfurt', 'vatandaslik', 'https://frankfurt-bk.mfa.gov.tr/Mission/ShowInfoNote/353280', null],
            ['frankfurt', 'bosanma-tescili', 'https://frankfurt-bk.mfa.gov.tr/Mission/ShowInfoNote/353356', null],
            ['frankfurt', 'adli-sicil', 'https://frankfurt-bk.mfa.gov.tr/Mission/ShowAnnouncement/397639', 'Bu temsilcilik (Frankfurt) belgeyi 29 dilde düzenleyebiliyor (2022 itibarıyla).'],
            ['frankfurt', 'evlilik-tescili', 'https://frankfurt-bk.mfa.gov.tr/Mission/ShowInfoNote/353358', null],

            // Karlsruhe Başkonsolosluğu — adres kaydı VE e-Devlet şifresi randevusuz
            ['karlsruhe', 'adres-kaydi', 'https://karlsruhe-bk.mfa.gov.tr/Mission/ShowAnnouncement/401287', 'Bu temsilcilikte (Karlsruhe) randevu GEREKMEZ (09:00-15:00) — aynı saatlerde e-Devlet şifresi de randevusuz alınabiliyor.'],
            ['karlsruhe', 'noter-tasdik', 'https://karlsruhe-bk.mfa.gov.tr/Mission/ShowAnnouncement/250826', 'Bu temsilcilikte (Karlsruhe) randevu KESİNLİKLE gerekli — her başvuru sahibi için ayrı randevu alınmalı.'],
            ['karlsruhe', 'tercume-tasdiki', null, 'Bu temsilcilikte (Karlsruhe) çeviriyi yapan tercümanın Karlsruhe\'de yeminli olması şart, randevu gereklidir.'],
            ['karlsruhe', 'adli-sicil', null, 'Bu temsilcilikte (Karlsruhe) e-Devlet şifresi henüz yoksa randevusuz şahsen temin edilebiliyor (09:00-15:00); şifre alındıktan sonra adli sicil kaydı da e-Devlet üzerinden randevusuz sorgulanır.'],

            // Essen Başkonsolosluğu — TERSİNE istisna: evlilik bildirimi postayla, randevusuz
            ['essen', 'evlilik-tescili', null, 'Bu temsilcilikte (Essen) POSTAYLA başvuru yeterli, randevu gerekmiyor — diğer 7 kategorinin aksine.'],

            // Mainz Başkonsolosluğu
            ['mainz', 'adres-kaydi', 'https://mainz-bk.mfa.gov.tr/Mission/ShowAnnouncement/401541', null],
            ['mainz', 'noter-tasdik', 'https://mainz-bk.mfa.gov.tr/Mission/ShowAnnouncement/250915', 'Bu temsilcilikte (Mainz) randevu GEREKLİ (2016\'dan beri noterlik/nüfus/vatandaşlık/askerlik işlemleri randevuya bağlı).'],

            // Münih Başkonsolosluğu — iki görev-bölgesi/ikamet şartı
            ['muenchen', 'adres-kaydi', 'https://munih-bk.mfa.gov.tr/Mission/ShowAnnouncement/401541', null],
            ['muenchen', 'ehliyet', null, 'Münih\'te Alman ehliyetine ÇEVİRME (konsolosluğun yapmadığı işlem) için yerel yetkili makam: KVR Führerscheinstelle, Eichstätter Str. 2.'],
            ['muenchen', 'bosanma-tescili', 'https://munih-bk.mfa.gov.tr/Mission/ShowInfoNote/368235', 'Bu temsilcilik (Münih) YALNIZ Oberbayern/Niederbayern/Schwaben bölgesindeki Alman mahkemesi kararlarını tescil eder — başka bölgeden ise doğru temsilciliğe başvurulmalı.'],
            ['muenchen', 'evlilik-tescili', 'https://munih-bk.mfa.gov.tr/Mission/ShowInfoNote/390492', 'Bu temsilcilikte (Münih) taraflardan en az birinin Münih konsolosluk görev bölgesinde ikamet etmesi şart.'],

            // Münster Başkonsolosluğu — noter+tercüme randevusuz (dördüncü şehir)
            ['muenster', 'adres-kaydi', 'https://munster-bk.mfa.gov.tr/Mission/ShowAnnouncement/401541', null],
            ['muenster', 'ehliyet', 'https://munster-bk.mfa.gov.tr/Mission/ShowInfoNote/407481', 'Bu temsilcilikte (Münster) BAŞVURU için randevu gerekli, ama HAZIR ehliyetin TESLİM ALINMASI randevusuz yapılabiliyor (09:00-12:00 / 13:00-16:00).'],
            ['muenster', 'noter-tasdik', null, 'Bu temsilcilikte (Münster) İmza/Mühür Tasdiki için randevu GEREKMEZ.'],
            ['muenster', 'tercume-tasdiki', null, 'Bu temsilcilikte (Münster) randevu GEREKMEZ.'],
            ['muenster', 'bosanma-tescili', 'https://munster-bk.mfa.gov.tr/Mission/ShowInfoNote/389711', 'Bu temsilciliğin (Münster) giriş katındaki bekleme salonunda fotoğraf çekme kabini ve fotokopi makinesi var.'],

            // Nürnberg Başkonsolosluğu
            ['nuernberg', 'vatandaslik', null, 'Bu temsilcilikte (Nürnberg) izinle çıkma başvurusunda Alman vatandaşlık belgesinin (Einbürgerungsurkunde/-zusicherung) TÜRKÇE TERCÜMESİNE GEREK YOK; reşit olmayan çocuk için muvafakatname noter servisinden AYRI bir randevu gerektiriyor.'],
            ['nuernberg', 'bosanma-tescili', null, 'Bu temsilcilikte (Nürnberg) çeviri MUTLAKA kayıtlı yeminli tercümandan olmalı — kayıtlı tercüman listesi kendi sayfasında yayında (Nürnberg/Bayreuth/Bamberg/Hof/Würzburg bölgesini kapsıyor).'],
        ];
    }
}
