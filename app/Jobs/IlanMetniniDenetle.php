<?php

namespace App\Jobs;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Notifications\ListingFlaggedNotification;
use App\Services\DolandiricilikTespiti;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * İlan metnini dolandırıcılık deseni için arka planda denetler.
 *
 * KUYRUKTA, İSTEK İÇİNDE DEĞİL: bir AI çağrısı, ilan kaydetmeyi saniyelerce
 * bekletirdi. Görsel moderasyonu da (ProcessListingImage) aynı yerde duruyor.
 *
 * İki seviyeli sonuç — gerekçesi DolandiricilikTespiti'nde yazılı:
 *   AĞIR  → yayındaki ilan Beklemede'ye alınır, sahibi bilgilendirilir
 *   HAFİF → ilan yayında kalır, yalnız panelde işaretlenir
 *
 * Denetimin KENDİSİ de kaydediliyor (`fraud_checked_at`): "bakıldı ve temiz"
 * ile "hiç bakılmadı" aynı şey değil.
 */
class IlanMetniniDenetle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $listingId) {}

    public function handle(DolandiricilikTespiti $tespit): void
    {
        $listing = Listing::find($this->listingId);

        if (! $listing) {
            return;
        }

        $sonuc = $tespit->kontrolEt($listing);

        // Fail-open: AI kapalı ya da çağrı başarısız. Damga bile basmıyoruz —
        // `fraud_checked_at` "denetlendi" demek, "denenip başarısız olundu"
        // değil. Yanlış damga, denetlenmemiş ilanı denetlenmiş gösterirdi.
        if ($sonuc === null) {
            return;
        }

        $kategori = $sonuc['flagged'] ? $sonuc['reason'] : null;

        $listing->forceFill([
            'fraud_reason' => $kategori,
            'fraud_checked_at' => now(),
        ])->save();

        if ($kategori === null) {
            return;
        }

        activity('listing')
            ->performedOn($listing)
            ->withProperties(['kategori' => $kategori, 'agir' => $tespit->agirMi($kategori)])
            ->log('AI metin denetimi şüpheli desen buldu');

        /*
         * YALNIZ AĞIR kategoride yayından düşürüyoruz. "Kapora alınır" gibi
         * ifadeler kiralamada sıradan; hafif bir yanlış alarmda dürüst bir ev
         * sahibinin ilanını kapatmak, korumaktan çok zarar verirdi.
         */
        if ($tespit->agirMi($kategori) && $listing->status === ListingStatus::Aktif) {
            $listing->update(['status' => ListingStatus::Beklemede]);

            $listing->user?->notify(new ListingFlaggedNotification(
                $listing->title,
                route('panel.listings.index'),
            ));
        }
    }
}
