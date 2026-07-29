<?php

namespace App\Console\Commands;

use App\Models\KahyaCalismasi;
use App\Notifications\GunlukKahyaRaporu;
use App\Services\Kahya\KahyaTeshisi;
use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Günlük Kâhya raporunu üretir ve yöneticiye gönderir.
 *
 *     php artisan kahya:gunluk-rapor            # üret + gönder
 *     php artisan kahya:gunluk-rapor --gonderme # yalnız üret (deneme)
 *
 * TEŞHİS BURADA SENKRON TOPLANIR, kuyrukta değil. Kuyruk sürücüsü `database`
 * ve `retry_after` 90 saniye; kuyrukta 90 sn'yi aşan bir iş bitmeden yeniden
 * denenir ve rapor İKİ KEZ gönderilir. Medya doğrulama yüzlerce dosya
 * kontrolü yapar, bu sınıra yaklaşabilir. Komut zamanlayıcı sürecinde koşar
 * ve süre sınırı yoktur; kuyruğa yalnız hafif bildirim düşer.
 *
 * ÜRETİM İLE GÖNDERİM AYRIDIR. Rapor üretildiyse defterde saklanır — e-posta
 * gitmese bile (SMTP kapalı, ağ hatası) içerik kaybolmaz ve panelden okunur.
 * Gönderim hatası komutu ÇÖKERTMEZ: çökerse defter kaydı da yazılmaz ve
 * "en son ne zaman koştu" bilgisi kaybolur — ki bu sistemin tek erken uyarı
 * işaretidir.
 */
class KahyaGunlukRapor extends Command
{
    protected $signature = 'kahya:gunluk-rapor {--gonderme : Yalnız üret, e-posta gönderme}';

    protected $description = 'Günlük Kâhya raporunu üretir ve yöneticiye gönderir';

    public function handle(KahyaTeshisi $teshis): int
    {
        $baslangic = microtime(true);

        $veri = $teshis->topla();
        $sonGun = $teshis->sonYirmiDortSaat();

        $this->ozetiYazdir($veri, $sonGun);

        $alici = $this->alici();
        $gonderildi = false;
        $hata = null;

        if ($this->option('gonderme')) {
            $this->comment('--gonderme verildi, e-posta gönderilmedi.');
        } elseif ($alici === null) {
            $hata = 'Alıcı adresi tanımlı değil (kahya.alici / iletisim.eposta)';
            $this->warn($hata);
        } else {
            try {
                Notification::route('mail', $alici)->notify(new GunlukKahyaRaporu($veri, $sonGun));
                $gonderildi = true;
                $this->info("Rapor gönderildi: {$alici}");
            } catch (\Throwable $e) {
                // Gönderim hatası komutu ÇÖKERTMEZ (docblock'taki gerekçe).
                $hata = mb_substr($e->getMessage(), 0, 200);
                $this->error('Gönderilemedi: '.$hata);
                Log::error('Kâhya günlük raporu gönderilemedi', ['exception' => $e->getMessage()]);
            }
        }

        KahyaCalismasi::create([
            'tur' => 'gunluk_rapor',
            'gonderildi' => $gonderildi,
            'alici' => $alici,
            'ozet' => ['teshis' => $veri, 'son_gun' => $sonGun],
            'hata' => $hata,
            'sure_ms' => (int) ((microtime(true) - $baslangic) * 1000),
        ]);

        // Gönderim başarısız olsa da komut BAŞARILI sayılır: rapor üretildi ve
        // saklandı. Aksi hâlde zamanlayıcı her gün hata biriktirirdi ve asıl
        // sinyal (defterdeki `hata` alanı) gürültüde kaybolurdu.
        return self::SUCCESS;
    }

    /**
     * Raporun gideceği adres.
     *
     * `kahya.alici` panelden ayarlanabilir; boşsa iletişim adresine düşer —
     * o da zaten form bildirimlerinin gittiği adrestir.
     */
    private function alici(): ?string
    {
        foreach ([Settings::get('kahya.alici'), Settings::get('iletisim.eposta')] as $aday) {
            $aday = trim((string) $aday);

            if ($aday !== '' && filter_var($aday, FILTER_VALIDATE_EMAIL)) {
                return $aday;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $veri
     * @param  array<string, int>  $sonGun
     */
    private function ozetiYazdir(array $veri, array $sonGun): void
    {
        $e = $veri['envanter'];

        $this->line(sprintf('Pazaryeri: %d ilan, %d satıcı%s', $e['ilan'], $e['satici'], $e['uyari'] ? '  ⚠ '.$e['uyari'] : ''));
        $this->line(sprintf('Son 24 saat: %d yeni üye, %d yeni ilan', $sonGun['yeni_uye'], $sonGun['yeni_ilan']));
        $this->line(sprintf('Bekleyen iş kuyruğu: %d', count($veri['bekleyen'])));
        $this->line(sprintf('Kayıp medya: %d / %d', $veri['bozuk']['medya']['kayip'], $veri['bozuk']['medya']['taranan']));
        $this->line(sprintf('Hata kaydı (24s): %d', $veri['bozuk']['log']['toplam']));
        $this->line(sprintf('Kritik boş alan: %d', count($veri['eksik']['kritik'])));
    }
}
