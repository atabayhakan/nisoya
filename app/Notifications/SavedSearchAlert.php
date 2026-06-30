<?php

namespace App\Notifications;

use App\Models\SavedSearch;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class SavedSearchAlert extends Notification
{
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
        $mail = (new MailMessage)
            ->subject('Nisoya: Aramana uygun yeni ilanlar var')
            ->greeting('Merhaba '.$notifiable->name.',')
            ->line('"'.$this->search->label.'" aramana uygun '.$this->listings->count().' yeni ilan:');

        foreach ($this->listings as $listing) {
            $price = $listing->price !== null
                ? ' — '.number_format((float) $listing->price, 0).' '.$listing->currency
                : '';
            $mail->line('• '.$listing->title.$price);
        }

        return $mail
            ->action('İlanları gör', route('listings.index', $this->search->toQueryParams()))
            ->line('Bu uyarıyı panelindeki "Aramalarım" bölümünden kapatabilirsin.');
    }
}
