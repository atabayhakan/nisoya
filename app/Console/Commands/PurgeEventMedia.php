<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Notifications\EventMediaPurgeWarning;
use Illuminate\Console\Command;

/**
 * 12 aylık medya saklama politikası (bkz. tasarım belgesi Bölüm 5):
 * - Etkinlikten 11 ay sonra: ev sahibine "1 ay içinde silinecek" uyarısı (bir kez).
 * - Etkinlikten 12 ay sonra: anı akışı medyası dosyalarıyla birlikte silinir.
 * Etkinlik ve LCV kayıtları silinmez — yalnızca medya. Günlük zamanlanır.
 */
class PurgeEventMedia extends Command
{
    protected $signature = 'events:purge-media';

    protected $description = 'Etkinlik medyası saklama politikası: 11. ayda uyar, 12. ayda sil';

    public function handle(): int
    {
        $warned = 0;
        $purgedEvents = 0;
        $purgedFiles = 0;

        // 1) Uyarı: 11 ayı geçen, medyası olan, henüz uyarılmamış etkinlikler
        Event::query()
            ->whereNull('media_purge_warned_at')
            ->where('starts_at', '<=', now()->subMonths(11))
            ->whereHas('media')
            ->with('user')
            ->each(function (Event $event) use (&$warned) {
                $purgeDate = $event->starts_at->copy()->addMonths(12)->format('d.m.Y');
                $event->user?->notify(new EventMediaPurgeWarning($event->title, $purgeDate, $event->inviteUrl()));
                $event->update(['media_purge_warned_at' => now()]);
                $warned++;
            });

        // 2) Silme: 12 ayı geçen etkinliklerin medyası (deleting hook dosyaları temizler)
        Event::query()
            ->where('starts_at', '<=', now()->subMonths(12))
            ->whereHas('media')
            ->each(function (Event $event) use (&$purgedEvents, &$purgedFiles) {
                $count = 0;
                $event->media()->get()->each(function ($media) use (&$count) {
                    $media->delete();
                    $count++;
                });
                $purgedEvents++;
                $purgedFiles += $count;
            });

        $this->info("Uyarılan etkinlik: {$warned} · Medyası silinen etkinlik: {$purgedEvents} ({$purgedFiles} dosya kaydı)");

        return self::SUCCESS;
    }
}
