<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Support\Tema;
use Illuminate\Database\Eloquent\Builder;
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
        if (in_array($type, ['hizmet', 'urun', 'emlak', 'vasita'], true)) {
            $query->where('type', $type);
        }

        // Öne çıkanlar (süresi geçmemiş) her zaman üstte, sonra seçilen sıralama
        $query->orderByFeatured();

        match ($request->string('sirala')->toString()) {
            'fiyat_artan' => $query->orderBy('price'),
            'fiyat_azalan' => $query->orderByDesc('price'),
            'populer' => $query->orderByDesc('views_count'),
            default => $query->latest(),
        };

        $listings = $query->paginate(12)->withQueryString();

        // Vitrin (Faz P4) — filtre kolonundaki kategori sayaçları ve fiyat
        // histogramı. Yalnız Vitrin aktifken hesaplanır: klasik tema bunları
        // göstermediği için orada ek sorgu maliyeti doğmaz.
        $kategoriSayaclari = [];
        $fiyatDagilimi = [];

        if (Tema::vitrinMi()) {
            // Sayaç/histogram, KULLANICININ SEÇTİĞİ kategori ve fiyat aralığı
            // HARİÇ diğer tüm filtrelerle hesaplanır — aksi halde "bu
            // kategoride 12 ilan var" derken zaten o kategoriye filtrelenmiş
            // sonucu sayardık ve histogram tek kovaya çökerdi.
            $temel = fn () => tap(Listing::query()->active(), function ($q) use ($request, $type) {
                if ($keyword = trim((string) $request->input('q'))) {
                    $q->where(function ($sub) use ($keyword) {
                        $sub->where('title', 'like', "%{$keyword}%")
                            ->orWhere('description', 'like', "%{$keyword}%");
                    });
                }
                if ($country = $request->string('ulke')->toString()) {
                    $q->where('country_code', $country);
                }
                if ($city = trim((string) $request->input('sehir'))) {
                    $q->where('city', 'like', "%{$city}%");
                }
                if ($request->boolean('uzaktan')) {
                    $q->where('is_remote', true);
                }
                if (in_array($type, ['hizmet', 'urun', 'emlak', 'vasita'], true)) {
                    $q->where('type', $type);
                }
            });

            // 1 sorgu: kategori başına adet (alt kategoriler köke toplanır).
            $kategoriSayaclari = $temel()
                ->selectRaw('category_id, count(*) as adet')
                ->groupBy('category_id')
                ->pluck('adet', 'category_id')
                ->all();

            $fiyatDagilimi = $this->fiyatDagilimi($temel());
        }

        return view('listings.index', [
            'listings' => $listings,
            'kategoriSayaclari' => $kategoriSayaclari,
            'fiyatDagilimi' => $fiyatDagilimi,
            // children eager load: Vitrin filtre kolonu kök kategori sayacına
            // alt kategorileri de topluyor — lazy kalırsa kategori başına bir
            // sorgu (N+1) doğar.
            'categories' => Category::query()->whereNull('parent_id')->where('is_active', true)
                ->with('children:id,parent_id')->orderBy('sort_order')->get(),
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
                'tip' => in_array($type, ['hizmet', 'urun', 'emlak', 'vasita'], true) ? $type : '',
            ],
        ]);
    }

    /**
     * Fiyat histogramı (Vitrin filtre kolonu): fiyatı olan ilanları 9 eşit
     * kovaya böler. 2 sorgu. Anlamlı bir dağılım çıkmıyorsa (fiyatlı ilan yok
     * ya da hepsi aynı fiyatta) BOŞ döner — çağıran taraf bloğu hiç basmaz
     * ("kapalı blok DOM'a basılmaz" kuralı).
     *
     * SQLite + MySQL uyumu: yalnız MIN/MAX/COUNT/CAST kullanılır; kova
     * hesabı PHP tarafında yapılır (FLOOR/veritabanı-özel ifade yok).
     *
     * @param  Builder<Listing>  $temel
     * @return array{kovalar: array<int,int>, min: float, max: float}|array{}
     */
    protected function fiyatDagilimi($temel): array
    {
        // toBase(): toplama sonucu bir Listing modeli değil, düz satırdır —
        // Eloquent builder'da bırakırsak min/max/count sanki model sütunuymuş
        // gibi okunur (PHPStan haklı olarak şikâyet eder).
        $sinirlar = (clone $temel)->whereNotNull('price')->toBase()
            ->selectRaw('min(price) as en_az, max(price) as en_cok, count(*) as adet')
            ->first();

        if (! $sinirlar || (int) $sinirlar->adet === 0) {
            return [];
        }

        $enAz = (float) $sinirlar->en_az;
        $enCok = (float) $sinirlar->en_cok;

        if ($enCok <= $enAz) {
            return [];   // tek fiyat — histogram bilgi taşımaz
        }

        $kovaSayisi = 9;
        $adim = ($enCok - $enAz) / $kovaSayisi;
        $kovalar = array_fill(0, $kovaSayisi, 0);

        // Yalnız fiyat sütunu çekilir; kova ataması PHP'de.
        foreach ((clone $temel)->whereNotNull('price')->pluck('price') as $fiyat) {
            $i = (int) floor(((float) $fiyat - $enAz) / $adim);
            $kovalar[min($i, $kovaSayisi - 1)]++;
        }

        return ['kovalar' => $kovalar, 'min' => $enAz, 'max' => $enCok];
    }
}
