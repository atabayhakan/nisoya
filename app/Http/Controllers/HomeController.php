<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\HomeHighlight;
use App\Models\Listing;
use App\Services\NabizService;
use App\Support\CategoryIcon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(NabizService $nabiz): View
    {
        return view('home', [
            'categories' => Category::query()->whereNull('parent_id')->where('is_active', true)
                ->orderBy('sort_order')->get(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'stats' => [
                'countries' => Country::query()->where('is_active', true)->count(),
                'categories' => Category::query()->where('is_active', true)->count(),
                'cities' => City::query()->count(),

                // Vitrin hero'nun kanıt satırı için: katalog büyüklüğü değil
                // GERÇEK HAREKET. 'categories' (97) ve 'cities' (ülke başına
                // 2 tohumlanmış) ziyaretçiye bir şey söylemiyor; "kaç aktif
                // ilan var" ve "kaç şehirde ilan var" söylüyor.
                // Şehir sayımı LOWER(TRIM(city)) üzerinden: listings.city
                // serbest metin, ham distinct 'Berlin'/'berlin'/'Berlin ' → 3 sayardı.
                'activeListings' => Listing::query()->active()->count(),
                'activeCities' => Listing::query()->active()
                    ->whereNotNull('city')
                    ->where('city', '!=', '')
                    ->distinct()
                    ->count(DB::raw('LOWER(TRIM(city))')),
            ],
            'latestListings' => Listing::query()->active()
                ->with(['coverImage', 'category.parent', 'country', 'user'])
                ->latest()
                ->take(8)
                ->get(),
            'activityFeed' => Listing::query()->active()
                ->with(['category.parent', 'country', 'user'])
                ->latest()
                ->take(10)
                ->get()
                // listings.category_id NULLABLE ve nullOnDelete: bir kategori
                // silinince ilanları kategorisiz kalır. Eskiden aşağıdaki
                // `$listing->category->name` bu durumda ANA SAYFAYI 500'e
                // düşürüyordu (bir alt satırdaki ikon okuması ?-> ile
                // korunmuştu, bu değildi). Akış satırının anlamı zaten
                // kategori olduğu için kategorisiz ilan akışa hiç girmez.
                ->filter(fn (Listing $listing) => $listing->category !== null && $listing->user !== null)
                ->values()
                ->map(fn (Listing $listing) => [
                    'firstName' => Str::before($listing->user->name, ' '),
                    'categoryName' => $listing->category->name,
                    'categoryIcon' => CategoryIcon::heroicon($listing->category?->parent?->icon ?? $listing->category?->icon),
                    'flag' => $listing->country?->emoji,
                    'place' => $listing->city ?: $listing->country?->name_tr,
                    'timeAgo' => $listing->created_at->diffForHumans(),
                    // "Canlı akış" başlığı ancak gerçekten taze içerik varken
                    // dürüsttür. Blade'de diffForHumans() STRING'inden yaş
                    // çıkarılamaz; ham karar burada verilir.
                    'taze' => $listing->created_at->gt(now()->subDay()),
                    'href' => route('listings.show', [$listing, $listing->slug]),
                ]),

            // Hero çipleri: SERBEST METİN DEĞİL, gerçekten ilanı olan
            // kategoriler. Eski çipler `?q=` ile başlık/açıklama araması
            // yapıyordu (BrowseController:42) — "taşınma" çipi "Nakliyat &
            // Taşınma" kategorisindeki ilanları GETİRMİYORDU, ilk tıklamada
            // boş sayfa dönüyordu. Artık `?kategori=` ile gerçek kategori
            // eşleşmesi yapılır ve liste yalnız DOLU kategorilerden kurulur,
            // yani boş sonuç yapısal olarak imkânsız.
            'heroCips' => Category::query()
                ->where('is_active', true)
                ->withCount(['listings' => fn ($q) => $q->active()])
                ->orderByDesc('listings_count')
                ->take(4)
                ->get()
                ->filter(fn (Category $c) => $c->listings_count > 0)
                ->values(),
            'nabizGoal' => $nabiz->goalProgress(),
            'nabizAmbassadors' => $nabiz->cityAmbassadors(3),
            'pulseCountries' => $nabiz->countryActivity(),
            'bigHighlights' => HomeHighlight::forSlot(HomeHighlight::SLOT_BIG),
            'smallHighlights' => HomeHighlight::forSlot(HomeHighlight::SLOT_SMALL),
        ]);
    }
}
