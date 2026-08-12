<?php

namespace App\Jobs;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Notifications\ListingFlaggedNotification;
use App\Services\ImageModerationService;
use App\Services\ImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * İlan görselini arka planda işler: EXIF çıkarma + orientation düzeltme +
 * thumb/medium/large WebP varyantları üretme. Bu işlem CPU/bellek ağırlıklı
 * olduğu için HTTP isteği içinde değil, kuyruk worker'ında çalışır — 1 vCPU'luk
 * üretim sunucusunda çoklu görselli bir ilan yüklemesinin isteği bloke
 * etmesini önler.
 *
 * Akış: controller ham dosyayı 'local' (özel/genel erişime kapalı) diske
 * kaydeder ve bu job'ı kuyruklar. Job işlemeyi tamamlayınca ListingImage
 * kaydını oluşturur ve ham dosyayı siler. İşleme başarısız olursa hiçbir
 * kayıt oluşturulmaz (gizlilik: ham/EXIF'i temizlenmemiş dosya asla public
 * diske veya DB'ye yazılmaz).
 */
class ProcessListingImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public int $listingId,
        public string $rawPath,
        public string $rawDisk,
        public int $sortOrder,
        public bool $isCover,
        public ?int $causerId = null,
    ) {}

    public function handle(ImageService $imageService, ImageModerationService $moderation): void
    {
        $listing = Listing::find($this->listingId);

        if (! $listing) {
            // İlan (ör. hemen silindi) artık yok — ham dosyayı temizle, işlem yapma.
            Storage::disk($this->rawDisk)->delete($this->rawPath);

            return;
        }

        try {
            $realPath = Storage::disk($this->rawDisk)->path($this->rawPath);
            $result = $imageService->storeOptimizedFromPath($realPath, 'listings');
        } catch (\Throwable $e) {
            Log::error('İlan görseli kuyrukta işlenemedi', [
                'listing_id' => $this->listingId,
                'exception' => $e->getMessage(),
            ]);
            // Kullanıcı bunu ekranda görsün: sessizce kaybolan görsel, en kötü
            // geri bildirimdir. (Bu dal `failed()`'i tetiklemez — istisna
            // yutuluyor — o yüzden işareti burada kendimiz koymak zorundayız.)
            $this->sayaciDusur($listing, hata: true);
            Storage::disk($this->rawDisk)->delete($this->rawPath);

            return;
        }

        $image = $listing->images()->create([
            'path' => $result['large'],
            'path_thumb' => $result['thumb'],
            'path_medium' => $result['medium'],
            'path_large' => $result['large'],
            'width' => $result['original_dimensions']['width'],
            'height' => $result['original_dimensions']['height'],
            'exif_metadata' => $result['exif_metadata'],
            'had_gps' => $result['had_gps'],
            'has_sensitive_exif' => $result['has_sensitive_exif'] ?? false,
            'gps_lat' => $result['gps_lat'] ?? null,
            'gps_lng' => $result['gps_lng'] ?? null,
            'sort_order' => $this->sortOrder,
            'is_cover' => $this->isCover,
        ]);

        try {
            $image->update(['size_bytes' => Storage::disk('public')->size($image->path_large)]);
        } catch (\Throwable) {
            // ignore — boyut okunamazsa devam et
        }

        if ($result['had_gps']) {
            activity('image')
                ->performedOn($image)
                ->causedBy($this->causerId)
                ->withProperties([
                    'listing_id' => $listing->id,
                    'had_gps' => true,
                    'orientation_corrected' => $result['orientation_corrected'],
                ])
                ->log('GPS içeren görsel yüklendi (EXIF temizlendi)');
        }

        $this->moderate($listing, $image, $moderation);

        $this->sayaciDusur($listing);

        Storage::disk($this->rawDisk)->delete($this->rawPath);
    }

    /**
     * Bekleyen görsel sayacını bir azaltır.
     *
     * Arayüz "işleniyor / hata / hazır" ayrımını bu sayaçtan okuyor; sayaç
     * düşmezse ilan sonsuza dek işleniyor görünür. Bu yüzden BAŞARIDA DA,
     * HATADA DA çağrılması şart — çıkışı olan her dalda.
     *
     * `max(0, …)`: sayaç asla negatife düşmesin. Aynı iş iki kez koşarsa
     * (tries=2 + kısmi başarı) negatif bir sayaç "hazır" ile "işleniyor"
     * ayrımını sessizce bozardı.
     */
    private function sayaciDusur(Listing $listing, bool $hata = false): void
    {
        $listing->pending_images = max(0, ($listing->pending_images ?? 0) - 1);

        if ($hata) {
            $listing->images_failed = true;
        }

        $listing->save();
    }

    /**
     * AI ile uygunsuz içerik ön-elemesi. Görsel SİLİNMEZ — yalnızca
     * işaretlenir ve ilan (henüz aktifse) incelemeye alınır; nihai karar
     * admin'e ait. AI kapalı/başarısızsa fail-open (hiçbir şey yapılmaz).
     */
    private function moderate(Listing $listing, $image, ImageModerationService $moderation): void
    {
        $result = $moderation->check(Storage::disk('public')->path($image->path_large));

        if (! $result || ! $result['flagged']) {
            return;
        }

        $image->update(['is_flagged' => true, 'flagged_reason' => $result['reason']]);

        activity('image')
            ->performedOn($image)
            ->withProperties(['listing_id' => $listing->id, 'reason' => $result['reason']])
            ->log('AI moderasyonu görseli uygunsuz olarak işaretledi');

        if ($listing->status === ListingStatus::Aktif) {
            $listing->update(['status' => ListingStatus::Beklemede]);

            $listing->user?->notify(new ListingFlaggedNotification(
                $listing->title,
                route('panel.listings.index'),
            ));
        }
    }

    /**
     * Tüm denemeler tükendi: ham dosyayı bırakma VE kullanıcıya söyle.
     *
     * Sayaç burada düşmezse ilan sonsuza dek "görsel işleniyor" der — yani
     * sessiz kayıp, "sonsuza dek bekleyen" kayba dönüşür. İkisi de yalan.
     */
    public function failed(\Throwable $e): void
    {
        $listing = Listing::find($this->listingId);

        if ($listing) {
            $this->sayaciDusur($listing, hata: true);
        }

        Storage::disk($this->rawDisk)->delete($this->rawPath);
    }
}
