<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * İlanın bir görseli AI moderasyonunca uygunsuz bulununca gönderilir.
 * Ton bilinçli olarak yumuşak/suçlayıcı değil — AI yanılabilir, nihai
 * kararı bir admin verecek (bkz. App\Services\ImageModerationService).
 */
class ListingFlaggedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $listingTitle,
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
            'icon' => '🔍',
            'title' => 'İlanın incelemeye alındı',
            'body' => '"'.Str::limit($this->listingTitle, 50).'" — görsellerinden biri gözden geçiriliyor.',
            'url' => $this->url,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nisoya: İlanın incelemeye alındı')
            ->greeting('Merhaba '.$notifiable->name.',')
            ->line('"'.$this->listingTitle.'" ilanındaki bir görsel, otomatik ön kontrolümüz tarafından ek incelemeye alındı.')
            ->line('Bu genellikle yanlış bir eşleşmedir ve ekibimiz kısa süre içinde gözden geçirip ilanını tekrar yayına alacaktır. Ekstra bir şey yapmana gerek yok.')
            ->action('İlanlarımı görüntüle', $this->url)
            ->line('Nisoya — Ne İş Olursa Yaparız');
    }
}
