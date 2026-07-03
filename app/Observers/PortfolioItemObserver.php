<?php

namespace App\Observers;

use App\Models\PortfolioItem;
use App\Services\ImageService;

/**
 * PortfolioItem kaydı silindiğinde thumb/medium/large varyant
 * dosyalarını da diskten temizler (bkz. ListingImageObserver — aynı desen).
 */
class PortfolioItemObserver
{
    public function deleting(PortfolioItem $portfolioItem): void
    {
        app(ImageService::class)->deleteVariants($portfolioItem->variantPaths());
    }
}
