<?php

namespace App\Services\Kahya\Dis;

use App\Models\BekleyenHamle;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Onaylanan e-posta hamlesini GERÇEKTEN gönderen el (F4 — tasarım §2.2/F4).
 *
 * ---------------------------------------------------------------------------
 * ANA POSTA SİSTEMİNE FALLBACK YOK — BİLE BİLE
 *
 * Nisoya'nın işlemsel e-postaları (şifre sıfırlama, bildirim) sitenin can
 * damarı. Tanıtım/erişim postası aynı kimlikten çıkarsa ve şikâyet yerse,
 * spam damgasını İKİSİ birden yer — ve o damga yedekten geri yüklenemez
 * (docs/06 §3). Bu yüzden buradaki mailer YALNIZ kahya.gonderim_* ayarlarından
 * kurulur; ayar eksikse gönderim YOKTUR, "bari ana mailer'la gönderelim"
 * düşülmez. Ayar alanının yanındaki uyarı da aynı şeyi söyler: buraya ana
 * alan adının SMTP'sini DEĞİL, ayrı gönderim alanının kimliğini gir.
 *
 * ---------------------------------------------------------------------------
 * İKİ KORKULUK DAHA
 *
 * Günlük tavan (ısıtma): yeni gönderim kimliği ilk haftalarda günde 5-10
 * postayla ısınmalı; varsayılan tavan 10 ve panelden yönetilir.
 * Engel listesi: ret/şikâyet gelen adres kalıcı engellidir — istemeyene
 * ikinci kez yazılmaz.
 */
class HamleGonderici
{
    public const MAILER = 'kahya-gonderim';

    /** Gönderim kimliği panelden eksiksiz girilmiş mi? */
    public function hazirMi(): bool
    {
        foreach (['kahya.gonderim_host', 'kahya.gonderim_kullanici', 'kahya.gonderim_parola', 'kahya.gonderim_adresi'] as $anahtar) {
            if (trim((string) Settings::get($anahtar, '')) === '') {
                return false;
            }
        }

        return true;
    }

    public function gunlukLimit(): int
    {
        return max(0, (int) (Settings::get('kahya.gunluk_gonderim_limiti') ?: 10));
    }

    public function bugunGonderilen(): int
    {
        return BekleyenHamle::query()
            ->whereDate('gonderildi_at', now()->toDateString())
            ->count();
    }

    public function engelliMi(string $eposta): bool
    {
        return DB::table('kahya_gonderim_engelleri')
            ->where('eposta', mb_strtolower(trim($eposta)))
            ->exists();
    }

    /**
     * Onaylanmış bir e-posta hamlesini gönderir.
     *
     * Dönen metin SAHİBE gösterilir — başarı da başarısızlık da açık
     * cümleyle söylenir; kart üzerindeki gonderim_hata alanı ayrıca
     * kalıcı iz taşır.
     */
    public function gonder(BekleyenHamle $hamle): string
    {
        if ($hamle->durum !== BekleyenHamle::DURUM_ONAYLANDI) {
            return 'Gönderilmedi: hamle onaylı değil.';
        }

        if ($hamle->gonderildi_at !== null) {
            return 'Zaten gönderilmiş ('.$hamle->gonderildi_at->format('d.m.Y H:i').') — ikinci kez gönderilmez.';
        }

        $alici = mb_strtolower(trim((string) $hamle->alici_eposta));

        if ($alici === '' || ! filter_var($alici, FILTER_VALIDATE_EMAIL)) {
            $hamle->update(['gonderim_hata' => 'Alıcı adresi yok ya da geçersiz.']);

            return 'Gönderilmedi: kartta geçerli bir alıcı adresi yok — hamleyi elle uygulaman gerekecek.';
        }

        if (! $this->hazirMi()) {
            return 'Gönderilmedi: gönderim kimliği yapılandırılmamış (Kâhya Ayarları → Dış Eller). '
                .'Onay kaydedildi; hamleyi şimdilik elle uygula.';
        }

        if ($this->engelliMi($alici)) {
            $hamle->update(['gonderim_hata' => 'Alıcı engel listesinde.']);

            return "Gönderilmedi: {$alici} engel listesinde — istemeyene ikinci kez yazılmaz.";
        }

        $bugun = $this->bugunGonderilen();
        $limit = $this->gunlukLimit();

        if ($bugun >= $limit) {
            // Tavan hatası kalıcı iz DEĞİL: yarın tekrar denenebilir.
            return "Gönderilmedi: günlük ısıtma tavanı dolu ({$bugun}/{$limit}). Yarın tekrar dene "
                .'ya da tavanı Kâhya Ayarları\'ndan artır (yeni gönderim kimliğinde acele etme).';
        }

        try {
            Mail::mailer(self::MAILER)->raw($hamle->icerik, function ($mesaj) use ($hamle, $alici): void {
                $mesaj->to($alici)
                    ->subject($hamle->baslik)
                    ->from(
                        (string) Settings::get('kahya.gonderim_adresi'),
                        (string) (Settings::get('kahya.gonderim_ad') ?: Settings::get('genel.site_adi', 'Nisoya')),
                    );
            });
        } catch (\Throwable $e) {
            report($e);
            $hamle->update(['gonderim_hata' => mb_substr($e->getMessage(), 0, 300)]);

            return 'Gönderilemedi: sunucu hatası — ayrıntı kartın üstünde ve log\'da. Ayarları kontrol et.';
        }

        $hamle->update(['gonderildi_at' => now(), 'gonderim_hata' => null]);

        Log::info('Kâhya hamle gönderdi', ['hamle_id' => $hamle->id]);

        return "Gönderildi: {$alici} ({$hamle->baslik}). Bugün ".($bugun + 1)."/{$limit}.";
    }
}
