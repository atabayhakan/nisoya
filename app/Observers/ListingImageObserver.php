<?php

namespace App\Observers;

use App\Models\ListingImage;
use App\Services\ImageService;

/**
 * ListingImage kaydı silindiğinde (tekil veya admin panelden toplu),
 * thumb/medium/large varyant dosyalarını da diskten temizler. Aksi halde
 * DB kaydı gider ama dosyalar diskte öksüz kalır — GPS/EXIF içeren bir
 * görsel KVKK amacıyla silinse bile fiziksel dosya kalmaya devam eder.
 */
class ListingImageObserver
{
    public function deleting(ListingImage $listingImage): void
    {
        app(ImageService::class)->deleteVariants($listingImage->variantPaths());
    }
}
