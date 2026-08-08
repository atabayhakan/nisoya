<?php

namespace App\Console\Commands;

use App\Models\OutreachTarget;
use App\Support\Growth\RegionPolicy;
use App\Support\Growth\UlkeTespiti;
use Illuminate\Console\Command;

/**
 * Havuzdaki yanlış `country` değerlerini adresten/alan adından yeniden hesaplar
 * ve `marketing_status`'ü tazeler.
 *
 *   php artisan growth:ulke-duzelt              # YALNIZ SAYAR (varsayılan)
 *   php artisan growth:ulke-duzelt --uygula     # gerçekten yazar
 *
 * ---------------------------------------------------------------------------
 * VARSAYILAN KURU KOŞU — BİLİNÇLİ
 *
 * Üretimde veri değiştiren bir komutun varsayılanı "yaz" olamaz. Önce kaç
 * kaydın, hangi yönde değişeceği görülür; yazma ayrı ve açık bir karardır.
 *
 * KAPALI DÜŞER: `UlkeTespiti` kanıt bulamazsa null döner ve kayda DOKUNULMAZ.
 * Döngü "eşleşme varsa güncelle" biçiminde kurulur — "eşleşme yoksa atla"
 * biçiminde DEĞİL; ikincisi tek bir olumsuzlama kaybında (kabuk kaçışı, ters
 * çevrilen koşul) tüm tabloyu tarar.
 *
 * `updated_at` kurtarma tutamağıdır: yanlış bir koşu olursa etkilenen kayıtlar
 * `where('updated_at', '>=', <koşu zamanı>)` ile bulunur.
 */
class GrowthUlkeDuzelt extends Command
{
    protected $signature = 'growth:ulke-duzelt
        {--uygula : Sayma, gerçekten yaz (varsayılan yalnız sayar)}
        {--limit=0 : Yalnız ilk N kaydı işle (0 = hepsi)}';

    protected $description = 'Keşif havuzundaki yanlış ülke değerlerini adresten düzeltir, gönderim iznini tazeler.';

