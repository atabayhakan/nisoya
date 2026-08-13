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
use App\Models\ListingImage;
use App\Services\GeocodingService;
use App\Services\IlanCevirmeni;
use App\Services\ImageService;
use App\Services\TemsiliGorselUretici;
use App\Support\Modules;
use App\Support\Tema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
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

    /**
     * Emlak/vasıta dikey modülü kapalıysa o türde ilan oluşturmayı 404 yapar
     * (public rota gate'iyle tutarlı — bkz. App\Support\Modules · G4).
     */
    private function assertTypeEnabled(string $type): void
    {
        if (in_array($type, Modules::KEYS, true)) {
            abort_unless(Modules::enabled($type), 404);
        }
    }

    public function create(Request $request): View
    {
        $tip = $request->query('tip');
        $type = in_array($tip, ['urun', 'emlak', 'vasita'], true) ? $tip : 'hizmet';
        $this->assertTypeEnabled($type);

        return view('panel.listings.create', $this->formData($type));
    }

    public function store(ListingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = $data['type'] ?? 'hizmet';
        $this->assertTypeEnabled($type);
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
            'width_cm' => $type === 'urun' ? ($data['width_cm'] ?? null) : null,
            'height_cm' => $type === 'urun' ? ($data['height_cm'] ?? null) : null,
            // Taslak mı yayın mı: formdaki hangi düğmeye basıldığı belirler.
            // Beklenmedik bir değer gelirse YAYIN DEĞİL taslak seçilmez —
            // varsayılan davranış değişmemeli (geriye dönük uyum).
            'status' => $request->input('eylem') === 'taslak'
                ? ListingStatus::Taslak->value
                : ListingStatus::Aktif->value,
        ]);

        $this->syncPropertyDetail($listing, $request);
        $this->syncVehicleDetail($listing, $request);
        $this->storeImages($listing, $request);

        if ($request->input('eylem') === 'taslak') {
            return redirect()->route('panel.listings.index')
                ->with('status', 'Taslak kaydedildi. Hazır olunca "Yayınla" ile herkese açabilirsin.');
        }

        /*
         * YAYIN SONRASI PAYLAŞIM ANI (2026-08-06).
         *
         * Paylaşım kartı (WhatsApp durumu için 1080x1920) ve paylaş düğmeleri
         * ilan DETAY sayfasında zaten vardı — ama kullanıcı yayınladıktan sonra
         * İlanlarım'a düşüyor ve orada paylaşımdan söz eden hiçbir şey yok.
         * Yani en istekli an (az önce yayınladı) boşa gidiyordu; paylaşmak için
         * ilanı bulup detayına gitmesi gerekiyordu.
         *
         * Pazaryerinin darboğazı arz ve dağıtım. Yeni ilan veren kişi, o ilanın
         * en doğal dağıtım kanalı — kendi çevresi. İstemek için doğru an bu.
         *
         * Yönlendirme hedefi DEĞİŞTİRİLMEDİ (mevcut testler İlanlarım'a
         * yönlendirmeyi mühürlüyor ve bu davranış doğru): yalnız ilanın kimliği
         * flash'a ekleniyor, İlanlarım o ilan için paylaşım bloğu basıyor.
         */
        return redirect()->route('panel.listings.index')
            ->with('yayinlanan', $listing->id)
            ->with('status', match ($type) {
                'urun' => 'Ürün ilanın yayınlandı! 🎉',
                'emlak' => 'Emlak ilanın yayınlandı! 🎉',
                'vasita' => 'Vasıta ilanın yayınlandı! 🎉',
                default => 'İlanın yayınlandı! 🎉',
            });
    }

    public function edit(Listing $listing): View
    {
        Gate::authorize('update', $listing);

        $listing->load('images', 'translations');

        return view('panel.listings.edit', array_merge(
            [
                'listing' => $listing,
                // Kapıyı controller'da soruyoruz ki görünüm karar vermesin:
                // aynı kontrol POST tarafında da çalışıyor ve ikisinin ayrı
                // yerlerde yeniden yazılması, birinin unutulması demek.
                'temsiliGorselOnerilebilir' => app(TemsiliGorselUretici::class)->uygunMu($listing),
                'cevirmen' => app(IlanCevirmeni::class),
            ],
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
            'width_cm' => $listing->type->value === 'urun' ? ($data['width_cm'] ?? null) : null,
            'height_cm' => $listing->type->value === 'urun' ? ($data['height_cm'] ?? null) : null,
        ]);

        $this->syncPropertyDetail($listing, $request);
        $this->syncVehicleDetail($listing, $request);

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

    /** Ürün görselinin kırpma odağını kaydet (parmak/fare ile sürükleyerek hizalama). */
    public function alignImage(Request $request, Listing $listing, ListingImage $image): JsonResponse
    {
        Gate::authorize('update', $listing);

        if ($image->listing_id !== $listing->id) {
            abort(404);
        }

        $data = $request->validate([
            'focal_x' => ['required', 'integer', 'min:0', 'max:100'],
            'focal_y' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $image->update([
            'focal_x' => $data['focal_x'],
            'focal_y' => $data['focal_y'],
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Taslağı yayına alır.
     *
     * Taslak kaydetme aynı turda eklendi; bu metot onun ÇIKIŞ YOLU. Olmasaydı
     * kullanıcı taslağı kaydedip bir daha yayınlayamazdı — düzenleme
     * sayfasında durum alanı yok. Yarım özellik, hiç özellik olmamasından
     * kötüdür.
     *
     * Yetki `update` yeteneğiyle: kendi ilanını düzenleyebilen yayınlayabilir.
     * Yalnız TASLAK yayına alınır — reddedilmiş ilanı buradan diriltmek
     * moderasyon kararını atlatmak olurdu. Üyenin kendi kaldırdığı PASİF
     * ilanın ayrı bir kapısı var (`geriYayinla`); oraya da yönetimin
     * kaldırdığı ilan giremez.
     */
    public function yayinla(Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        if ($listing->status !== ListingStatus::Taslak) {
            return redirect()->route('panel.listings.index')
                ->with('status', 'Bu ilan zaten taslak değil.');
        }

        return $this->yayinaAl($listing, 'İlanın yayınlandı! 🎉');
    }

    /**
     * Üye kendi ilanını yayından kaldırır (Aktif → Pasif).
     *
     * NEDEN VAR: "Pasif" durumu sistemde vardı ama üyenin ona ULAŞMASININ
     * hiçbir yolu yoktu — tek üreteni `UserObserver::suspendActiveListings()`
     * idi (hesap askıya alınınca). Yani "sattım/şimdilik kapatayım" diyen üye
     * ilanı SİLMEK zorunda kalıyordu: görüntülenme, değerlendirme bağlamı ve
     * paylaşılmış bağlantı hep birlikte gidiyordu. Silmek geri alınamaz, bu
     * geri alınabilir.
     *
     * `unpublished_at` DOLDURULUR — geri açma hakkının tek kanıtı o (bkz.
     * Listing::isOwnerUnpublished).
     */
    public function yayindanKaldir(Listing $listing): RedirectResponse
    {
        Gate::authorize('manageVisibility', $listing);

        // Yalnız AKTİF ilan kaldırılır. Taslak zaten yayında değil; beklemede
        // olanı kaldırmak moderasyon kuyruğundan kaçmak, reddedilmişi
        // "kaldırmak" ise reddi üyenin kendi kararıymış gibi göstermek olurdu.
        if ($listing->status !== ListingStatus::Aktif) {
            return redirect()->route('panel.listings.index')
                ->with('status', 'Yalnızca yayındaki ilanlar yayından kaldırılabilir.');
        }

        $listing->update([
            'status' => ListingStatus::Pasif,
            'unpublished_at' => now(),
        ]);

        return redirect()->route('panel.listings.index')
            ->with('status', 'İlanın yayından kaldırıldı. İstediğin zaman geri açabilirsin.');
    }

    /**
     * Üye kendi kaldırdığı ilanı geri yayınlar (Pasif → Aktif).
     *
     * TEKRAR MODERASYONA GİRMEZ — bilinçli. `yayinla` (Taslak → Aktif) da
     * doğrudan geçiyor; metin moderasyonu bu projede önden değil, şikâyet ve
     * AI görsel elemesiyle işliyor. Kendi yayınladığı, zaten bir kez yayında
     * kalmış ilanı geri açmayı kuyruğa sokmak, üyeye "kendi ilanın sana ait
     * değil" demek ve sahibe gereksiz bir kuyruk yüklemek olurdu.
     *
     * TEK İSTİSNA `yayinaAl()` içinde: işaretli görsel varsa Aktif yerine
     * "Onay bekliyor". Yoksa yayından kaldır → işaretli görsel yükle → geri
     * yayınla dizisi AI elemesini tamamen atlatırdı.
     */
    public function geriYayinla(Listing $listing): RedirectResponse
    {
        Gate::authorize('manageVisibility', $listing);

        if (! $listing->isOwnerUnpublished()) {
            return redirect()->route('panel.listings.index')
                ->with('status', $listing->status === ListingStatus::Pasif
                    ? 'Bu ilan yönetim tarafından yayından kaldırıldı; geri açmak için bizimle iletişime geç.'
                    : 'Bu ilan yayından kaldırılmış değil.');
        }

        return $this->yayinaAl($listing, 'İlanın tekrar yayında! 🎉');
    }

    /**
     * Yayına alma tek kapı: durum + `unpublished_at` temizliği + işaretli
     * görsel kontrolü hep birlikte.
     *
     * İki çağrısı (`yayinla`, `geriYayinla`) aynı kuralı paylaşmak zorunda:
     * biri işaretli görsel kontrolünü atlarsa boşluk oradan geri açılır.
     */
    private function yayinaAl(Listing $listing, string $mesaj): RedirectResponse
    {
        $isaretli = $listing->hasFlaggedImage();

        $listing->update([
            'status' => $isaretli ? ListingStatus::Beklemede : ListingStatus::Aktif,
            'unpublished_at' => null,
        ]);

        if ($isaretli) {
            return redirect()->route('panel.listings.index')
                ->with('status', 'İlanın incelemeye alındı: görsellerinden biri otomatik kontrolde işaretlendi. Onaylanınca yayına çıkacak.');
        }

        return redirect()->route('panel.listings.index')
            ->with('yayinlanan', $listing->id)
            ->with('status', $mesaj);
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
    public function show(Request $request, Listing $listing, ?string $slug = null): View|RedirectResponse
    {
        $isOwner = $request->user()?->id === $listing->user_id;

        /*
         * ARŞİV KİPİ (2026-08-06) — yayından kalkmış ilan artık 404 değil.
         *
         * Sahip "satıcının geçmiş ilanlarını görebileyim" dedi. Bir listenin
         * kartları tıklanabiliyorsa hedefleri de açılabilmeli; aksi hâlde
         * özellik "her kart 404'e gider" demek olurdu.
         *
         * Açılan YALNIZ `Pasif`tir. Taslak, onay bekleyen ve reddedilen
         * ilanlar sahibi dışında kimseye görünmez — biri henüz yayınlanmadı,
         * diğeri moderasyondan geçmedi, üçüncüsü kasten reddedildi.
         */
        $isArchived = $listing->arsivdeMi();

        if (! $isOwner && $listing->status !== ListingStatus::Aktif && ! $isArchived) {
            abort(404);
        }

        // Yanlış/eksik slug'lı istekleri kanonik URL'e yönlendir (duplicate content önleme).
        if ($slug !== $listing->slug) {
            return redirect()->route('listings.show', array_merge(
                ['listing' => $listing, 'slug' => $listing->slug],
                $request->query()
            ), 301);
        }

        // Görüntülenme: aynı ziyaretçi (giriş yapmışsa user, değilse oturum)
        // kısa pencerede tekrar açarsa sayacı şişirmesin — hem daha doğru metrik
        // hem her istekte bir UPDATE yerine dedup ile daha az yazma.
        // Arşiv görüntülemesi sayılmaz: ölü bir ilanın görüntülenme sayacını
        // şişirmek, o sayıyı bir talep göstergesi olarak okunamaz hâle getirir.
        if (! $isOwner && ! $isArchived) {
            $viewerKey = 'viewed:listing:'.$listing->id.':'.(auth()->id() ?? $request->session()->getId());
            if (Cache::add($viewerKey, true, now()->addHours(6))) {
                /*
                 * `updated_at`'E DOKUNMADAN artır.
                 *
                 * Düz `increment()` zaman damgasını da günceller ve bu İKİ
                 * yerde birden yalan söyler:
                 *   1. İlan detayında "X önce güncellendi" yazıyor — oysa
                 *      satıcı bir şey değiştirmedi, sadece BİRİ BAKTI.
                 *   2. Site haritasındaki `lastmod` bu alandan geliyor; her
                 *      ziyaret arama motoruna "içerik değişti" diye haber
                 *      veriyor. İçerik değişmediği hâlde sürekli tazelenen
                 *      bir lastmod, tarayıcı güvenini aşındırır.
                 *
                 * Canlıda ölçüldü: üç ilanın üçü de "3 dakika önce
                 * güncellendi" görünüyordu — tek sebep sayfaların açılmasıydı.
                 */
                $listing->timestamps = false;
                $listing->increment('views_count');
                $listing->timestamps = true;
            }
        }

        $listing->load(['images', 'user.paymentLinks', 'category', 'country']);

        /*
         * Çeviriler KOŞULLU yükleniyor.
         *
         * Yerel dil bloğu her ilan detayında çalışıyor; koşulsuz yükleseydik
         * sayfa başına +1 sorgu olurdu — ülkesi dil haritasında OLMAYAN
         * ilanlarda ise o sorgunun karşılığı hiç yok, çünkü blok zaten
         * basılmıyor. Haritada dil yoksa hem yükleme hem bileşen erken
         * çıkıyor (IlanCevirmeni::guncelCeviri de aynı kapıdan geçer), yani
         * o sayfalarda maliyet SIFIR.
         */
        if (app(IlanCevirmeni::class)->hedefDil($listing) !== null) {
            $listing->load('translations');
        }

        if (in_array($listing->type, [ListingType::Emlak, ListingType::Vasita], true)) {
            $listing->load([
                $listing->type === ListingType::Emlak ? 'propertyDetail' : 'vehicleDetail',
                'unavailableRanges' => fn ($q) => $q->where('ends_on', '>=', now()->toDateString()),
            ]);
        }

        $isFavorited = $request->user()
            ? $request->user()->favorites()->where('listing_id', $listing->id)->exists()
            : false;

        $sellerReviews = $listing->user->reviewsReceived()->where('status', 'yayinda');
        $sellerRating = [
            'avg' => round((float) $sellerReviews->avg('rating'), 1),
            'count' => $sellerReviews->count(),
        ];

        // Vitrin (Faz P4) — yalnız Vitrin teması aktifken yüklenir:
        // klasik tema bu blokları göstermediği için orada sorgu maliyeti
        // doğurmaz (ilan detayı <25 sorgu bütçesi, PerformanceBenchmarkTest).
        $similarListings = collect();
        $recentReviews = collect();

        if (Tema::vitrinMi()) {
            // Benzer ilanlar: aynı kategori, kendisi hariç, aktif. Aynı
            // şehirdekiler önce gelsin (yakınlık daha alakalı) — tek sorgu.
            $similarListings = $listing->category_id
                // Benzer ilan, bakılan ilanla AYNI gerçeklikten seçilir:
                // gerçek ilanın altında örnek ilan önerilmez, örnek ilanın
                // altında da gerçek satıcı reklamı yapılmaz.
                ? Listing::query()->active()
                    ->where('is_demo', $listing->is_demo)
                    ->where('category_id', $listing->category_id)
                    ->whereKeyNot($listing->id)
                    ->with(['coverImage', 'country'])
                    ->when($listing->city, fn ($q) => $q->orderByRaw('(city = ?) desc', [$listing->city]))
                    ->latest()
                    ->take(4)
                    ->get()
                : collect();

            // Satıcının son değerlendirmeleri (profiles.show ile aynı sözleşme).
            $recentReviews = $listing->user->reviewsReceived()
                ->where('status', 'yayinda')
                ->with('reviewer')
                ->latest()
                ->take(3)
                ->get();
        }

        /*
         * Satıcının ilan sayıları — "Güncel ilanları (12)" düğmesindeki sayı
         * gerçek olmalı. Sıfır olan düğme HİÇ basılmaz: tıklandığında boş
         * sayfa açan bir düğme, olmayan bir şeyin sözünü verir.
         *
         * TEK SORGU, iki değil. İlk yazışta iki ayrı `count()` vardı ve İKİ
         * AYRI sorgu bütçesini birden aştı (PerformanceBenchmarkTest 25/25,
         * VitrinVeriBloklariTest 32/32). Koşullu toplama ile bir sorguya
         * indi — aynı desen {@see User::trustProfile()} içinde de kullanılıyor.
         */
        /*
         * "Geçmiş" sayısı YALNIZ üyenin kendi kaldırdıklarını sayar
         * (`unpublished_at IS NOT NULL`). Yalnız duruma bakmak, askıya alınmış
         * bir hesabın ilanlarını herkese açık profilde "Geçmiş" diye saymak
         * olurdu — bkz. Listing::arsivdeMi() açıklaması.
         */
        $sayilar = $listing->user->listings()
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as guncel, '
                .'SUM(CASE WHEN status = ? AND unpublished_at IS NOT NULL THEN 1 ELSE 0 END) as gecmis',
                [ListingStatus::Aktif->value, ListingStatus::Pasif->value],
            )
            ->toBase()
            ->first();

        $sellerListingCounts = [
            'guncel' => (int) ($sayilar->guncel ?? 0),
            'gecmis' => (int) ($sayilar->gecmis ?? 0),
        ];

        /*
         * ARAMA MOTORU GÖRÜNÜRLÜĞÜ — iki ayrı sebep, tek karar.
         *
         * Arşiv (yayından kalkmış) sayfası zaten noindex'ti. ÖRNEK ilanlar
         * değildi: sitemap'e giren 29 ilanın 28'i örnekti ve hiçbirinde
         * robots etiketi yoktu (ölçüm 2026-08-08). Sitemap'ten çıkarmak tek
         * başına yetmez — arama motoru bu sayfalara iç bağlantılardan da
         * ulaşır, asıl kapı sayfanın kendi etiketidir (aynı gerekçe: boş
         * kategori sayfaları, BrowseController).
         *
         * Sayfa erişilebilir KALIR: rozetli örnek ilan, sahibin ve
         * ziyaretçinin gezinebildiği bir demo olarak duruyor; yalnız arama
         * sonucunda gerçek arz gibi görünmüyor.
         *
         * Karar burada veriliyor ki klasik ve Vitrin show görünümleri aynı
         * değişkeni okusun — ikisine ayrı ayrı yazılsaydı biri unutulurdu.
         */
        $noindex = $isArchived || (bool) $listing->is_demo;

        return view('listings.show', compact(
            'listing', 'isOwner', 'isArchived', 'noindex', 'isFavorited', 'sellerRating',
            'similarListings', 'recentReviews', 'sellerListingCounts'
        ));
    }

    /** Form için ortak veri (tipe göre filtreli kategoriler, para birimleri, ülkeler). */
    protected function formData(string $type = 'hizmet'): array
    {
        // 'ikisi' yalnızca hizmet+ürün ortak kategorileri için — emlak ve
        // vasıta kendi kategori ağaçlarını kullanır, 'ikisi' oraya sızmamalı.
        $categoryTypes = in_array($type, ['emlak', 'vasita'], true) ? [$type] : [$type, 'ikisi'];

        return [
            'type' => $type,
            'categories' => Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->whereIn('type', $categoryTypes)
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

    /** Emlak ilanının 1:1 detay kaydını oluştur/güncelle (diğer tiplerde no-op). */
    protected function syncPropertyDetail(Listing $listing, ListingRequest $request): void
    {
        if ($listing->type !== ListingType::Emlak) {
            return;
        }

        $data = $request->validated();

        $listing->propertyDetail()->updateOrCreate([], [
            'rooms' => $data['rooms'] ?? null,
            'area_m2' => $data['area_m2'] ?? null,
            'floor' => $data['floor'] ?? null,
            'furnished' => $request->boolean('furnished'),
            'deposit' => $data['deposit'] ?? null,
            'available_from' => $data['available_from'] ?? null,
            'max_guests' => $data['max_guests'] ?? null,
            'min_stay_nights' => $data['min_stay_nights'] ?? null,
            'badges' => array_values($data['badges'] ?? []),
        ]);
    }

    /** Vasıta ilanının 1:1 detay kaydını oluştur/güncelle (diğer tiplerde no-op). */
    protected function syncVehicleDetail(Listing $listing, ListingRequest $request): void
    {
        if ($listing->type !== ListingType::Vasita) {
            return;
        }

        $data = $request->validated();

        $listing->vehicleDetail()->updateOrCreate([], [
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'year' => $data['year'] ?? null,
            'mileage_km' => $data['mileage_km'] ?? null,
            'fuel' => $data['fuel'] ?? null,
            'transmission' => $data['transmission'] ?? null,
            'body_type' => $data['body_type'] ?? null,
            'color' => $data['color'] ?? null,
            'min_rental_days' => $data['min_rental_days'] ?? null,
            'deposit' => $data['deposit'] ?? null,
            'km_limit_per_day' => $data['km_limit_per_day'] ?? null,
            'badges' => array_values($data['badges'] ?? []),
        ]);
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
        $kuyruga = 0;

        foreach ($request->file('images') as $file) {
            $rawPath = $file->store('pending-listing-images', 'local');
            $order++;
            $kuyruga++;

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

        if ($kuyruga === 0) {
            return;
        }

        /*
         * İŞLENME DURUMUNU İŞARETLE — kullanıcı bekleyeceğini bilsin.
         *
         * Kayıt yalnızca kuyruk işinde doğduğu için, buradan çıkıldığında ilan
         * hâlâ görselsiz. O boşlukta BOŞ KUTU ile ARIZA aynı görünüyordu ve
         * sahip 2026-08-12'de tam bunu yaşadı. Sayaç işi bitiren job'da düşer
         * (bkz. ProcessListingImage).
         *
         * `images_failed` sıfırlanıyor: yeni bir deneme, eski hatanın uyarısını
         * ekranda bırakmamalı.
         *
         * `fillable`da DEĞİLLER ve olmamalılar — bunlar kullanıcı girdisi değil,
         * sistemin kendi durumu. Doğrudan atama kütlesel atama korumasını
         * zaten atlar.
         */
        $listing->pending_images = ($listing->pending_images ?? 0) + $kuyruga;
        $listing->images_queued_at = now();
        $listing->images_failed = false;
        $listing->save();
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
