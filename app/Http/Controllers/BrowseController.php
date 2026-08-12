<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Services\DogalDilArama;
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
        /*
         * DOĞAL DİLLE ARAMA — süzgeçler okunmadan ÖNCE.
         *
         * "Berlin'de ucuz ev temizliği" bugün olduğu gibi aranınca, bu cümlenin
         * tamamı başlık/açıklamada LIKE ile aranıyor ve hiçbir şey bulunmuyor.
         * Cümle burada `q` + `sehir` + `tip` + `max` alanlarına ayrılıyor;
         * aşağıdaki sorgu hiç değişmeden çalışıyor.
         *
         * ÜÇ KAPI:
         *   - Kullanıcı süzgeci kendisi seçtiyse yorumlama ÇALIŞMAZ (elle
         *     seçilen bir süzgeci AI'ın değiştirmesi en sinir bozucu hatadır).
         *   - `?ham=1` ile kullanıcı yorumu kapatabilir (aşağıdaki şeritteki
         *     bağlantı buraya gider).
         *   - AI kapalı/kırıksa hiçbir şey olmaz, arama bugünkü gibi çalışır.
         */
        $aramaCumlesi = trim((string) $request->input('q'));
        $yorum = null;

        $baskaSuzgecVar = $category !== null || $request->filled([
            'kategori', 'ulke', 'sehir', 'min', 'max', 'tip',
        ]);

        if (! $request->boolean('ham')) {
            $cevirmen = app(DogalDilArama::class);

            if ($cevirmen->yorumlanmaliMi($aramaCumlesi, $baskaSuzgecVar)) {
                $yorum = $cevirmen->yorumla($aramaCumlesi);

                if ($yorum !== null) {
                    // Yalnız DOLU alanlar isteğe yazılır; null olanlar
                    // kullanıcının hiçbir şeyini değiştirmesin.
                    $request->merge(array_filter($yorum, fn ($v) => $v !== null && $v !== ''));
                }
            }
        }

        // Demo süzgeci burada DEĞİL, süzgeçler kurulduktan sonra uygulanır
        // (aşağıda `$query->gercek()`) — örnek rafı aynı süzgeçleri paylaşsın diye.
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

        /*
         * SÜZGEÇLER BİTTİ — burada iki dala ayrılıyor.
         *
         * gercek(): ÖRNEK ilanlar listeye ve "N ilan bulundu" sayacına
         * KARIŞMAZ. Kartın üstündeki rozet, sayacın söylediği yalanı
         * düzeltmiyordu: /ilanlar?ulke=DE "12 ilan bulundu" derken 12'si de
         * örnekti (ölçüm 2026-08-08). Ana sayfa bu kararı 2026-08-01'de
         * zaten vermişti (HomeController); liste sayfasında verilmemişti.
         *
         * Demo tamamen kaybolmuyor: gerçek sonuç boş kalırsa aşağıda ayrı,
         * ETİKETLİ bir rafta gösteriliyor. Kopya tam BURADAN alınır —
         * sıralamadan önce, süzgeçlerden sonra — ki aynı süzgeç zinciri
         * ikinci kez elle yazılmasın.
         */
        $ornekSorgusu = (clone $query)->where('is_demo', true);
        $query->gercek();

        // Öne çıkanlar (süresi geçmemiş) her zaman üstte, sonra seçilen sıralama
        $query->orderByFeatured();

        match ($request->string('sirala')->toString()) {
            'fiyat_artan' => $query->orderBy('price'),
            'fiyat_azalan' => $query->orderByDesc('price'),
            'populer' => $query->orderByDesc('views_count'),
            default => $query->latest(),
        };

        $listings = $query->paginate(12)->withQueryString();

        /*
         * ÖRNEK RAFI — yalnız gerçek sonuç BOŞKEN.
         *
         * Ana sayfadaki kuralın aynısı (HomeController): demo, gerçek arzın
         * yerine geçmez; gerçek arz yokken tamamen boş bir sayfadan iyidir.
         * Sayaç bu rafı saymaz, raf kendi başlığıyla örnek olduğunu söyler.
         *
         * Sorgu boş-durum dışında HİÇ çalışmaz — dolu sayfanın sorgu bütçesi
         * (PerformanceBenchmarkTest) değişmez.
         */
        $ornekIlanlar = $listings->total() === 0
            ? $ornekSorgusu->latest()->take(12)->get()
            : collect();

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
            // gercek(): filtre kolonundaki "Nakliyat (12)" rozeti de bir
            // SAYIDIR; örnek ilanla şişerse kullanıcı boş bir kategoriye
            // tıklar. Histogram için de aynısı — örnek fiyatlar gerçek fiyat
            // dağılımı gibi okunmamalı.
            $temel = fn () => tap(Listing::query()->active()->gercek(), function ($q) use ($request, $type) {
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
            'ornekIlanlar' => $ornekIlanlar,
            'kategoriSayaclari' => $kategoriSayaclari,
            'fiyatDagilimi' => $fiyatDagilimi,
            // BOŞ SONUÇ SAYFASI İNDEKSLENMEZ.
            //
            // Sitede 97 kategori sayfasının 93'ünde sıfır ilan vardı ve hepsi
            // indekslenebilir durumdaydı. İçeriği olmayan yüzlerce benzer sayfa
            // "thin content" desenidir ve değerlendirme site geneline yansır.
            // Sitemap'ten çıkarmak tek başına yetmez — arama motoru bu sayfalara
            // iç bağlantılardan da ulaşır; asıl kapı sayfanın kendi robots
            // etiketidir.
            //
            // Yalnız KATEGORİ sayfaları için: filtreli aramaların (?q=, ?sehir=)
            // boş dönmesi normaldir ve zaten indekslenmeleri istenmez; burada
            // ölçüt "kalıcı bir kategori sayfası bugün boş mu".
            'noindex' => $activeCategory !== null && $listings->total() === 0,
            // children eager load: Vitrin filtre kolonu kök kategori sayacına
            // alt kategorileri de topluyor — lazy kalırsa kategori başına bir
            // sorgu (N+1) doğar.
            'categories' => Category::query()->whereNull('parent_id')->where('is_active', true)
                ->with('children:id,parent_id')->orderBy('sort_order')->get(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'activeCategory' => $activeCategory,
            /*
             * ŞERİT VERİSİ: yorum yapıldıysa kullanıcıya NE ANLAŞILDIĞI
             * yazılır. Arama kutusuna yazdığından başka bir sonuç kümesi
             * görüp sebebini bilmemek, aramaya olan güveni bitirir.
             */
            'aramaYorumu' => $yorum,
            'aramaCumlesi' => $aramaCumlesi,
            'filters' => [
                // Kutuda kullanıcının KENDİ cümlesi kalır — yorumun daralttığı
                // anahtar kelime değil. Aksi hâlde kullanıcı yazdığını
                // düzeltemez, her aramada cümlesi elinden alınırdı.
                'q' => $aramaCumlesi !== '' ? $aramaCumlesi : $request->input('q', ''),
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