    public function handle(): int
    {
        $uygula = (bool) $this->option('uygula');
        $limit = (int) $this->option('limit');

        $sorgu = OutreachTarget::query()->orderBy('id');
        if ($limit > 0) {
            $sorgu->limit($limit);
        }

        $degisecek = [];
        $kanitsiz = 0;
        $dogru = 0;

        foreach ($sorgu->get() as $hedef) {
            $tespit = UlkeTespiti::tespit($hedef->city, $hedef->website, $hedef->contact_email);

            if ($tespit === null) {
                $kanitsiz++;

                continue;
            }

            if ($tespit === $hedef->country) {
                $dogru++;

                continue;
            }

            $degisecek[] = [
                'id' => $hedef->id,
                'ad' => (string) $hedef->name,
                'adres' => (string) $hedef->city,
                'eski' => (string) $hedef->country,
                'yeni' => $tespit,
                'eski_izin' => (string) $hedef->marketing_status,
                'yeni_izin' => RegionPolicy::marketingStatus($tespit),
            ];
        }

        $this->newLine();
        $this->components->twoColumnDetail('Taranan kayıt', (string) ($kanitsiz + $dogru + count($degisecek)));
        $this->components->twoColumnDetail('Ülkesi zaten doğru', (string) $dogru);
        $this->components->twoColumnDetail('Kanıt yok — DOKUNULMADI', (string) $kanitsiz);
        $this->components->twoColumnDetail('<fg=yellow>Değişecek</>', (string) count($degisecek));

        // İZİN YÖNÜ AYRI SAYILIR. "Kaç kayıt değişti" tek başına yetmez:
        // asıl önemli olan kaç kaydın gönderime KAPANDIĞI (düzeltmenin amacı)
        // ve kaçının AÇILDIĞI (bu ters yön, dikkatle bakılmalı).
        $kapanan = array_filter($degisecek, fn ($d) => $d['eski_izin'] === RegionPolicy::ALLOWED && $d['yeni_izin'] !== RegionPolicy::ALLOWED);
        $acilan = array_filter($degisecek, fn ($d) => $d['eski_izin'] !== RegionPolicy::ALLOWED && $d['yeni_izin'] === RegionPolicy::ALLOWED);

        // RENKLER RİSKE GÖRE, İYİ/KÖTÜ HABERE GÖRE DEĞİL.
        //
        // İlk yazımda "KAPANAN" kırmızı, "AÇILAN" yeşildi — sezgisel ama ters:
        // kapanmak bu düzeltmenin AMACI, açılmak ise tek tehlikeli yön. Yeşil
        // bir "AÇILAN: 3" satırı, okuyanı tam da durup bakması gereken yerde
        // rahatlatırdı.
        $this->components->twoColumnDetail('  → gönderime kapanan (amaç bu)', (string) count($kapanan));
        $this->components->twoColumnDetail(
            count($acilan) > 0 ? '<fg=red;options=bold>  → gönderime AÇILAN — DUR VE BAK</>' : '  → gönderime açılan',
            (string) count($acilan),
        );

        // KALAN RİSK RAHATLATICI GÖRÜNMEMELİ.
        //
        // "Kanıt yok — DOKUNULMADI: 262" satırı bir güvence gibi okunuyordu.
        // Oysa dokunulmayan kayıt DÜZELMİŞ değil; ülkesi hâlâ sorgudan gelen
        // (yani güvenilmez) değeri taşıyor ve bir kısmı gönderime AÇIK
        // duruyor. Yazma tarafı kapalı-düşüyor, SİSTEM düşmüyor.
        $kanitsizAcik = OutreachTarget::query()
            ->where('marketing_status', RegionPolicy::ALLOWED)
            ->get(['id', 'city', 'website', 'contact_email'])
            ->filter(fn ($h) => UlkeTespiti::tespit($h->city, $h->website, $h->contact_email) === null)
            ->count();

        if ($kanitsizAcik > 0) {
            $this->newLine();
            $this->components->warn(
                "ÜLKESİ KANITLANAMAYAN {$kanitsizAcik} KAYIT gönderime açıktı — KAPATILIYOR. ".
                'Ülke bilinmiyorsa hangi hukukun geçerli olduğu da bilinmiyor; '.
                'bu kayıtlara ticari e-posta gönderilemez.'
            );
        }

        if ($degisecek === []) {
            $this->components->info('Değişecek kayıt yok.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['id', 'İşletme', 'Adres', 'Eski', 'Yeni', 'İzin'],
            array_map(fn ($d) => [
                $d['id'],
                mb_strimwidth($d['ad'], 0, 34, '…'),
                mb_strimwidth($d['adres'], 0, 28, '…'),
                $d['eski'],
                $d['yeni'],
                $d['eski_izin'] === $d['yeni_izin'] ? '=' : $d['eski_izin'].' → '.$d['yeni_izin'],
            ], $degisecek),
        );

        if (! $uygula) {
            $this->newLine();
            $this->components->warn('KURU KOŞU — hiçbir kayıt değiştirilmedi. Yazmak için: --uygula');

            return self::SUCCESS;
        }

        $damga = now();
        $yazilan = 0;
        $silinenEposta = 0;

        foreach ($degisecek as $d) {
            // KAPALI DÜŞEN DÖNGÜ: yalnız listeye GİRMİŞ kayıtlar güncellenir.
            $hedef = OutreachTarget::query()->find($d['id']);
            if (! $hedef) {
                continue;
            }

            $guncelleme = [
                'country' => $d['yeni'],
                'marketing_status' => $d['yeni_izin'],
            ];

            /*
             * GÖNDERİME KAPANAN KAYDIN İLETİŞİM ADRESİ SİLİNİR.
             *
             * `EnrichmentRunner::enrichOne()` engelli bölgede iletişim
             * TOPLAMAZ — kural zaten yazılıydı. Ama kayıt o sırada yanlışlıkla
             * "allowed" göründüğü için toplama gerçekleşti: Türkiye'deki
             * işletmelerin e-postaları havuza girdi. Ülkeyi düzeltip adresi
             * bırakmak, kuralın ihlalini KALICI hâle getirirdi — GDPR'da
             * mesele yalnız göndermek değil, tutmak.
             *
             * Silme yalnız KAPANAN yönde yapılır; ülkesi US→US gibi zararsız
             * düzelen kayıtların verisine dokunulmaz.
             */
            if ($d['eski_izin'] === RegionPolicy::ALLOWED && $d['yeni_izin'] !== RegionPolicy::ALLOWED && $hedef->contact_email) {
                $guncelleme['contact_email'] = null;
                $silinenEposta++;
            }

            $hedef->update($guncelleme);
            $yazilan++;
        }

        /*
         * BİLİNMEYEN ÜLKE = GÖNDERİM YOK.
         *
         * Tespit `null` döndüğünde ülke alanına dokunulmaz (doğru: uydurma
         * yazmayız), ama kapı AÇIK kalıyordu — yani yazma tarafı kapalı
         * düşerken SİSTEM fail-OPEN çalışıyordu. Ülkesi bilinmeyen bir
         * işletmeye ticari e-posta göndermek, hangi hukukun geçerli olduğunu
         * bilmeden göndermektir.
         *
         * `country` KASITLI olarak değiştirilmez — yalnız kapı kapanır.
         * Sonradan kanıt çıkarsa (site eklenir, adres zenginleşir) aynı komut
         * ülkeyi yazar ve kapı kendiliğinden doğru duruma döner.
         */
        $kapatilan = 0;
        foreach (OutreachTarget::query()->where('marketing_status', RegionPolicy::ALLOWED)->get() as $hedef) {
            if (UlkeTespiti::tespit($hedef->city, $hedef->website, $hedef->contact_email) !== null) {
                continue;
            }

            $hedef->update(['marketing_status' => RegionPolicy::BLOCKED]);
            $kapatilan++;
        }

        $this->newLine();
        $this->components->info("{$yazilan} kayıt güncellendi.");
        if ($kapatilan > 0) {
            $this->components->info("{$kapatilan} kaydın kapısı kapatıldı (ülkesi kanıtlanamadı).");
        }
        if ($silinenEposta > 0) {
            $this->components->info("{$silinenEposta} kaydın iletişim e-postası silindi (engelli bölgeden toplanmıştı).");
        }
        $this->components->info('Geri alma tutamağı: updated_at >= '.$damga->toDateTimeString());

        return self::SUCCESS;
    }
}
