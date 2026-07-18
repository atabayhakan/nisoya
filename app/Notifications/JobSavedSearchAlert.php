<?php

namespace App\Notifications;

use App\Models\JobSavedSearch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class JobSavedSearchAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public JobSavedSearch $search,
        public Collection $jobs,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Nisoya: Aramana uygun yeni iş ilanları var')
            ->greeting('Merhaba '.$notifiable->name.',')
            ->line('"'.$this->search->label.'" aramana uygun '.$this->jobs->count().' yeni iş ilanı:');

        foreach ($this->jobs as $job) {
            $company = $job->company?->name ? ' — '.$job->company->name : '';
            $mail->line('• '.$job->title.$company);
        }

        return $mail
            ->action('İş ilanlarını gör', route('jobs.index', $this->search->toQueryParams()))
            ->line('Bu uyarıyı panelindeki "İş Aramalarım" bölümünden kapatabilirsin.');
    }
}
