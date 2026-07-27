<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Support\MailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Destek biletine panelden yazılan yanıtı, bileti açan kişiye e-postayla
 * gönderir.
 *
 * ANONİM ALICI: iletişim formunu misafirler de (hesapsız) doldurabildiği
 * için alıcı bir User modeli olmayabilir — NewContactMessageNotification ile
 * aynı desen kullanılır: Notification::route('mail', $email)->notify(...).
 *
 * replyTo: misafirin "yanıtla" demesi doğal beklenti; cevabı destek
 * adresine düşürüyoruz (bu depoda replyTo'nun ilk kullanımı). Böylece
 * gelen-yanıt yakalama (IMAP/webhook) altyapısı kurmadan da yazışma
 * kopmuyor — sahip destek kutusundan okuyup bilete iç not düşebilir.
 */
class ContactReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ContactMessage $ticket,
        public string $body,
    ) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = [
            '{ad}' => $this->ticket->name,
            '{konu}' => $this->ticket->category->getLabel(),
        ];

        $destekAdresi = setting('iletisim.eposta', 'destek@nisoya.com');

        $mail = (new MailMessage)
            ->subject(MailTemplates::part('destek_yaniti', 'subject', $data))
            ->greeting(MailTemplates::part('destek_yaniti', 'greeting', $data))
            ->line(MailTemplates::part('destek_yaniti', 'intro', $data));

        // Yöneticinin yazdığı serbest metin. Satır sonları korunsun diye
        // paragraf paragraf eklenir; boş satırlar atlanır.
        foreach (preg_split('/\R{2,}/', trim($this->body)) ?: [] as $paragraf) {
            $paragraf = trim($paragraf);
            if ($paragraf !== '') {
                $mail->line($paragraf);
            }
        }

        return $mail
            ->action(MailTemplates::part('destek_yaniti', 'action', $data), url('/'))
            ->line(MailTemplates::part('destek_yaniti', 'outro', $data))
            ->replyTo($destekAdresi, setting('genel.site_adi', 'Nisoya'));
    }
}
