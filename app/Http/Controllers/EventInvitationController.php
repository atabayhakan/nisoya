<?php

namespace App\Http\Controllers;

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\EventGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
    /** Davetiye arayüzünün desteklediği diller (misafirin eşi/arkadaşı için). */
    private const LOCALES = ['tr', 'en', 'de'];

    public function show(Request $request, string $token): View
    {
        $event = Event::query()->where('token', $token)->where('is_active', true)->firstOrFail();

        $locale = $this->applyLocale($request);

        // Bu tarayıcıdan daha önce LCV verildiyse formu doldurup "güncelle" moduna geç
        $myGuest = $this->guestFromCookie($request, $event);
        $isHost = $request->user()?->id === $event->user_id;

        // Anı akışı: yayındakiler herkese; misafir kendi bekleyenlerini,
        // ev sahibi tüm bekleyenleri de görür (moderasyon panelde ayrıca var)
        $media = null;
        if ($event->streamIsOpen()) {
            $media = $event->media()
                ->with('guest')
                ->where(function ($q) use ($myGuest, $isHost) {
                    if ($isHost) {
                        return; // hepsi
                    }
                    $q->where('status', 'yayinda');
                    if ($myGuest) {
                        $q->orWhere('event_guest_id', $myGuest->id);
                    }
                })
                ->latest('id')
                ->paginate(24);
        }

        return view('davet.show', [
            'event' => $event,
            'theme' => $event->themeConfig(),
            'myGuest' => $myGuest,
            'isHost' => $isHost,
            'media' => $media,
            'statuses' => RsvpStatus::cases(),
            'locale' => $locale,
            'locales' => self::LOCALES,
        ]);
    }

    /** Takvime ekle: RFC 5545 uyumlu .ics dosyası (yerel "duvar saati" — TZ'siz). */
    public function ics(string $token): Response
    {
        $event = Event::query()->where('token', $token)->where('is_active', true)->firstOrFail();

        $escape = fn (?string $text) => str_replace(
            ['\\', ';', ',', "\n"],
            ['\\\\', '\;', '\,', '\n'],
            (string) $text,
        );

        $location = trim(implode(' — ', array_filter([$event->venue_name, $event->venue_address])));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Nisoya//Davetiye//TR',
            'BEGIN:VEVENT',
            'UID:davet-'.$event->token.'@nisoya.com',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$event->starts_at->format('Ymd\THis'),
            'DTEND:'.$event->starts_at->copy()->addHours(4)->format('Ymd\THis'),
            'SUMMARY:'.$escape($event->title),
            $location !== '' ? 'LOCATION:'.$escape($location) : null,
            $event->description ? 'DESCRIPTION:'.$escape($event->description) : null,
            'URL:'.$event->inviteUrl(),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return response(implode("\r\n", array_filter($lines)), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="davetiye.ics"',
        ]);
    }

    /**
     * ?dil= parametresine göre istek yerelini ayarla (Carbon tarihleri dahil).
     * Geçersiz/eksik değerde açıkça 'tr'ye sıfırlanır — locale, uzun ömürlü
     * süreçlerde (test, octane) bir önceki istekten sızmasın.
     */
    private function applyLocale(Request $request): string
    {
        $locale = $request->string('dil')->toString();

        if (! in_array($locale, self::LOCALES, true)) {
            $locale = 'tr';
        }

        app()->setLocale($locale);

        return $locale;
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

        // Misafir token'ı 90 gün çerezde kalır (yanıt güncelleme + yükleme sahipliği)
        $params = ['token' => $event->token];
        if (in_array($request->input('dil'), self::LOCALES, true)) {
            $params['dil'] = $request->input('dil');
        }

        return redirect()->route('davet.show', $params)
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
