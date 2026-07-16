<?php

namespace App\Http\Controllers;

use App\Services\ListingVisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Kamera-önce hızlı ilan (Faz M3). Fotoğraf çek → Claude görüntü analizi →
 * öneriler normal ilan formuna önceden doldurulur. Kullanıcı onaylamadan
 * hiçbir şey yayınlanmaz. Analiz kapalıysa/başarısızsa akış zarifçe normal
 * forma düşer.
 */
class QuickListingController extends Controller
{
    public function __construct(private readonly ListingVisionService $vision) {}

    /** Kamera-önce tek ekran (mobil öncelikli). */
    public function create(): View|RedirectResponse
    {
        // Özellik kapalıysa doğrudan normal ürün formuna yönlendir.
        if (! $this->vision->isEnabled()) {
            return redirect()->route('panel.listings.create', ['tip' => 'urun']);
        }

        return view('panel.listings.quick');
    }

    /** Fotoğrafı analiz et, önerileri forma taşı. */
    public function analyze(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $target = redirect()->route('panel.listings.create', ['tip' => 'urun']);

        if (! $this->vision->isEnabled()) {
            return $target;
        }

        $suggestion = $this->vision->analyze($request->file('photo')->getRealPath());

        if ($suggestion === null) {
            return $target->with('status', 'Fotoğraf otomatik okunamadı — bilgileri elle doldurabilirsin.');
        }

        // Durum bilgisini açıklamanın sonuna küçük bir not olarak ekle.
        $description = $suggestion['description'];
        if ($suggestion['condition']) {
            $description = trim($description."\n\nDurum: ".$suggestion['condition']);
        }

        // old() üzerinden forma taşı — form-fields partial'ı zaten old() okur.
        return $target
            ->withInput([
                'type' => 'urun',
                'title' => Str::limit($suggestion['title'], 250, ''),
                'category_id' => $suggestion['category_id'],
                'description' => Str::limit($description, 4900, ''),
                'price' => $suggestion['price'],
            ])
            ->with('quick_prefill', true);
    }
}
