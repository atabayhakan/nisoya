<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEventImage;
use App\Models\Event;
use App\Models\EventMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Anı akışı yüklemeleri (herkese açık davet sayfasından).
 * Kimlik: misafirler LCV çerezindeki token'la, ev sahibi oturumuyla tanınır.
 * Kimliği olmayan ziyaretçi yükleme yapamaz (önce LCV vermeli).
 */
class EventMediaController extends Controller
{
    public function store(Request $request, string $token): RedirectResponse
    {
        $event = Event::query()->where('token', $token)->where('is_active', true)->firstOrFail();

        abort_unless($event->streamIsOpen(), 403, 'Anı akışı henüz açık değil.');

        [$guest, $isHost] = $this->identify($request, $event);

        if (! $guest && ! $isHost) {
            return back()->withErrors(['files' => 'Fotoğraf paylaşmak için önce yukarıdan katılımını bildir (LCV).']);
        }

        if ($guest?->is_blocked) {
            abort(403);
        }

        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,'.implode(',', EventMedia::VIDEO_MIMES), 'max:'.(EventMedia::MAX_VIDEO_BYTES / 1024)],
        ], [], ['files' => 'dosyalar', 'files.*' => 'dosya']);

        // Kota anlık görüntüsü (kuyruktaki işlenmemiş fotoğraflar sayıma girmez —
        // limitler bilinçli olarak "yaklaşık ama katı sınırın altında" çalışır)
        $photoCount = $event->media()->where('type', 'image')->count();
        $videoCount = $event->media()->where('type', 'video')->count();
        $totalBytes = (int) $event->media()->sum('size_bytes');

        $status = ($event->require_approval && ! $isHost) ? 'beklemede' : 'yayinda';
        $accepted = 0;

        foreach ($request->file('files', []) as $file) {
            /** @var UploadedFile $file */
            $isVideo = in_array($file->getMimeType(), EventMedia::VIDEO_MIMES, true);

            if ($totalBytes + $file->getSize() > EventMedia::MAX_TOTAL_BYTES_PER_EVENT) {
                return back()->withErrors(['files' => 'Bu etkinliğin depolama alanı doldu — yeni dosya eklenemiyor.']);
            }

            if ($isVideo) {
                if ($videoCount >= EventMedia::MAX_VIDEOS_PER_EVENT) {
                    return back()->withErrors(['files' => 'Video limiti doldu (en fazla '.EventMedia::MAX_VIDEOS_PER_EVENT.' video).']);
                }

                $path = $file->store('event-media/'.$event->id.'/video', EventMedia::DISK);

                $event->media()->create([
                    'event_guest_id' => $guest?->id,
                    'type' => 'video',
                    'status' => $status,
                    'path' => $path,
                    'size_bytes' => $file->getSize(),
                ]);

                $videoCount++;
                $totalBytes += $file->getSize();
            } else {
                if ($file->getSize() > EventMedia::MAX_IMAGE_UPLOAD_KB * 1024) {
                    return back()->withErrors(['files' => 'Fotoğraflar en fazla 8 MB olabilir.']);
                }

                if ($photoCount >= EventMedia::MAX_PHOTOS_PER_EVENT) {
                    return back()->withErrors(['files' => 'Fotoğraf limiti doldu (en fazla '.EventMedia::MAX_PHOTOS_PER_EVENT.' fotoğraf).']);
                }

                // Gizlilik: ham dosya önce özel diske, EXIF temizliği kuyrukta
                $rawPath = $file->store('pending-event-media', 'local');

                ProcessEventImage::dispatch(
                    eventId: $event->id,
                    eventGuestId: $guest?->id,
                    rawPath: $rawPath,
                    rawDisk: 'local',
                    status: $status,
                );

                $photoCount++;
                $totalBytes += $file->getSize(); // yaklaşık — WebP sonrası küçülür
            }

            $accepted++;
        }

        $message = $status === 'beklemede'
            ? $accepted.' dosya alındı — ev sahibi onaylayınca akışta görünecek. 🕐'
            : $accepted.' dosya paylaşıldı! Fotoğraflar birkaç dakika içinde akışta görünür. 📸';

        return redirect()->route('davet.show', $event->token)->with('media_status', $message);
    }

    /** Misafir kendi yüklemesini, ev sahibi her şeyi silebilir. */
    public function destroy(Request $request, string $token, EventMedia $media): RedirectResponse
    {
        $event = Event::query()->where('token', $token)->firstOrFail();

        abort_unless($media->event_id === $event->id, 404);

        [$guest, $isHost] = $this->identify($request, $event);

        $ownsMedia = $guest && $media->event_guest_id === $guest->id;

        abort_unless($isHost || $ownsMedia, 403);

        $media->delete(); // dosyalar model deleting hook'unda silinir

        return back()->with('media_status', 'Paylaşım silindi.');
    }

    /** [misafir|null, evSahibiMi] — misafir LCV çerezinden, ev sahibi oturumdan. */
    private function identify(Request $request, Event $event): array
    {
        $isHost = $request->user()?->id === $event->user_id;

        $guestToken = $request->cookie('davet_misafir_'.$event->id);
        $guest = is_string($guestToken) && $guestToken !== ''
            ? $event->guests()->where('token', $guestToken)->first()
            : null;

        return [$guest, $isHost];
    }
}
