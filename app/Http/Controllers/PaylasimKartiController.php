<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Services\PaylasimKartiUretici;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * İlanın WhatsApp durumu için 1080×1920 paylaşım kartını sunar.
 *
 * Görünürlük kuralı ilan detayının AYNISI (ListingController::show): aktif
 * olmayan ilanın kartını yalnız sahibi alabilir. Ayrı bir kural yazmak,
 * yayından kaldırılmış bir ilanın kartının erişilebilir kalmasına yol açardı.
 *
 * Kart diske önbelleklenir ve dosya adı ilanın imzasını taşır (bkz.
 * PaylasimKartiUretici::yol) — yani içerik değişince URL de değişir, bu
 * yüzden yanıtı uzun süre önbelleğe vermek güvenli.
 */
class PaylasimKartiController extends Controller
{
    public function __invoke(Request $request, Listing $listing, PaylasimKartiUretici $uretici): BinaryFileResponse
    {
        abort_unless(
            $listing->status === ListingStatus::Aktif || $request->user()?->id === $listing->user_id,
            404
        );

        $listing->loadMissing(['coverImage', 'images', 'category', 'country']);

        $yol = $uretici->hazirla($listing);

        return response()
            ->file(Storage::disk('public')->path($yol), [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=604800',
                // İndirme yolunda (Web Share API yoksa) dosya anlamlı bir adla
                // kaydedilsin; tarayıcı sekmesinde açıldığında da aynı ad görünür.
                'Content-Disposition' => 'inline; filename="nisoya-'.$listing->slug.'.png"',
            ]);
    }
}
