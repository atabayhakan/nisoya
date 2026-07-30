<?php

namespace App\Notifications;

use App\Support\MailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Günlük Kâhya raporu — dört bölüm: ne oldu · ne bozuk · ne eksik · ne bekliyor.
 *
 * MAILABLE DEĞİL NOTIFICATION: `app/Mail/` bu projede boştur, tüm e-postalar
 * Notification üzerinden gider (14 sınıf). Desenden sapmamak için aynı yol.
 *
 * AĞIR İŞ BURADA YAPILMAZ: bildirim yalnız HAZIR bir dizi taşır. Teşhis
 * toplama komutta senkron koşar. Sebep: kuyruk sürücüsü `database` ve
 * `retry_after` 90 sn — kuyrukta 90 saniyeyi aşan bir iş bitmeden yeniden
 * denenir ve rapor İKİ KEZ gönderilir. Bildirim hafif olduğu için o riske
 * hiç girmez.
 *
 * BOŞ GÜNDE DE GÖNDERİLİR. Sessizlik "her şey yolunda" ile "komut çöktü"yü
 * ayırt edilemez kılar; tek satırlık "bugün bir şey olmadı" mesajı, gelmeyen
 * mesajdan çok daha fazla bilgi taşır.
 */
class GunlukKahyaRaporu extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $teshis  KahyaTeshisi::topla() çıktısı
     * @param  array<string, int>  $sonGun
     */
    public function __construct(
        public array $teshis,
        public array $sonGun,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = ['{tarih}' => now()->translatedFormat('j F Y')];

        $mail = (new MailMessage)
            ->subject(MailTemplates::part('gunluk_rapor', 'subject', $t))
            ->greeting(MailTemplates::part('gunluk_rapor', 'greeting', $t))
            ->line(MailTemplates::part('gunluk_rapor', 'intro', $t));

        $this->envanterSatiri($mail);
        $this->neOldu($mail);
        $this->gorevlerdeNeDurumda($mail);
        $this->neBekliyor($mail);
        $this->neBozuk($mail);
        $this->neEksik($mail);

        return $mail
            ->action(MailTemplates::part('gunluk_rapor', 'action', $t), url('/yonetim'))
            ->line(MailTemplates::part('gunluk_rapor', 'outro', $t));
    }

    /**
     * EN ÜSTTE VE HER ZAMAN: pazaryerinin gerçek doluluğu.
     * Bir gösterge panelinin en kolay gizleyeceği şey budur.
     */
    private function envanterSatiri(MailMessage $mail): void
    {
        $e = $this->teshis['envanter'];

        $mail->line(sprintf(
            '**Pazaryeri: %d aktif ilan, %d satıcı.**%s',
            $e['ilan'],
            $e['satici'],
            $e['uyari'] ? ' ⚠️ '.$e['uyari'] : ''
        ));
    }

    private function neOldu(MailMessage $mail): void
    {
        $mail->line('**Son 24 saat**');

        if (array_sum($this->sonGun) === 0) {
            $mail->line('• Yeni üye ya da ilan yok.');

            return;
        }

        foreach (['yeni_uye' => 'yeni üye', 'yeni_ilan' => 'yeni ilan'] as $anahtar => $etiket) {
            if (($this->sonGun[$anahtar] ?? 0) > 0) {
                $mail->line(sprintf('• %d %s', $this->sonGun[$anahtar], $etiket));
            }
        }
    }

    /**
     * Görev defteri durumu (F2): misyonlar sessizce ölmesin.
     *
     * Anahtar yokluğuna dayanıklı: panel, DEFTERDEKİ eski raporları da
     * yeniden çizer ve F2 öncesi kayıtlarda `gorevler` yoktur.
     */
    private function gorevlerdeNeDurumda(MailMessage $mail): void
    {
        $gorevler = $this->teshis['gorevler'] ?? null;

        if ($gorevler === null || ($gorevler['acik'] === [] && $gorevler['bekleyen_hamle'] === 0)) {
            return;
        }

        $mail->line('**Görevlerde durum**');

        foreach ($gorevler['acik'] as $g) {
            $satir = sprintf('• #%d %s — %d/%d adım', $g['id'], $g['baslik'], $g['yapildi'], $g['toplam']);

            if ($g['siradaki'] !== null) {
                $satir .= sprintf(', sıradaki: %s', $g['siradaki']);
            }

            // Üç günden uzun hareketsizlik raporda açıkça söylenir — görev
            // defterinin bütün amacı unutulan misyonu görünür kılmak.
            if (($g['hareketsiz_gun'] ?? 0) >= 3) {
                $satir .= sprintf(' ⚠️ %d gündür hareketsiz', $g['hareketsiz_gun']);
            }

            $mail->line($satir);
        }

        if ($gorevler['bekleyen_hamle'] > 0) {
            $mail->line(sprintf(
                '• **%d hamle kartı kararını bekliyor** — Kâhya & Yapay Zekâ → Bekleyen Hamleler.',
                $gorevler['bekleyen_hamle']
            ));
        }
    }

    private function neBekliyor(MailMessage $mail): void
    {
        $bekleyen = $this->teshis['bekleyen'];

        if ($bekleyen === []) {
            return;
        }

        $mail->line('**Seni bekleyen işler**');

        foreach ($bekleyen as $kuyruk) {
            $mail->line(sprintf(
                '• %s: **%d** — %s',
                $kuyruk['etiket'],
                $kuyruk['adet'],
                $kuyruk['aciklama']
            ));
        }
    }

    private function neBozuk(MailMessage $mail): void
    {
        $medya = $this->teshis['bozuk']['medya'];
        $log = $this->teshis['bozuk']['log'];

        $satirlar = [];

        if ($medya['kayip'] > 0) {
            $satirlar[] = sprintf(
                '• **%d medya dosyası diskte yok** (%d kayıt tarandı) — kullanıcı kırık görsel görüyor.',
                $medya['kayip'],
                $medya['taranan']
            );

            foreach (array_slice($medya['ornekler'], 0, 3) as $ornek) {
                $satirlar[] = sprintf('  · %s #%d — eksik: %s', $ornek['tur'], $ornek['id'], implode(', ', $ornek['eksik']));
            }
        }

        if ($log['toplam'] > 0) {
            $satirlar[] = sprintf('• **%d hata kaydı** (son 24 saat):', $log['toplam']);

            foreach (array_slice($log['imzalar'], 0, 3) as $imza) {
                $satirlar[] = sprintf('  · %s — %s (%s) × %d', $imza['seviye'], $imza['sinif'], $imza['yer'], $imza['adet']);
            }
        }

        if ($log['uyari']) {
            $satirlar[] = '• '.$log['uyari'];
        }

        if ($satirlar === []) {
            return;
        }

        $mail->line('**Bozuk olanlar**');

        foreach ($satirlar as $satir) {
            $mail->line($satir);
        }
    }

    private function neEksik(MailMessage $mail): void
    {
        $eksik = $this->teshis['eksik'];
        $satirlar = [];

        foreach ($eksik['kritik'] as $alan) {
            $satirlar[] = sprintf('• `%s` boş — %s', $alan['anahtar'], $alan['neden']);
        }

        if ($eksik['ilansiz_kategori']['sayi'] > 0) {
            $satirlar[] = sprintf(
                '• %d kategoride hiç ilan yok (ör. %s)',
                $eksik['ilansiz_kategori']['sayi'],
                implode(', ', array_slice($eksik['ilansiz_kategori']['ornekler'], 0, 3))
            );
        }

        if ($satirlar === []) {
            return;
        }

        $mail->line('**Doldurulmayı bekleyenler**');

        foreach ($satirlar as $satir) {
            $mail->line($satir);
        }

        if ($eksik['istege_bagli_sayi'] > 0) {
            $mail->line(sprintf(
                '_Ayrıca %d isteğe bağlı alan boş — çoğunun boş olması normaldir, tek tek listelenmiyor._',
                $eksik['istege_bagli_sayi']
            ));
        }
    }
}
