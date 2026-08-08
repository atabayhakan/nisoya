<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Models\ListingPropertyDetail;
use App\Models\ListingUnavailableRange;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PropertyBrowseController extends Controller
{
    /** /emlak vitrini: emlak ilanları + dikeye özgü filtreler. */
    public function index(Request $request): View
    {
        // gercek(): örnek ilan sayaca ve listeye karışmaz (bkz. Listing::scopeGercek).
        $query = Listing::query()->active()->gercek()
            ->where('type', 'emlak')
            ->with(['coverImage', 'category', 'country', 'user', 'propertyDetail']);

        if ($keyword = trim((string) $request->input('q'))) {
            $query->where(function ($sub) use ($keyword) {
                $sub->where('title', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // Kategori sekmeleri (kiralik-konut / satilik-konut / kisa-donem-tatil / oda-ev-arkadasi)
        $activeCategory = null;
        if ($catSlug = $request->string('kategori')->toString()) {
            $activeCategory = Category::query()
                ->where('slug', $catSlug)
                ->where('type', 'emlak')
                ->first();

            if ($activeCategory) {
                $query->where('category_id', $activeCategory->id);
            }
        }

        if ($country = $request->string('ulke')->toString()) {
            $query->where('country_code', $country);
        }

        if ($city = trim((string) $request->input('sehir'))) {
            $query->where('city', 'like', "%{$city}%");
        }

        if ($request->filled('min')) {
            $query->where('price', '>=', (float) $request->input('min'));
        }

        if ($request->filled('max')) {
            $query->where('price', '<=', (float) $request->input('max'));
        }

        if ($rooms = $request->string('oda')->toString()) {
            if (in_array($rooms, ListingPropertyDetail::ROOM_OPTIONS, true)) {
                $query->whereHas('propertyDetail', fn ($d) => $d->where('rooms', $rooms));
            }
        }

        if ($request->filled('min_m2')) {
            $query->whereHas('propertyDetail', fn ($d) => $d->where('area_m2', '>=', (int) $request->input('min_m2')));
        }

        if ($request->filled('max_m2')) {
            $query->whereHas('propertyDetail', fn ($d) => $d->where('area_m2', '<=', (int) $request->input('max_m2')));
        }

        if ($request->boolean('esyali')) {
            $query->whereHas('propertyDetail', fn ($d) => $d->where('furnished', true));
        }

        // Kısa dönem: seçilen giriş-çıkış aralığı takvimde tamamen boş olmalı
        [$checkIn, $checkOut] = $this->dateRange($request);
        if ($checkIn && $checkOut) {
            $query->whereDoesntHave(
                'unavailableRanges',
                fn ($r) => ListingUnavailableRange::overlapWhere($r, $checkIn, $checkOut),
            );
        }

        if ($guests = (int) $request->input('kisi')) {
            $query->whereHas('propertyDetail', fn ($d) => $d->where('max_guests', '>=', $guests));
        }

        $query->orderByFeatured();

        match ($request->string('sirala')->toString()) {
            'fiyat_artan' => $query->orderBy('price'),
            'fiyat_azalan' => $query->orderByDesc('price'),
            default => $query->latest(),
        };

        $listings = $query->paginate(12)->withQueryString();

        return view('properties.index', [
            'listings' => $listings,
            'categories' => Category::query()
                ->where('type', 'emlak')
                ->whereNotNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'roomOptions' => ListingPropertyDetail::ROOM_OPTIONS,
            'activeCategory' => $activeCategory,
            'filters' => [
                'q' => $request->input('q', ''),
                'kategori' => $activeCategory->slug ?? '',
                'ulke' => $request->input('ulke', ''),
                'sehir' => $request->input('sehir', ''),
                'min' => $request->input('min', ''),
                'max' => $request->input('max', ''),
                'oda' => $request->input('oda', ''),
                'min_m2' => $request->input('min_m2', ''),
                'max_m2' => $request->input('max_m2', ''),
                'esyali' => $request->boolean('esyali'),
                'giris' => $checkIn ?? '',
                'cikis' => $checkOut ?? '',
                'kisi' => $request->input('kisi', ''),
                'sirala' => $request->input('sirala', ''),
            ],
        ]);
    }

    /** Geçerli (giriş < çıkış, bugünden ileri) tarih aralığı; yoksa [null, null]. */
    private function dateRange(Request $request): array
    {
        $in = $request->string('giris')->toString();
        $out = $request->string('cikis')->toString();

        if (! $in || ! $out) {
            return [null, null];
        }

        try {
            $inDate = Carbon::parse($in)->toDateString();
            $outDate = Carbon::parse($out)->toDateString();
        } catch (\Throwable) {
            return [null, null];
        }

        return $inDate < $outDate ? [$inDate, $outDate] : [null, null];
    }
}
