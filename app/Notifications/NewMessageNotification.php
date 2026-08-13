<?php

namespace App\Notifications;

use App\Models\Message;
use App\Support\MailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Bildirim bu kadar GECİKMELİ gönderilir ve o ana kadar mesaj okunduysa
     * HİÇ gönderilmez.
     *
     * -----------------------------------------------------------------------
     * NEDEN (sahibin bildirimi, 2026-08-13)
     *
     * "Her mesaj yazdığında mail geliyor; sohbet ekranı açıkken mail gelmesine
     * gerek yok." Haklı: karşılıklı yazışırken her satır için bir e-posta,
     * gelen kutusunu doldurur ve insanı bildirimleri toptan kapatmaya iter —
     * yani GERÇEKTEN gerekli olan bildirimi de kaybettirir.
     *
     * "Ekran açık mı" diye sormak yerine "OKUNDU MU" diye soruyoruz. İkincisi
     * daha sağlam: sekmeyi bir dakika sonra açan da kapsanır, sekmesi açık
     * unutulmuş ama başında olmayan kişi ise yine haber alır.
     *
     * Bedeli: gerçekten uzakta olan kişiye bildirim bir dakika geç gider.
     * Uzaktaki biri için bir dakika hiçbir şey; oysa yanıt yazışması sırasında
     * gelen her posta gerçek bir rahatsızlık.
     */
    public const GECIKME_SANIYE = 60;

    public function __construct(
        public string $body,
        public string $senderName,
        public int $conversationId,
        /** Okundu kontrolü için — verilmezse eski davranış (hep gönder). */
        public ?int $messageId = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        /*
         * OKUNDUYSA HİÇBİR KANAL YOK — posta da, zil de, push da.
         *
         * Yalnız postayı kesmek yetmezdi: okunmuş bir mesaj için zilde
         * bildirim biriktirmek de aynı gürültünün başka biçimi.
         * Laravel boş kanal listesinde hiçbir şey göndermez.
         */
        if ($this->messageId !== null && Message::whereKey($this->messageId)->whereNotNull('read_at')->exists()) {
            return [];
        }

        $channels = ['mail', 'database'];

        // VAPID anahtarları tanımlı değilse (örn. üretim .env'i henüz
        // güncellenmediyse) kanal hiç eklenmez — kuyruk job'ı patlamaz.
        if (config('webpush.vapid.public_key') && config('webpush.vapid.private_key')) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->senderName.' sana mesaj gönderdi')
            ->body(Str::limit($this->body, 120))
            ->icon('/icons/icon-192.png')
            ->badge('/icons/icon-192.png')
            ->tag('conversation-'.$this->conversationId) // aynı sohbetin push'ları üst üste binmesin, sonuncusu görünsün
            ->data(['url' => route('panel.messages.show', $this->conversationId)]);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => '💬',
            'title' => $this->senderName.' sana mesaj gönderdi',
            'body' => Str::limit($this->body, 80),
            'url' => route('panel.messages.show', $this->conversationId),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Metinler panelden düzenlenebilir (Site Yönetimi → E-posta Metinleri).
        $t = ['{ad}' => $notifiable->name, '{gonderen}' => $this->senderName];

        return (new MailMessage)
            ->subject(MailTemplates::part('yeni_mesaj', 'subject', $t))
            ->greeting(MailTemplates::part('yeni_mesaj', 'greeting', $t))
            ->line(MailTemplates::part('yeni_mesaj', 'intro', $t))
            ->line('"'.Str::limit($this->body, 140).'"')
            ->action(MailTemplates::part('yeni_mesaj', 'action', $t), route('panel.messages.show', $this->conversationId))
            ->line(MailTemplates::part('yeni_mesaj', 'outro', $t));
    }
}
