<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\HomeHighlight;
use App\Models\IslemTuru;
use App\Models\Listing;
use App\Services\NabizService;
use App\Services\RehberYuzeyi;
use App\Support\CategoryIcon;
use App\Support\Modules;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Kanıt şeridinin sayıları göstermeye başladığı aktif ilan eşiği.
     * Değer BrowseController'ın sayfa boyutuyla aynı (12) — bir sayfayı bile
     * doldurmayan envanter kendi lehine tanıklık edemez.
     */
    private const SERIT_ILAN_ESIGI = 12;

    public function index(Request $request, NabizService $nabiz, RehberYuzeyi $rehberYuzeyi): View
    {
        /*
         * VİTRİN KURALI (2026-08-01): "Öne çıkan ilanlar" GERÇEK ilan
         * önceliklidir. [ÖRNEK] rozetli demo ilanlarla dolu bir ana sayfa,
         * ilk kez gelen ziyaretçiye "bu site sahte/boş" dedirtir — işaretleme
         * dürüstlüğü doğru, ama vitrine taşınması ayrı bir karar. Demo YALNIZ
         * tek bir gerçek aktif ilan bile yokken gösterilir (tamamen boş bir
         * vitrinden yine de iyidir) ve o durumda da rozetlidir.
         */
        $latestListings = Listing::query()->active()
            ->where('is_demo', false)
            ->with(['coverImage', 'category.parent', 'country', 'user'])
            ->latest()
            ->take(8)
            ->get();

        if ($latestListings->isEmpty()) {
            $latestListings = Listing::query()->active()
                ->with(['coverImage', 'category.parent', 'country', 'user'])
                ->latest()
                ->take(8)
                ->get();
        }

        $rehber = $this->rehberVerisi($request, $rehberYuzeyi);

        $aktifIlan = Listing::query()->active()->where('is_demo', false)->count();

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
                // ilan var" ve "kaç şehirde ilan var" söylüyor. Demo ilanlar
                // SAYILMAZ — "gerçek hareket" iddiası örnek veriyle şişirilemez
                // (Kâhya teşhisiyle aynı ilke).
                // Şehir sayımı LOWER(TRIM(city)) üzerinden: listings.city
                // serbest metin, ham distinct 'Berlin'/'berlin'/'Berlin ' → 3 sayardı.
                'activeListings' => $aktifIlan,
                'activeCities' => Listing::query()->active()
                    ->where('is_demo', false)
                    ->whereNotNull('city')
                    ->where('city', '!=', '')
                    ->distinct()
                    ->count(DB::raw('LOWER(TRIM(city))')),

                'serit' => $this->heroSerit($aktifIlan, $rehber),
            ],
            'latestListings' => $latestListings,
            // "Canlı akış" satırlarında ÖRNEK işareti görünmez (ad + kategori
            // + zaman) — demo burada rozetsiz sahte hareket gibi okunurdu, bu
            // yüzden akışa HİÇ girmez (vitrin kuralının aksine boş-durum
            // istisnası da yok: akış bölümü boşken kendini zaten gizliyor).
            'activityFeed' => Listing::query()->active()
                ->where('is_demo', false)
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
                // Demo sayılmaz: çip "bu kategoride gerçek ilan var" vaadidir.
                ->withCount(['listings' => fn ($q) => $q->active()->where('is_demo', false)])
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
            'rehber' => $rehber,
        ]);
    }

    /**
     * "Ülke rehberi" bölümünün verisi (F2) — iki temanın ORTAK sözleşmesi.
     *
     * Modül kapalıyken ya da hiçbir ülkenin yayında içeriği yokken null döner
     * ve bölüm hiç basılmaz: boş bir rehber vaadi, ana sayfada yer kaplayan
     * bir özürden ibaret olurdu. Ülke önceliği K1 (üye ikameti > GeoIP);
     * çözülen ülke hazır değilse bölüm hazır ülkeleri gösterir, dayatmaz.
     *
     * @return array{ulkeler: Collection<int, Country>, secili: ?Country, cozulenKod: ?string, ozet: ?array{temsilcilikSayisi: int, islemTurleri: Collection<int, IslemTuru>}}|null
     */
    /**
     * Hero'nun kanıt şeridi hangi hâlde basılacak?
     *
     * Şerit bir kez "97 kategori · 44 şehir"den GERÇEK harekete çevrilmişti
     * (katalog büyüklüğü ziyaretçiye bir şey söylemiyordu). O düzeltme doğruydu
     * ama yeni bir sorun doğurdu: gerçek hareket azken şerit siteyi SAVUNMUYOR,
     * ALEYHİNE tanıklık ediyor. Mobilde ilk ekranda görünen somut sayı
     * "3 aktif ilan" olunca ziyaretçi "burada kimse yok" diye okuyor.
     *
     * Çözüm sayıyı şişirmek DEĞİL (demo saymak, kategori saymak — ikisi de
     * daha önce bilerek elendi), o ziyaretçi için gerçekten dolu olan şeyi
     * göstermek:
     *
     *   sayilar → hareket kendi başına duruyorsa
     *   rehber  → durmuyor ama ziyaretçinin ülkesinde YAYINDA rehber varsa
     *   cagri   → ikisi de yoksa: sayı yerine arz çağrısı
     *
     * Eşik uydurma değil: ilan listesi sayfa başına 12 kayıt gösteriyor
     * (BrowseController). Bir sayfayı bile doldurmayan envanter, "gelin bakın"
     * demeye yetmez; o eşiğin altında dürüst olan şey davet etmektir.
     *
     * @param  array{ulkeler: mixed, secili: mixed, cozulenKod: ?string, ozet: ?array}|null  $rehber
     * @return array{tip: string, ulke?: mixed, temsilcilik?: int, islem?: int}
     */
    private function heroSerit(int $aktifIlan, ?array $rehber): array
    {
        if ($aktifIlan >= self::SERIT_ILAN_ESIGI) {
            return ['tip' => 'sayilar'];
        }

        $secili = $rehber['secili'] ?? null;
        $ozet = $rehber['ozet'] ?? null;

        if ($secili !== null && $ozet !== null && $ozet['temsilcilikSayisi'] > 0) {
            return [
                'tip' => 'rehber',
                'ulke' => $secili,
                'temsilcilik' => $ozet['temsilcilikSayisi'],
                'islem' => $ozet['islemTurleri']->count(),
            ];
        }

        return ['tip' => 'cagri'];
    }

    private function rehberVerisi(Request $request, RehberYuzeyi $yuzey): ?array
    {
        if (! Modules::enabled('rehber')) {
            return null;
        }

        $ulkeler = $yuzey->hazirUlkeler();

        if ($ulkeler->isEmpty()) {
            return null;
        }

        $cozulenKod = $yuzey->cozulenUlkeKodu($request->user(), $request);
        $secili = $cozulenKod !== null ? $ulkeler->firstWhere('code', $cozulenKod) : null;

        return [
            'ulkeler' => $ulkeler,
            'secili' => $secili,
            'cozulenKod' => $cozulenKod,
            'ozet' => $secili !== null ? $yuzey->ulkeOzeti($secili) : null,
        ];
    }
}
