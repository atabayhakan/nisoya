<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\PriceUnit;
use App\Http\Requests\ListingRequest;
use App\Jobs\ProcessListingImage;
use App\Models\Category;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Listing;
use App\Services\GeocodingService;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ListingController extends Controller
{
    /** Üyenin kendi ilanları (panel). */
    public function index(Request $request): View
    {
        $listings = $request->user()->listings()
            ->with('coverImage')
            ->withExists(['featureRequests as has_pending_feature' => fn ($q) => $q->where('status', 'beklemede')])
            ->latest()
            ->paginate(12);

        return view('panel.listings.index', compact('listings'));
    }

    public function create(Request $request): View
    {
        $type = $request->query('tip') === 'urun' ? 'urun' : 'hizmet';

        return view('panel.listings.create', $this->formData($type));
    }

    public function store(ListingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = $data['type'] ?? 'hizmet';
        $coords = app(GeocodingService::class)->locate($data['city'] ?? null, $data['country_code']);

        $listing = $request->user()->listings()->create([
            'type' => $type,
            'title' => $data['title'],
            'slug' => $this->makeSlug($data['title']),
            'description' => $data['description'],
            'category_id' => $data['category_id'],
            'price' => $data['price'] ?? null,
            'currency' => $data['currency'],
            'price_unit' => $data['price_unit'],
            'country_code' => $data['country_code'],
            'city' => $data['city'] ?? null,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'is_remote' => $type === 'hizmet' ? $request->boolean('is_remote') : false,
            'stock' => $type === 'urun' ? ($data['stock'] ?? null) : null,
            'status' => ListingStatus::Aktif->value,
        ]);

        $this->storeImages($listing, $request);

        return redirect()->route('panel.listings.index')
            ->with('status', $type === 'urun' ? 'Ürün ilanın yayınlandı! 🎉' : 'İlanın yayınlandı! 🎉');
    }

    public function edit(Listing $listing): View
    {
        Gate::authorize('update', $listing);

        return view('panel.listings.edit', array_merge(
            ['listing' => $listing->load('images')],
            $this->formData($listing->type->value),
        ));
    }

    public function update(ListingRequest $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $data = $request->validated();
        $coords = app(GeocodingService::class)->locate($data['city'] ?? null, $data['country_code']);

        $listing->update([
            'title' => $data['title'],
            'slug' => $this->makeSlug($data['title']),
            'description' => $data['description'],
            'category_id' => $data['category_id'],
            'price' => $data['price'] ?? null,
            'currency' => $data['currency'],
            'price_unit' => $data['price_unit'],
            'country_code' => $data['country_code'],
            'city' => $data['city'] ?? null,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'is_remote' => $listing->type->value === 'hizmet' ? $request->boolean('is_remote') : false,
            'stock' => $listing->type->value === 'urun' ? ($data['stock'] ?? null) : null,
        ]);

        // İşaretlenen görselleri sil (tüm varyantlarıyla)
        $imageService = app(ImageService::class);
        foreach ((array) $request->input('delete_images', []) as $imageId) {
            $image = $listing->images()->find($imageId);
            if ($image) {
                $imageService->deleteVariants($image->variantPaths());
                $image->delete();
            }
        }

        $this->storeImages($listing, $request);
        $this->ensureCover($listing);

        return redirect()->route('panel.listings.index')
            ->with('status', 'İlan güncellendi.');
    }

    public function destroy(Listing $listing): RedirectResponse
    {
        Gate::authorize('delete', $listing);

        $imageService = app(ImageService::class);
        foreach ($listing->images as $image) {
            $imageService->deleteVariants($image->variantPaths());
        }

        $listing->delete(); // listing_images cascade ile silinir

        return redirect()->route('panel.listings.index')
            ->with('status', 'İlan silindi.');
    }

    /** Herkese açık ilan detayı. */
    public function show(Request $request, Listing $listing, ?string $slug = null): View
    {
        $isOwner = $request->user()?->id === $listing->user_id;

        if ($listing->status !== ListingStatus::Aktif && ! $isOwner) {
            abort(404);
        }

        if (! $isOwner) {
            $listing->increment('views_count');
        }

        $listing->load(['images', 'user', 'category', 'country']);

        $isFavorited = $request->user()
            ? $request->user()->favorites()->where('listing_id', $listing->id)->exists()
            : false;

        $sellerReviews = $listing->user->reviewsReceived()->where('status', 'yayinda');
        $sellerRating = [
            'avg' => round((float) $sellerReviews->avg('rating'), 1),
            'count' => $sellerReviews->count(),
        ];

        return view('listings.show', compact('listing', 'isOwner', 'isFavorited', 'sellerRating'));
    }

    /** Form için ortak veri (tipe göre filtreli kategoriler, para birimleri, ülkeler). */
    protected function formData(string $type = 'hizmet'): array
    {
        return [
            'type' => $type,
            'categories' => Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->whereIn('type', [$type, 'ikisi'])
                ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get(),
            'currencies' => Currency::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'priceUnits' => PriceUnit::forType($type),
        ];
    }

    protected function makeSlug(string $title): string
    {
        $slug = Str::slug($title);

        return $slug !== '' ? $slug : 'ilan';
    }

    protected function storeImages(Listing $listing, Request $request): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        // Ağır iş (EXIF çıkarma + 3 varyant üretimi) HTTP isteği içinde değil,
        // kuyruk worker'ında yapılır — 1 vCPU'luk üretim sunucusunda çoklu
        // görselli bir ilan yüklemesinin isteği bloke etmesini önler.
        // Ham dosya, işlenene kadar hiçbir zaman public diske yazılmaz
        // (gizlilik: EXIF/GPS temizlenmeden hiçbir görsel yayınlanmaz).
        $hasCover = $listing->images()->where('is_cover', true)->exists();
        $order = (int) $listing->images()->max('sort_order');

        foreach ($request->file('images') as $file) {
            $rawPath = $file->store('pending-listing-images', 'local');
            $order++;

            ProcessListingImage::dispatch(
                listingId: $listing->id,
                rawPath: $rawPath,
                rawDisk: 'local',
                sortOrder: $order,
                isCover: ! $hasCover,
                causerId: $request->user()?->id,
            );

            $hasCover = true;
        }
    }

    /** En az bir kapak görseli olmasını garanti et. */
    protected function ensureCover(Listing $listing): void
    {
        if ($listing->images()->where('is_cover', true)->exists()) {
            return;
        }

        $first = $listing->images()->orderBy('sort_order')->first();
        $first?->update(['is_cover' => true]);
    }
}
