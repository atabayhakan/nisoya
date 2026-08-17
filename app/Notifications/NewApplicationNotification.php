<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApplicationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $applicantName,
        public string $jobTitle,
        public int $jobId,
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
            'icon' => '📩',
            'title' => 'Yeni başvuru: '.$this->jobTitle,
            'body' => $this->applicantName.' ilanına başvurdu.',
            'url' => route('panel.jobs.applicants', $this->jobId),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nisoya: "'.$this->jobTitle.'" ilanına yeni başvuru')
            ->greeting('Merhaba '.$notifiable->name.',')
            ->line($this->applicantName.', "'.$this->jobTitle.'" ilanına başvurdu.')
            ->action('Başvuruyu görüntüle', route('panel.jobs.applicants', $this->jobId))
            ->line('Nisoya — Ne İş Olursa Yaparız');
    }
}
