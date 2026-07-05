<?php

namespace App\Http\Controllers;

use App\Models\CompanyGalleryImage;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyGalleryController extends Controller
{
    public const MAX_ITEMS = 6;

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->company;

        abort_if(! $company, 403, 'Önce şirket profilini oluşturmalısın.');

        if ($company->galleryImages()->count() >= self::MAX_ITEMS) {
            return back()->withErrors(['image' => 'En fazla '.self::MAX_ITEMS.' galeri görseli ekleyebilirsin.']);
        }

        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'caption' => ['nullable', 'string', 'max:120'],
        ], attributes: ['image' => 'görsel', 'caption' => 'açıklama']);

        $imageService = app(ImageService::class);
        try {
            $result = $imageService->storeOptimized($request->file('image'), 'company-gallery', 1200, 85);
        } catch (\RuntimeException) {
            return back()->withErrors(['image' => 'Görsel işlenemedi, lütfen başka bir dosyayla tekrar dene.']);
        }

        $company->galleryImages()->create([
            'path_thumb' => $result['thumb'],
            'path_medium' => $result['medium'],
            'path_large' => $result['large'],
            'caption' => $data['caption'] ?? null,
            'sort_order' => $company->galleryImages()->count(),
        ]);

        return back()->with('status', 'Galeri görseli eklendi.');
    }

    public function destroy(Request $request, CompanyGalleryImage $companyGalleryImage): RedirectResponse
    {
        abort_if($companyGalleryImage->company->user_id !== $request->user()->id, 403);

        app(ImageService::class)->deleteVariants($companyGalleryImage->variantPaths());
        $companyGalleryImage->delete();

        return back()->with('status', 'Galeri görseli kaldırıldı.');
    }
}
