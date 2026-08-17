<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * İletişim formundan yeni mesaj gelince admin'e (anonim mail yönlendirmesiyle
 * — Notification::route('mail', ...)) gönderilir. Yönetici hesabı olmadığı
 * için 'database' kanalı yok, sadece 'mail'.
 */
class NewContactMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ContactMessage $contactMessage,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = $this->contactMessage;

        return (new MailMessage)
            ->subject('Nisoya: yeni iletişim mesajı — '.$m->category->getLabel())
            ->greeting('Yeni bir iletişim mesajı geldi.')
            ->line('**Gönderen:** '.$m->name.' ('.$m->email.')')
            ->when($m->phone, fn (MailMessage $mail) => $mail->line('**Telefon:** '.$m->phone))
            ->line('**Konu:** '.$m->category->getLabel())
            ->line('**Mesaj:**')
            ->line(Str::limit($m->message, 1000))
            ->action('Admin panelinde görüntüle', route('filament.admin.resources.contact-messages.index'))
            ->line('Nisoya — Ne İş Olursa Yaparız');
    }
}
