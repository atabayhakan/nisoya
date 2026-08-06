<?php

namespace App\Observers;

use App\Enums\ListingStatus;
use App\Enums\UserStatus;
use App\Models\User;

class UserObserver
{
    /**
     * Kullanıcı durumu "Aktif" dışına geçtiğinde (Askıda veya Silinmiş)
     * kullanıcının tüm aktif ilanlarını otomatik "Pasif"e çek.
     *
     * Tersi durumda (Silinmiş → Aktif) ilanlar geri aktifleştirilmez;
     * admin tek tek yeniden yayına almalıdır (bilinçli tasarım kararı).
     */
    public function updated(User $user): void
    {
        if (! $user->wasChanged('status')) {
            return;
        }

        /*
         * `getRawOriginal` — `getOriginal` DEĞİL (2026-08-06'da bulundu).
         *
         * `getOriginal()` cast'i UYGULAR: status alanı UserStatus'a cast
         * edildiği için geriye string değil ENUM döner. Alttaki karşılaştırma
         * ise `UserStatus::Aktif->value` (string) ile yapılıyordu — yani koşul
         * HİÇBİR ZAMAN doğru olmadı ve bu otomatik susturma canlıda hiç
         * çalışmadı. Sessizdi çünkü mevcut test `suspendActiveListings()`'i
         * doğrudan çağırıyordu; tetikleyicinin kendisi hiç test edilmemişti
         * (bkz. IlanYayinDurumuTest — artık uçtan uca mühürlü).
         */
        $old = $user->getRawOriginal('status');
        $new = $user->status;

        if ($old === UserStatus::Aktif->value && $new !== UserStatus::Aktif) {
            $this->suspendActiveListings($user);
        }
    }

    /**
     * Kullanıcının aktif ilanlarını "pasif" durumuna geçir.
     * Testlerde doğrudan çağrılabilmesi için public metoda çıkarıldı.
     */
    public function suspendActiveListings(User $user): int
    {
        return $user->listings()
            ->where('status', ListingStatus::Aktif->value)
            ->update(['status' => ListingStatus::Pasif->value]);
    }
}
