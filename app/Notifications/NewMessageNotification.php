<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $body,
        public string $senderName,
        public int $conversationId,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nisoya: '.$this->senderName.' sana mesaj gönderdi')
            ->greeting('Merhaba '.$notifiable->name.',')
            ->line($this->senderName.' sana yeni bir mesaj gönderdi:')
            ->line('"'.Str::limit($this->body, 140).'"')
            ->action('Mesajı görüntüle', route('panel.messages.show', $this->conversationId))
            ->line('Nisoya — Ne İş Olursa Yaparım');
    }
}
