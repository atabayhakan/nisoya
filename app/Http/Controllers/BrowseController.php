<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrowseController extends Controller
{
    public function index(Request $request): View
    {
        return $this->render($request, null);
    }

    public function category(Request $request, Category $category): View
    {
        return $this->render($request, $category);
    }

    protected function render(Request $request, ?Category $category): View
    {
        $query = Listing::query()->active()->with(['coverImage', 'category.parent', 'country', 'user']);

        // Kategori: route parametresi öncelikli, yoksa query string (?kategori=slug)
        $activeCategory = $category
            ?: ($request->filled('kategori')
                ? Category::query()->where('slug', $request->string('kategori'))->first()
                : null);

        if ($activeCategory) {
            $ids = collect([$activeCategory->id])
                ->merge($activeCategory->children()->pluck('id'))
                ->all();
            $query->whereIn('category_id', $ids);
        }

        if ($keyword = trim((string) $request->input('q'))) {
            $query->where(function ($sub) use ($keyword) {
                $sub->where('title', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
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

        if ($request->boolean('uzaktan')) {
            $query->where('is_remote', true);
        }

        $type = $request->string('tip')->toString();
        if (in_array($type, ['hizmet', 'urun', 'emlak'], true)) {
            $query->where('type', $type);
        }

        // Öne çıkanlar her zaman üstte, sonra seçilen sıralama
        $query->orderByDesc('is_featured');

        match ($request->string('sirala')->toString()) {
            'fiyat_artan' => $query->orderBy('price'),
            'fiyat_azalan' => $query->orderByDesc('price'),
            'populer' => $query->orderByDesc('views_count'),
            default => $query->latest(),
        };

        $listings = $query->paginate(12)->withQueryString();

        return view('listings.index', [
            'listings' => $listings,
            'categories' => Category::query()->whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->get(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'activeCategory' => $activeCategory,
            'filters' => [
                'q' => $request->input('q', ''),
                'kategori' => $activeCategory?->slug ?? '',
                'ulke' => $request->input('ulke', ''),
                'sehir' => $request->input('sehir', ''),
                'min' => $request->input('min', ''),
                'max' => $request->input('max', ''),
                'uzaktan' => $request->boolean('uzaktan'),
                'sirala' => $request->input('sirala', ''),
                'tip' => in_array($type, ['hizmet', 'urun', 'emlak'], true) ? $type : '',
            ],
        ]);
    }
}
