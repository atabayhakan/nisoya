<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Yüklediği bir ilan görselinde GPS koordinatı tespit edilen kullanıcıya
 * gönderilir (KVKK bilgilendirme). Koordinatlar Nisoya tarafından zaten
 * yayınlanmadan önce EXIF'ten temizlendi — bu sadece bilgilendirme amaçlı.
 */
class GpsPrivacyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $listingId,
        public string $listingTitle,
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
            'icon' => '📍',
            'title' => 'Bir görselinde konum bilgisi tespit edildi',
            'body' => "\"{$this->listingTitle}\" ilanındaki bir fotoğrafta GPS koordinatı vardı, yayından önce temizlendi.",
            'url' => route('panel.listings.edit', $this->listingId),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nisoya: Bir fotoğrafında konum bilgisi tespit edildi')
            ->greeting('Merhaba '.$notifiable->name.',')
            ->line("\"{$this->listingTitle}\" ilanına yüklediğin bir fotoğrafta GPS konum bilgisi (EXIF metadata) bulundu.")
            ->line('Bu bilgi görsel yayınlanmadan önce otomatik olarak temizlendi; başkaları tarafından görülemez.')
            ->line('Bilgin olsun istedik — telefon kamerandaki konum servisini kapatarak gelecekte bu bilgiyi baştan engelleyebilirsin.')
            ->action('İlanı görüntüle', route('panel.listings.edit', $this->listingId))
            ->line('Nisoya — Ne İş Olursa Yaparız');
    }
}
