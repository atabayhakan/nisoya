<?php

namespace App\Notifications;

use App\Models\SavedSearch;
use App\Support\MailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class SavedSearchAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SavedSearch $search,
        public Collection $listings,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Statik metinler panelden düzenlenebilir (Site Yönetimi → E-posta Metinleri).
        // İlan listesi dinamik olduğu için kodda kalır.
        $t = [
            '{ad}' => $notifiable->name,
            '{arama}' => $this->search->label,
            '{sayi}' => (string) $this->listings->count(),
        ];

        $mail = (new MailMessage)
            ->subject(MailTemplates::part('kayitli_arama', 'subject', $t))
            ->greeting(MailTemplates::part('kayitli_arama', 'greeting', $t))
            ->line(MailTemplates::part('kayitli_arama', 'intro', $t));

        foreach ($this->listings as $listing) {
            $price = $listing->price !== null
                ? ' — '.number_format((float) $listing->price, 0).' '.$listing->currency
                : '';
            $mail->line('• '.$listing->title.$price);
        }

        return $mail
            ->action(MailTemplates::part('kayitli_arama', 'action', $t), route('listings.index', $this->search->toQueryParams()))
            ->line(MailTemplates::part('kayitli_arama', 'outro', $t));
    }
}
