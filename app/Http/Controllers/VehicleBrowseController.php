<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Models\ListingUnavailableRange;
use App\Models\ListingVehicleDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class VehicleBrowseController extends Controller
{
    /** /vasita vitrini: araç ilanları + dikeye özgü filtreler. */
    public function index(Request $request): View
    {
        // gercek(): örnek ilan sayaca ve listeye karışmaz (bkz. Listing::scopeGercek).
        $query = Listing::query()->active()->gercek()
            ->where('type', 'vasita')
            ->with(['coverImage', 'category', 'country', 'user', 'vehicleDetail']);

        if ($keyword = trim((string) $request->input('q'))) {
            $query->where(function ($sub) use ($keyword) {
                $sub->where('title', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // Kategori sekmeleri (satilik-arac / kiralik-arac)
        $activeCategory = null;
        if ($catSlug = $request->string('kategori')->toString()) {
            $activeCategory = Category::query()
                ->where('slug', $catSlug)
                ->where('type', 'vasita')
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

        if ($brand = trim((string) $request->input('marka'))) {
            $query->whereHas('vehicleDetail', fn ($d) => $d->where('brand', 'like', "%{$brand}%"));
        }

        if ($request->filled('min_yil')) {
            $query->whereHas('vehicleDetail', fn ($d) => $d->where('year', '>=', (int) $request->input('min_yil')));
        }

        if ($request->filled('max_km')) {
            $query->whereHas('vehicleDetail', fn ($d) => $d->where('mileage_km', '<=', (int) $request->input('max_km')));
        }

        if ($fuel = $request->string('yakit')->toString()) {
            if (array_key_exists($fuel, ListingVehicleDetail::FUELS)) {
                $query->whereHas('vehicleDetail', fn ($d) => $d->where('fuel', $fuel));
            }
        }

        if ($transmission = $request->string('vites')->toString()) {
            if (array_key_exists($transmission, ListingVehicleDetail::TRANSMISSIONS)) {
                $query->whereHas('vehicleDetail', fn ($d) => $d->where('transmission', $transmission));
            }
        }

        if ($request->filled('min')) {
            $query->where('price', '>=', (float) $request->input('min'));
        }

        if ($request->filled('max')) {
            $query->where('price', '<=', (float) $request->input('max'));
        }

        // Kiralık: seçilen tarih aralığı takvimde tamamen boş olmalı
        [$checkIn, $checkOut] = $this->dateRange($request);
        if ($checkIn && $checkOut) {
            $query->whereDoesntHave(
                'unavailableRanges',
                fn ($r) => ListingUnavailableRange::overlapWhere($r, $checkIn, $checkOut),
            );
        }

        $query->orderByFeatured();

        match ($request->string('sirala')->toString()) {
            'fiyat_artan' => $query->orderBy('price'),
            'fiyat_azalan' => $query->orderByDesc('price'),
            default => $query->latest(),
        };

        $listings = $query->paginate(12)->withQueryString();

        return view('vehicles.index', [
            'listings' => $listings,
            'categories' => Category::query()
                ->where('type', 'vasita')
                ->whereNotNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'fuels' => ListingVehicleDetail::FUELS,
            'transmissions' => ListingVehicleDetail::TRANSMISSIONS,
            'activeCategory' => $activeCategory,
            'filters' => [
                'q' => $request->input('q', ''),
                'kategori' => $activeCategory->slug ?? '',
                'ulke' => $request->input('ulke', ''),
                'sehir' => $request->input('sehir', ''),
                'marka' => $request->input('marka', ''),
                'min_yil' => $request->input('min_yil', ''),
                'max_km' => $request->input('max_km', ''),
                'yakit' => $request->input('yakit', ''),
                'vites' => $request->input('vites', ''),
                'min' => $request->input('min', ''),
                'max' => $request->input('max', ''),
                'giris' => $checkIn ?? '',
                'cikis' => $checkOut ?? '',
                'sirala' => $request->input('sirala', ''),
            ],
        ]);
    }

    /** Geçerli (başlangıç < bitiş) tarih aralığı; yoksa [null, null]. */
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
