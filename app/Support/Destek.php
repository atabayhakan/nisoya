<?php

namespace App\Support;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Notifications\ContactReplyNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Destek bileti iş mantığı (2026-07-27).
 *
 * Filament aksiyonlarından ayrı tutulur ki hem test edilebilsin hem de
 * ileride başka bir yerden (ör. toplu aksiyon, komut) çağrılabilsin.
 */
final class Destek
{
    /**
     * Yanıt gönderme yetkisi. Bilinçli olarak GÖRÜNTÜLEMEDEN daha dar:
     * moderatör gelen kutusunu görebiliyor (içerik moderasyonu kaynakları
     * ona açık — bkz. RestrictsToAdmins docblock) ama site adına dışarıya
     * e-posta yazmak sahibin işi.
     */
    public static function yanitlayabilirMi(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * Bilete yanıt yazar ve misafire e-posta gönderir.
     *
     * Gönderim başarısız olursa yanıt KAYBOLMAZ: failed_at/error ile
     * kaydedilir, böylece bilet yanlışlıkla "yanıtlandı" görünmez.
     */
    public static function yanitla(ContactMessage $ticket, string $body): ContactMessageReply
    {
        $yanit = ContactMessageReply::create([
            'contact_message_id' => $ticket->id,
            'user_id' => auth()->id(),
            'body' => trim($body),
        ]);

        try {
            Notification::route('mail', $ticket->email)
                ->notify(new ContactReplyNotification($ticket, $yanit->body));

            $yanit->update(['sent_at' => now()]);

            $ticket->update([
                'status' => ContactMessageStatus::Yanitlandi,
                'first_replied_at' => $ticket->first_replied_at ?? now(),
                // Yanıtlayan kişi bileti otomatik üstlenir (atanmamışsa).
                'assigned_to' => $ticket->assigned_to ?? auth()->id(),
            ]);
        } catch (\Throwable $e) {
            $yanit->update([
                'failed_at' => now(),
                'error' => mb_substr($e->getMessage(), 0, 1000),
            ]);
        }

        return $yanit->refresh();
    }

    /** Bileti kapatır (kapanış zamanını damgalar). */
    public static function kapat(ContactMessage $ticket): void
    {
        $ticket->update([
            'status' => ContactMessageStatus::Kapandi,
            'closed_at' => now(),
        ]);
    }
}
