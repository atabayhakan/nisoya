<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * "İlanında şunlar eksik" — satıcıya BİR KEZ gönderilen yardım mesajı.
 *
 * TON: öneri, uyarı değil. İlanda yanlış bir şey yok; sadece daha iyi
 * olabilir. Suçlayıcı bir dil, insanın ilanı düzeltmesini değil siteyi
 * bırakmasını sağlar.
 *
 * BİR KEZ: tekrarı yok, hatırlatması yok. Satıcı görmezden geldiyse cevabı
 * budur (bkz. IlanIpuclariGonder komutundaki `tips_notified_at`).
 */
class IlanIpuclariNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<array{anahtar: string, metin: string}>  $eksikler
     */
    public function __construct(
        public string $listingTitle,
        public array $eksikler,
        public string $url,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => '💡',
            'title' => 'İlanını güçlendirebilirsin',
            'body' => '"'.Str::limit($this->listingTitle, 40).'" için '.count($this->eksikler).' öneri var.',
            'url' => $this->url,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mesaj = (new MailMessage)
            ->subject('Nisoya: İlanını güçlendirmek için birkaç öneri')
            ->greeting('Merhaba '.$notifiable->name.',')
            ->line('"'.$this->listingTitle.'" ilanın yayında. Birkaç küçük ekleme daha çok kişiye ulaşmanı sağlayabilir:');

        foreach ($this->eksikler as $eksik) {
            $mesaj->line('• '.$eksik['metin']);
        }

        return $mesaj
            ->action('İlanı düzenle', $this->url)
            // Bu cümle bilerek var: tek seferlik olduğunu söylemek, "acaba
            // her hafta bu mesaj gelecek mi" endişesini baştan kapatıyor.
            ->line('Bu tek seferlik bir öneri mesajıdır; aynı ilan için tekrar göndermeyeceğiz.')
            ->line('Nisoya — Ne İş Olursa Yaparım');
    }
}
