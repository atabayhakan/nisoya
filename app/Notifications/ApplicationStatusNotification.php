<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $jobTitle,
        public string $statusLabel,
        public int $jobId,
        public ?string $jobSlug = null,
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
            'icon' => '📋',
            'title' => 'Başvuru durumu güncellendi',
            'body' => '"'.$this->jobTitle.'" başvurun: '.$this->statusLabel,
            'url' => route('jobs.show', [$this->jobId, $this->jobSlug]),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nisoya: Başvuru durumun güncellendi')
            ->greeting('Merhaba '.$notifiable->name.',')
            ->line('"'.$this->jobTitle.'" ilanına yaptığın başvurunun durumu güncellendi:')
            ->line('Yeni durum: '.$this->statusLabel)
            ->action('İlanı görüntüle', route('jobs.show', [$this->jobId, $this->jobSlug]))
            ->line('Nisoya — Ne İş Olursa Yaparım');
    }
}
