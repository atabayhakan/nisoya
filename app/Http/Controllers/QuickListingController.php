<?php

namespace App\Http\Controllers;

use App\Services\ListingTextService;
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
    public function __construct(
        private readonly ListingVisionService $vision,
        private readonly ListingTextService $text,
    ) {}

    /** Kamera-önce tek ekran (mobil öncelikli). */
    public function create(): View|RedirectResponse
    {
        // İKİSİ DE kapalıysa normal forma düş. Biri açıksa ekran açılır ve
        // yalnız açık olan kapı gösterilir — özelliğin yarısı kapalıyken
        // diğer yarısını da kaybetmek gereksiz.
        if (! $this->vision->isEnabled() && ! $this->text->isEnabled()) {
            return redirect()->route('panel.listings.create', ['tip' => 'urun']);
        }

        return view('panel.listings.quick', [
            'fotografAcik' => $this->vision->isEnabled(),
            'metinAcik' => $this->text->isEnabled(),
        ]);
    }

    /**
     * Serbest metinden taslak: birkaç kelime ya da WhatsApp'tan yapıştırılan
     * hazır ilan metni. Fotoğraf yolunun metin ikizi — aynı hedefe, aynı
     * `withInput()` sözleşmesiyle gider.
     */
    public function analyzeText(Request $request): RedirectResponse
    {
        $request->validate([
            'metin' => ['required', 'string', 'min:3', 'max:4000'],
        ], [], ['metin' => 'metin']);

        $target = redirect()->route('panel.listings.create', ['tip' => 'urun']);

        if (! $this->text->isEnabled()) {
            return $target;
        }

        $suggestion = $this->text->analyze($request->string('metin')->toString());

        if ($suggestion === null) {
            return $target->with('status', 'Metinden ilan çıkaramadık — bilgileri elle doldurabilirsin.');
        }

        return $this->formaTasi($suggestion, $suggestion['type']);
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

        return $this->formaTasi($suggestion, 'urun');
    }

    /**
     * Öneriyi normal ilan formuna taşır — fotoğraf ve metin yollarının ORTAK
     * çıkışı.
     *
     * Ayrı yazılsaydı iki yerde bakılırdı ve biri güncellenip diğeri
     * unutulurdu; alan adları (`title`, `category_id`, …) form-fields
     * partial'ının `old()` sözleşmesine bağlı ve o sözleşme tek yerde
     * durmalı.
     *
     * @param  array{title: string, category_id: ?int, description: string, price: ?float, condition: ?string}  $suggestion
     */
    private function formaTasi(array $suggestion, string $tip): RedirectResponse
    {
        // Durum bilgisini açıklamanın sonuna küçük bir not olarak ekle.
        $description = $suggestion['description'];
        if ($suggestion['condition']) {
            $description = trim($description."\n\nDurum: ".$suggestion['condition']);
        }

        // old() üzerinden forma taşı — form-fields partial'ı zaten old() okur.
        return redirect()
            ->route('panel.listings.create', ['tip' => $tip])
            ->withInput([
                'type' => $tip,
                'title' => Str::limit($suggestion['title'], 250, ''),
                'category_id' => $suggestion['category_id'],
                'description' => Str::limit($description, 4900, ''),
                'price' => $suggestion['price'],
            ])
            ->with('quick_prefill', true);
    }
}
