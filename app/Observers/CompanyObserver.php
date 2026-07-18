<?php

namespace App\Observers;

use App\Models\Company;
use App\Services\ImageService;

/**
 * Şirket silindiğinde diskte öksüz kalan görselleri temizler:
 *  - logo_path'in thumb/medium/large varyantları,
 *  - tüm galeri görsellerinin varyantları.
 *
 * Neden gerekli: company_gallery_images FK'si cascadeOnDelete olduğu için
 * Company silinince galeri SATIRLARI veritabanı seviyesinde silinir — bu
 * yol Eloquent'i baypas ettiğinden hiçbir model event'i tetiklenmez ve
 * dosyalar diskte kalır. Bu yüzden dosyaları BURADA (Company deleting
 * hook'unda) elle siliyoruz; satırların kendisi cascade ile gider.
 *
 * Tekil galeri/logo silme zaten kendi controller'larında (CompanyGallery
 * Controller::destroy, CompanyController::update) doğru temizleniyor —
 * bu observer yalnızca "tüm şirketi sil" yolundaki boşluğu kapatır.
 */
class CompanyObserver
{
    public function deleting(Company $company): void
    {
        $imageService = app(ImageService::class);

        if ($company->logo_path) {
            $imageService->deleteVariants(
                array_values($imageService->siblingVariantPaths($company->logo_path))
            );
        }

        foreach ($company->galleryImages as $image) {
            $imageService->deleteVariants($image->variantPaths());
        }
    }
}
