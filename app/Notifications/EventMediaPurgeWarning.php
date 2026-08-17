<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 12 aylık medya saklama politikası uyarısı: etkinlik fotoğraf/videoları
 * silinmeden ~1 ay önce ev sahibine gönderilir (bkz. tasarım belgesi Bölüm 5).
 */
class EventMediaPurgeWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $eventTitle,
        public string $purgeDate,   // 'd.m.Y'
        public string $inviteUrl,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Etkinlik anıların '.$this->purgeDate.' tarihinde silinecek — indirmeyi unutma')
            ->greeting('Merhaba!')
            ->line('"'.$this->eventTitle.'" etkinliğinin üzerinden neredeyse bir yıl geçti.')
            ->line('Depolama politikamız gereği anı akışındaki fotoğraf ve videolar **'.$this->purgeDate.'** tarihinde kalıcı olarak silinecek.')
            ->line('Saklamak istediklerini şimdiden bilgisayarına indirmeni öneririz.')
            ->action('Anı akışını aç', $this->inviteUrl)
            ->line('Nisoya — Ne İş Olursa Yaparız');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => '🗓️',
            'title' => 'Etkinlik anıların yakında silinecek',
            'body' => '"'.$this->eventTitle.'" medyası '.$this->purgeDate.' tarihinde silinecek — indirmeyi unutma.',
            'url' => $this->inviteUrl,
        ];
    }
}
