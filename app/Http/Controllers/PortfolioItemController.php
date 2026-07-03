<?php

namespace App\Http\Controllers;

use App\Models\PortfolioItem;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Kullanıcının profil portfolyosuna (geçmiş iş örnekleri) görsel
 * ekleyip kaldırdığı uçlar. Bkz. PaymentLinkController — aynı desen.
 */
class PortfolioItemController extends Controller
{
    public const MAX_ITEMS = 6;

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->portfolioItems()->count() >= self::MAX_ITEMS) {
            return back()->withErrors(['image' => 'En fazla '.self::MAX_ITEMS.' portfolyo görseli ekleyebilirsin.']);
        }

        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'caption' => ['nullable', 'string', 'max:120'],
        ], attributes: [
            'image' => 'görsel', 'caption' => 'açıklama',
        ]);

        $imageService = app(ImageService::class);

        try {
            $result = $imageService->storeOptimized($request->file('image'), 'portfolio', 1200, 85);
        } catch (\RuntimeException) {
            return back()->withErrors(['image' => 'Görsel işlenemedi, lütfen başka bir dosyayla tekrar dene.']);
        }

        $user->portfolioItems()->create([
            'path_thumb' => $result['thumb'],
            'path_medium' => $result['medium'],
            'path_large' => $result['large'],
            'caption' => $data['caption'] ?? null,
            'sort_order' => $user->portfolioItems()->count(),
        ]);

        return back()->with('status', 'Portfolyo görseli eklendi.');
    }

    public function destroy(Request $request, PortfolioItem $portfolioItem): RedirectResponse
    {
        abort_if($portfolioItem->user_id !== $request->user()->id, 403);

        $portfolioItem->delete();

        return back()->with('status', 'Portfolyo görseli kaldırıldı.');
    }
}
