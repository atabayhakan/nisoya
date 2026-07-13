<?php

namespace App\Http\Controllers;

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\EventGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/**
 * Herkese açık davetiye sayfası + hesapsız LCV.
 * Misafir kimliği çerezle taşınır: LCV verince misafir token'ı çereze
 * yazılır, aynı tarayıcıdan dönen misafir kendi yanıtını günceller
 * (mükerrer kayıt oluşmaz). Ev sahibi yönetimi için bkz. EventController.
 */
class EventInvitationController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $event = Event::query()->where('token', $token)->where('is_active', true)->firstOrFail();

        // Bu tarayıcıdan daha önce LCV verildiyse formu doldurup "güncelle" moduna geç
        $myGuest = $this->guestFromCookie($request, $event);

        return view('davet.show', [
            'event' => $event,
            'theme' => $event->themeConfig(),
            'myGuest' => $myGuest,
            'statuses' => RsvpStatus::cases(),
        ]);
    }

    public function rsvp(Request $request, string $token): RedirectResponse
    {
        $event = Event::query()->where('token', $token)->where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'status' => ['required', new Enum(RsvpStatus::class)],
            'party_size' => ['required', 'integer', 'min:1', 'max:20'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], [
            'name' => 'isim',
            'status' => 'katılım durumu',
            'party_size' => 'kişi sayısı',
            'note' => 'not',
        ]);

        $guest = $this->guestFromCookie($request, $event);

        if ($guest) {
            $guest->update($data);
            $message = 'Yanıtın güncellendi. 💚';
        } else {
            $guest = $event->guests()->create($data);
            $message = $data['status'] === RsvpStatus::Geliyor->value
                ? 'Yanıtın iletildi — görüşmek üzere! 🎉'
                : 'Yanıtın iletildi, teşekkürler. 💚';
        }

        // Misafir token'ı 90 gün çerezde kalır (yanıt güncelleme + D2'de yükleme sahipliği)
        return redirect()->route('davet.show', $event->token)
            ->with('rsvp_status', $message)
            ->withCookie(cookie($this->cookieName($event), $guest->token, 60 * 24 * 90));
    }

    private function guestFromCookie(Request $request, Event $event): ?EventGuest
    {
        $token = $request->cookie($this->cookieName($event));

        if (! is_string($token) || $token === '') {
            return null;
        }

        return $event->guests()->where('token', $token)->first();
    }

    private function cookieName(Event $event): string
    {
        return 'davet_misafir_'.$event->id;
    }
}
