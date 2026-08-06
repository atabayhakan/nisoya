{{-- ARŞİV SAYFASI ARAMA MOTORUNA KAPALI: yayından kalkmış bir ilanın arama
     sonucunda çıkması, tıklayan herkese ölü bir sayfa göstermek demektir.
     Sayfa insanlar için açık, dizinler için kapalı. --}}
<x-layouts.app :title="$listing->title.' — Nisoya'" :description="\Illuminate\Support\Str::limit(strip_tags($listing->description), 150)" :ogImage="$listing->coverImage?->enIyiUrl('large')" :noindex="$isArchived">
    {{-- VİTRİN İLAN DETAYI (P2) — klasik listings/show'un aynı-ad override'ı.
         Korunan sözleşmeler: TÜM JSON-LD blokları birebir (BreadcrumbList +
         tipe göre RealEstateListing/Product/Service), hero görselinde
         srcset/sizes/fetchpriority + TEK morph adı, emlak/vasıta özellik
         metinleri, güvenlik uyarıları, "Stokta N adet", boyut karşılaştırma,
         müsaitlik takvimi, mesaj/favori/şikayet formları (route + alan adları),
         paylaş partial'ı, ödeme güvenliği kartı, zone 'ilan_detay_yan'.
         Controller'a dokunulmadığı için veri karşılığı olmayan prototip
         blokları (benzer ilanlar, yorum listesi, yanıt süresi) BASILMAZ. --}}
    {{-- JSON-LD: BreadcrumbList --}}
    <x-json-ld type="BreadcrumbList" :data="[
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'İlanlar', 'item' => url('/ilanlar')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $listing->category?->name ?? 'Genel', 'item' => $listing->category ? url('/ilanlar/kategori/'.$listing->category->slug) : url('/ilanlar')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $listing->title, 'item' => url()->current()],
        ],
    ]" />

    {{-- JSON-LD: Service, Product veya RealEstateListing --}}
    @if ($listing->type->value === 'emlak')
        <x-json-ld type="RealEstateListing" :data="[
            'name' => $listing->title,
            'description' => \Illuminate\Support\Str::limit(strip_tags($listing->description), 300),
            'image' => $listing->coverImage?->enIyiUrl('large'),
            'url' => url()->current(),
            'datePosted' => $listing->created_at->toDateString(),
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'addressLocality' => $listing->city,
                'addressCountry' => $listing->country_code,
            ]),
            'offers' => $listing->price ? [
                '@type' => 'Offer',
                'price' => $listing->price,
                'priceCurrency' => $listing->currency,
            ] : null,
        ]" />
    @elseif ($listing->type->value === 'vasita')
        <x-json-ld type="Product" :data="[
            'name' => $listing->title,
            'description' => \Illuminate\Support\Str::limit(strip_tags($listing->description), 300),
            'image' => $listing->coverImage?->enIyiUrl('large'),
            'brand' => $listing->vehicleDetail?->brand ? ['@type' => 'Brand', 'name' => $listing->vehicleDetail->brand] : null,
            'offers' => $listing->price ? [
                '@type' => 'Offer',
                'price' => $listing->price,
                'priceCurrency' => $listing->currency,
            ] : null,
        ]" />
    @elseif ($listing->type->value === 'urun')
        <x-json-ld type="Product" :data="array_filter([
            'name' => $listing->title,
            'description' => \Illuminate\Support\Str::limit(strip_tags($listing->description), 300),
            'image' => $listing->coverImage?->enIyiUrl('large'),
            'itemCondition' => 'https://schema.org/UsedCondition',
            'width' => $listing->width_cm ? ['@type' => 'QuantitativeValue', 'value' => $listing->width_cm, 'unitCode' => 'CMT'] : null,
            'height' => $listing->height_cm ? ['@type' => 'QuantitativeValue', 'value' => $listing->height_cm, 'unitCode' => 'CMT'] : null,
            'offers' => [
                '@type' => 'Offer',
                'price' => $listing->price ?? 0,
                'priceCurrency' => $listing->currency,
                'availability' => ($listing->stock && $listing->stock > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ],
        ])" />
    @else
        <x-json-ld type="Service" :data="[
            'name' => $listing->title,
            'description' => \Illuminate\Support\Str::limit(strip_tags($listing->description), 300),
            'image' => $listing->coverImage?->enIyiUrl('large'),
            'provider' => [
                '@type' => 'Person',
                'name' => $listing->user->name,
            ],
            'areaServed' => $listing->country ? [
                '@type' => 'Country',
                'name' => $listing->country->name_tr,
            ] : null,
            'offers' => $listing->price ? [
                '@type' => 'Offer',
                'price' => $listing->price,
                'priceCurrency' => $listing->currency,
            ] : null,
        ]" />
    @endif

    <div class="mx-auto max-w-6xl px-4 pb-10 pt-5">
        @if (session('status'))
            <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs font-semibold text-stone-500 dark:text-stone-400">
            <a href="{{ url('/ilanlar') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İlanlar</a>
            @if ($listing->category)
                <span class="text-stone-300 dark:text-stone-400">/</span>
                <a href="{{ url('/ilanlar/kategori/'.$listing->category->slug) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">{{ $listing->category->name }}</a>
            @endif
        </nav>

        {{-- ARŞİV BANDI — ziyaretçiye durumu SAYFANIN BAŞINDA söyler.
             Yayından kalkmış bir ilanı normal ilan gibi göstermek, kişiyi
             cevap gelmeyecek bir mesaja sürükler; en pahalı hata bu olurdu. --}}
        @if ($isArchived && ! $isOwner)
            <div class="mt-4 flex items-start gap-3 rounded-2xl border border-stone-300 bg-stone-100 px-4 py-3.5 text-sm font-medium text-stone-700 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-300">
                <x-heroicon-o-archive-box class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <strong class="text-stone-800 dark:text-stone-100">Bu ilan artık yayında değil.</strong>
                    Geçmiş kaydı olarak görüntülüyorsun — mesaj gönderilemez.
                    <a href="{{ route('profiles.show', $listing->user->username) }}" class="font-bold text-emerald-700 hover:underline dark:text-emerald-400">{{ $listing->user->name }} kullanıcısının güncel ilanlarına bak →</a>
                </div>
            </div>
        @elseif ($isOwner && $listing->status->value !== 'aktif')
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                Bu ilan şu an <strong>{{ $listing->status->getLabel() }}</strong> durumunda — yalnızca sen görüyorsun.
            </div>
        @endif

        <div class="mt-4 grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_356px]">
            {{-- Sol kolon --}}
            <div class="grid gap-4">
                {{-- A. Galeri bentosu --}}
                @if ($listing->images->isNotEmpty())
                    @php
                        $hero = $listing->coverImage ?? $listing->images->first();
                        $heroSrcset = $hero->srcset();
                        $heroLarge = $hero->enIyiUrl('large');
                        $heroMedium = $hero->enIyiUrl('medium');
                        // Yan sütun: hero dışındaki ilk 3 görsel (kalanı "+N foto" örtüsünde sayılır)
                        $yanGorseller = $listing->images->reject(fn ($img) => $img->is($hero))->take(3);
                        $kalanFoto = $listing->images->count() - 1 - $yanGorseller->count();
                    @endphp
                    <div class="grid gap-3 {{ $yanGorseller->isNotEmpty() ? 'sm:grid-cols-[minmax(0,1fr)_176px]' : '' }}">
                        <div class="relative overflow-hidden rounded-2xl bg-stone-100 dark:bg-stone-800">
                            <img src="{{ $heroLarge }}"
                                 srcset="{{ $heroMedium }} 800w, {{ $heroLarge }} 1600w"
                                 sizes="(min-width: 1024px) 800px, 100vw"
                                 alt="{{ $listing->title }}"
                                 width="800"
                                 height="420"
                                 fetchpriority="high"
                                 style="--listing-transition-name: listing-image-{{ $listing->id }}; object-position: {{ $hero->objectPosition() }}"
                                 class="listing-cover-transition h-[260px] w-full object-cover sm:h-[352px]">
                            @if ($listing->images->count() > 1)
                                <span class="absolute bottom-3 left-3 rounded-full bg-white/95 px-[11px] py-[7px] text-2xs font-bold text-stone-800 dark:bg-stone-900/95 dark:text-stone-100">
                                    1 / {{ $listing->images->count() }}
                                </span>
                            @endif
                        </div>

                        @if ($yanGorseller->isNotEmpty())
                            <div class="grid grid-cols-3 gap-3 sm:grid-cols-1 sm:grid-rows-3">
                                @foreach ($yanGorseller as $image)
                                    @php $thumb = $image->enIyiUrl('thumb'); @endphp
                                    <div class="relative overflow-hidden rounded-[16px] bg-stone-100 dark:bg-stone-800">
                                        <img src="{{ $thumb }}"
                                             srcset="{{ $thumb }} 300w, {{ ($image->enIyiUrl('medium')) }} 800w"
                                             sizes="176px"
                                             alt=""
                                             width="176"
                                             height="109"
                                             loading="lazy"
                                             decoding="async"
                                             style="object-position: {{ $image->objectPosition() }}"
                                             class="h-full min-h-[76px] w-full object-cover sm:h-[109px]">
                                        @if ($loop->last && $kalanFoto > 0)
                                            <span class="absolute inset-0 grid place-items-center bg-stone-900/35">
                                                <span class="rounded-lg bg-white/90 px-2.5 py-1.5 text-xs font-bold text-stone-700">+{{ $kalanFoto }} foto</span>
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    @php $fallbackIcon = \App\Support\CategoryIcon::heroicon($listing->category?->parent?->icon ?? $listing->category?->icon); @endphp
                    <div style="--listing-transition-name: listing-image-{{ $listing->id }}" class="listing-cover-transition flex h-56 items-center justify-center rounded-2xl bg-stone-100 text-stone-300 dark:bg-stone-800 dark:text-stone-400">
                        <x-dynamic-component :component="'heroicon-o-'.$fallbackIcon" class="h-16 w-16" />
                    </div>
                @endif

                {{-- B. İçerik kartı --}}
                <div class="rounded-2xl border border-stone-200/60 bg-white px-5 py-5 shadow-brand sm:px-6 dark:border-stone-800 dark:bg-stone-900">
                    <div class="flex flex-wrap items-center gap-2 text-2xs font-bold">
                        @if ($listing->category)
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1.5 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">{{ $listing->category->name }}</span>
                        @endif
                        @if ($listing->isCurrentlyFeatured())
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1.5 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                <x-heroicon-s-star class="h-3 w-3" /> Öne çıkan
                            </span>
                        @endif
                        @if ($listing->user->is_verified)
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#e7f7f1] px-2.5 py-1.5 text-[#0f9d76] dark:bg-teal-950/60 dark:text-teal-300">
                                <x-heroicon-s-check class="h-3 w-3" /> Doğrulanmış satıcı
                            </span>
                        @endif
                        @if ($listing->country)
                            <span class="rounded-full bg-stone-100 px-2.5 py-1.5 text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                                {{ $listing->country->emoji }} @if ($listing->city){{ $listing->city }}, @endif{{ $listing->country->name_tr }}
                            </span>
                        @endif
                        @if ($listing->is_remote)
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#e7f7f1] px-2.5 py-1.5 text-[#0f9d76] dark:bg-teal-950/60 dark:text-teal-300">
                                <x-heroicon-o-globe-alt class="h-3 w-3" /> Uzaktan / Online
                            </span>
                        @endif
                        @if ($listing->type->value === 'urun' && $listing->stock !== null)
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#fff6e8] px-2.5 py-1.5 text-[#b9741a] dark:bg-amber-950/60 dark:text-amber-300">
                                <x-heroicon-o-archive-box class="h-3 w-3" /> Stokta {{ $listing->stock }} adet
                            </span>
                        @endif
                    </div>

                    <x-ornek-isareti :listing="$listing" bicim="bant" />
                    <h1 class="mt-3.5 text-2xl font-extrabold leading-[1.16] tracking-[-0.028em] text-stone-800 sm:text-3xl dark:text-stone-50" style="text-wrap: pretty">{{ $listing->title }}</h1>

                    <div class="mt-3.5 flex flex-wrap items-center gap-x-4 gap-y-2">
                        <span class="text-2xl font-extrabold text-emerald-700 dark:text-emerald-400">
                            @if ($listing->price !== null)
                                {{ $listing->bicimliFiyat() }} {{ $listing->currency }}
                            @else
                                Görüşülür
                            @endif
                        </span>
                        @if ($listing->price !== null && $listing->price_unit->suffix())
                            <span class="text-xs font-semibold text-stone-500 dark:text-stone-400">{{ $listing->price_unit->suffix() }}</span>
                        @endif
                        <span class="hidden h-[22px] w-px bg-stone-200 sm:block dark:bg-stone-700" aria-hidden="true"></span>
                        <span class="text-xs font-semibold text-stone-500 dark:text-stone-400">İlan no <b class="text-stone-800 dark:text-stone-200">NS-{{ $listing->id }}</b></span>
                        <span class="hidden h-[22px] w-px bg-stone-200 sm:block dark:bg-stone-700" aria-hidden="true"></span>
                        <span class="text-xs font-semibold text-stone-500 dark:text-stone-400">{{ $listing->updated_at->diffForHumans() }} güncellendi</span>
                    </div>

                    <div class="prose prose-stone mt-4 max-w-none text-sm font-medium leading-[1.7] text-stone-600 dark:prose-invert dark:text-stone-300">
                        {!! nl2br(e($listing->description)) !!}
                    </div>
                </div>

                {{-- MOBİLDE SATICI ŞERİDİ (lg:hidden) — 2026-08-06.
                     -----------------------------------------------------------
                     Sayfa masaüstünde iki kolon; mobilde sağ kolon en ALTA
                     düşüyor. Yani telefonda ilanı açan biri açıklamayı okuyup
                     bitirdiğinde SATICININ KİM OLDUĞUNU HÂLÂ BİLMİYOR — kimlik,
                     puan ve iletişim çok aşağıda kalıyordu. Bir pazaryerinde
                     "kimden alıyorum" sorusu üründen sonra gelen ikinci soru
                     değil, onunla aynı anda sorulan sorudur.

                     Bu şerit kenar çubuğundaki kartın KOPYASI DEĞİL: orada
                     olmayan bir işi yapıyor (mobilde erken kimlik + iletişime
                     kısayol). Ortak olan tek parça — ilan düğmeleri — iki yerde
                     de aynı partial'dan geliyor. --}}
                <div class="rounded-2xl border border-stone-200/60 bg-white px-5 py-4 shadow-brand lg:hidden dark:border-stone-800 dark:bg-stone-900">
                    <a href="{{ route('profiles.show', $listing->user->username) }}" class="flex items-center gap-3">
                        <x-avatar :user="$listing->user" size="h-11 w-11" text="text-base" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5 text-sm font-extrabold leading-tight text-stone-800 dark:text-stone-100">
                                {{ $listing->user->name }}
                                <x-trust-badge :user="$listing->user" />
                                @if ($listing->user->is_verified)<x-verified-badge />@endif
                            </div>
                            <div class="mt-0.5 text-xs font-semibold text-stone-500 dark:text-stone-400">
                                @if ($sellerRating['count'] > 0)
                                    ★ {{ $sellerRating['avg'] }} · {{ $sellerRating['count'] }} değerlendirme ·
                                @endif
                                {{ $listing->user->created_at->year }}'ten beri üye
                            </div>
                        </div>
                        {{-- text-stone-400 DEĞİL: MetinKontrastTest onu 2.59:1 ile
                             yakalıyor (açık zeminde okunmuyor). --}}
                        <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-stone-600 dark:text-stone-400" />
                    </a>

                    @include('partials.seller-listing-links', ['seller' => $listing->user, 'counts' => $sellerListingCounts])

                    {{-- İletişim formu mobilde bu şeridin ALTINDA kalıyor; bağlantı
                         oraya götürür. Formu burada TEKRARLAMAK iki ayrı form,
                         iki ayrı `old()` durumu ve iki bakım noktası demekti. --}}
                    @if (! $isArchived && ! $isOwner)
                        <a href="#satici-iletisim" class="mt-2 flex h-11 w-full items-center justify-center gap-2 rounded-[13px] bg-emerald-700 text-sm font-bold text-white transition hover:brightness-95 dark:bg-emerald-500 dark:text-stone-900">
                            <x-heroicon-o-chat-bubble-left class="h-4 w-4" /> Satıcıya mesaj yaz
                        </a>
                    @endif
                </div>

                {{-- Emlak özellikleri --}}
                @if ($listing->type->value === 'emlak' && $listing->propertyDetail)
                    @php $detail = $listing->propertyDetail; @endphp
                    <div class="rounded-2xl border border-stone-200/60 bg-white px-5 py-5 shadow-brand sm:px-6 dark:border-stone-800 dark:bg-stone-900">
                        <h2 class="text-lg font-extrabold tracking-[-0.018em] text-stone-800 dark:text-stone-100">🏡 Emlak Özellikleri</h2>
                        <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @php
                                $emlakAlanlari = collect([
                                    ['Oda sayısı', $detail->rooms ?: null],
                                    ['Brüt alan', $detail->area_m2 ? $detail->area_m2.' m²' : null],
                                    ['Bulunduğu kat', $detail->floor !== null ? (string) $detail->floor : null],
                                    ['Eşyalı', $detail->furnished ? 'Evet' : 'Hayır'],
                                    ['Depozito', $detail->deposit !== null ? \App\Support\Para::bicimle($detail->deposit).' '.$listing->currency : null],
                                    ['Müsait tarih', $detail->available_from?->format('d.m.Y')],
                                    ['Konuk kapasitesi', $detail->max_guests ? $detail->max_guests.' kişi' : null],
                                    ['Min. konaklama', $detail->min_stay_nights ? $detail->min_stay_nights.' gece' : null],
                                ])->filter(fn ($alan) => $alan[1] !== null);
                            @endphp
                            @foreach ($emlakAlanlari as [$etiket, $deger])
                                <div class="rounded-[14px] border border-stone-200 p-3 dark:border-stone-700">
                                    <dt class="text-xs font-semibold text-stone-600 dark:text-stone-400">{{ $etiket }}</dt>
                                    <dd class="mt-1.5 text-sm font-bold leading-tight text-stone-800 dark:text-stone-100">{{ $deger }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        @if ($detail->badgeLabels())
                            <div class="mt-4 flex flex-wrap gap-1.5 border-t border-stone-100 pt-3 dark:border-stone-800">
                                @foreach ($detail->badgeLabels() as $badge)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">✓ {{ $badge }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Vasıta özellikleri --}}
                @if ($listing->type->value === 'vasita' && $listing->vehicleDetail)
                    @php $vehicle = $listing->vehicleDetail; @endphp
                    <div class="rounded-2xl border border-stone-200/60 bg-white px-5 py-5 shadow-brand sm:px-6 dark:border-stone-800 dark:bg-stone-900">
                        <h2 class="text-lg font-extrabold tracking-[-0.018em] text-stone-800 dark:text-stone-100">🚗 Araç Özellikleri</h2>
                        <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @php
                                $vasitaAlanlari = collect([
                                    ['Marka', $vehicle->brand ?: null],
                                    ['Model', $vehicle->model ?: null],
                                    ['Model yılı', $vehicle->year ? (string) $vehicle->year : null],
                                    ['Kilometre', $vehicle->mileage_km !== null ? number_format($vehicle->mileage_km, 0, ',', '.').' km' : null],
                                    ['Yakıt', $vehicle->fuelLabel() ?: null],
                                    ['Vites', $vehicle->transmissionLabel() ?: null],
                                    ['Kasa tipi', $vehicle->bodyTypeLabel() ?: null],
                                    ['Renk', $vehicle->color ?: null],
                                    ['Min. kiralama', $vehicle->min_rental_days ? $vehicle->min_rental_days.' gün' : null],
                                    ['Depozito', $vehicle->deposit !== null ? \App\Support\Para::bicimle($vehicle->deposit).' '.$listing->currency : null],
                                    ['Günlük km sınırı', $vehicle->km_limit_per_day ? number_format($vehicle->km_limit_per_day, 0, ',', '.').' km' : null],
                                ])->filter(fn ($alan) => $alan[1] !== null);
                            @endphp
                            @foreach ($vasitaAlanlari as [$etiket, $deger])
                                <div class="rounded-[14px] border border-stone-200 p-3 dark:border-stone-700">
                                    <dt class="text-xs font-semibold text-stone-600 dark:text-stone-400">{{ $etiket }}</dt>
                                    <dd class="mt-1.5 text-sm font-bold leading-tight text-stone-800 dark:text-stone-100">{{ $deger }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        @if ($vehicle->badgeLabels())
                            <div class="mt-4 flex flex-wrap gap-1.5 border-t border-stone-100 pt-3 dark:border-stone-800">
                                @foreach ($vehicle->badgeLabels() as $badge)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">✓ {{ $badge }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Faz M5: boyut karşılaştırma (yalnızca ürün + en az bir ölçü girilmişse) --}}
                @if ($listing->type->value === 'urun' && ($listing->width_cm || $listing->height_cm))
                    <div class="rounded-2xl border border-stone-200/60 bg-white px-5 py-5 shadow-brand sm:px-6 dark:border-stone-800 dark:bg-stone-900">
                        <x-size-compare :width="$listing->width_cm" :height="$listing->height_cm" />
                    </div>
                @endif

                {{-- Emlak/vasıta dolandırıcılık uyarısı --}}
                @if ($listing->type->value === 'emlak')
                    <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                        <x-heroicon-o-shield-exclamation class="mt-0.5 h-5 w-5 shrink-0" />
                        <div>
                            <strong>Güvenlik hatırlatması:</strong> Evi görmeden asla kapora, depozito veya kira ödemesi yapmayın.
                            Anahtar "postayla gönderilecek" diyen ilan sahiplerine itibar etmeyin; şüpheli ilanı şikayet edin.
                        </div>
                    </div>
                @elseif ($listing->type->value === 'vasita')
                    <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                        <x-heroicon-o-shield-exclamation class="mt-0.5 h-5 w-5 shrink-0" />
                        <div>
                            <strong>Güvenlik hatırlatması:</strong> Aracı görmeden asla kapora veya ödeme yapmayın.
                            Km ve hasar beyanı ilan sahibinin sorumluluğundadır — alım öncesi bağımsız ekspertiz önerilir; şüpheli ilanı şikayet edin.
                        </div>
                    </div>
                @endif

                {{-- Değerlendirmeler (Faz P4) — satıcının son yorumları.
                     Veri ListingController'da YALNIZ vitrin aktifken yükleniyor;
                     yorum yoksa blok hiç basılmaz. --}}
                @if ($recentReviews->isNotEmpty())
                    <div class="rounded-2xl border border-stone-200/60 bg-white px-5 py-5 shadow-brand sm:px-6 dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-lg font-extrabold tracking-[-0.018em] text-stone-800 dark:text-stone-100">Değerlendirmeler</h2>
                            @if ($sellerRating['count'] > 0)
                                <div class="flex items-baseline gap-2">
                                    <span class="text-lg font-extrabold text-stone-800 dark:text-stone-100">★ {{ $sellerRating['avg'] }}</span>
                                    <span class="text-xs font-semibold text-stone-500 dark:text-stone-400">{{ $sellerRating['count'] }} değerlendirme</span>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 grid gap-3">
                            @foreach ($recentReviews as $review)
                                <div class="flex gap-3 rounded-[14px] border border-stone-200 p-3.5 dark:border-stone-700">
                                    <x-avatar :user="$review->reviewer" size="h-10 w-10" text="text-sm" />
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-baseline gap-2">
                                            <span class="text-sm font-bold text-stone-800 dark:text-stone-100">{{ $review->reviewer->name }}</span>
                                            <span class="text-xs font-semibold text-stone-600 dark:text-stone-400">
                                                @if ($review->reviewer->city){{ $review->reviewer->city }} · @endif{{ $review->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        <div class="mt-0.5 text-xs font-bold text-amber-500 dark:text-amber-400" aria-label="{{ $review->rating }} yıldız">
                                            {{ str_repeat('★', (int) $review->rating) }}<span class="text-stone-300 dark:text-stone-400">{{ str_repeat('★', 5 - (int) $review->rating) }}</span>
                                        </div>
                                        @if ($review->comment)
                                            <p class="mt-1.5 text-sm font-medium leading-relaxed text-stone-600 dark:text-stone-300">{{ $review->comment }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($sellerRating['count'] > $recentReviews->count())
                            <a href="{{ route('profiles.show', $listing->user->username) }}" class="mt-3 inline-block text-sm font-bold text-emerald-700 hover:underline dark:text-emerald-400">
                                Tüm değerlendirmeleri gör →
                            </a>
                        @endif
                    </div>
                @endif

                @auth
                    @unless ($isOwner)
                        <details class="text-sm">
                            <summary class="inline-flex cursor-pointer items-center gap-1 font-semibold text-stone-600 hover:text-stone-600 dark:text-stone-400 dark:hover:text-stone-300">
                                <x-heroicon-o-flag class="h-4 w-4" /> Bu ilanı şikayet et
                            </summary>
                            <form method="POST" action="{{ route('reports.store', $listing) }}" class="mt-3 max-w-md space-y-2 rounded-2xl border border-stone-200/60 bg-white p-4 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                                @csrf
                                <select name="reason" required class="w-full rounded-[9px] border-stone-300 px-3 py-2 text-sm font-semibold text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                    <option value="">Sebep seç...</option>
                                    <option value="Yanıltıcı / sahte ilan">Yanıltıcı / sahte ilan</option>
                                    <option value="Uygunsuz içerik">Uygunsuz içerik</option>
                                    <option value="Dolandırıcılık şüphesi">Dolandırıcılık şüphesi</option>
                                    <option value="Yanlış kategori">Yanlış kategori</option>
                                    <option value="Diğer">Diğer</option>
                                </select>
                                <textarea name="note" rows="2" placeholder="Eklemek istediğin not (ops.)" class="w-full rounded-[9px] border-stone-300 px-3 py-2 text-sm text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500"></textarea>
                                <button type="submit" class="rounded-[11px] bg-stone-800 px-4 py-2 text-sm font-bold text-white transition hover:brightness-110 dark:bg-stone-700">Şikayeti gönder</button>
                            </form>
                        </details>
                    @endunless
                @endauth
            </div>

            {{-- Sağ kolon (yapışkan) --}}
            <div class="grid gap-4 lg:sticky lg:top-[90px] lg:self-start">
                {{-- Fiyat + aksiyon kartı --}}
                <div id="satici-iletisim" class="scroll-mt-24 rounded-2xl border border-stone-200/60 bg-white p-5 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                    <div class="text-2xl font-extrabold text-stone-800 dark:text-stone-50">
                        @if ($listing->price !== null)
                            {{ $listing->bicimliFiyat() }} {{ $listing->currency }}
                            <span class="text-sm font-semibold text-stone-600 dark:text-stone-400">{{ $listing->price_unit->suffix() }}</span>
                        @else
                            <span class="text-emerald-700 dark:text-emerald-400">Görüşülür</span>
                        @endif
                    </div>

                    <div class="mt-4 space-y-2.5">
                        {{-- ARŞİVDE İLETİŞİM KAPALI. Formu göstermek, cevabı hiç
                             gelmeyecek bir mesajın yazılmasına davet olurdu;
                             asıl kapı MessageController::start() içinde. --}}
                        @if ($isArchived && ! $isOwner)
                            <div class="rounded-[13px] bg-stone-100 px-4 py-3 text-center text-sm font-semibold text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                                Bu ilan yayından kalktığı için mesaj gönderilemez.
                            </div>
                        @elseif ($isOwner)
                            <a href="{{ route('panel.listings.edit', $listing) }}" class="block w-full rounded-[13px] border border-stone-300 px-4 py-3 text-center text-sm font-bold text-stone-800 transition hover:bg-stone-100 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800">İlanı Düzenle</a>
                        @elseif (auth()->check())
                            @php
                                $isShortTerm = ($listing->type->value === 'emlak' && ($listing->category?->slug === 'kisa-donem-tatil' || $listing->propertyDetail?->max_guests))
                                    || ($listing->type->value === 'vasita' && $listing->category?->slug === 'kiralik-arac');
                            @endphp
                            <form method="POST" action="{{ route('messages.start', $listing) }}" class="space-y-2">
                                @csrf
                                @if ($isShortTerm)
                                    <div class="rounded-xl bg-stone-100 p-3 dark:bg-stone-800/60">
                                        <p class="text-xs font-semibold text-stone-500 dark:text-stone-400">📅 Tarih seç, talep mesajına eklensin <span class="font-normal">(ops.)</span></p>
                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                            <input name="giris" type="date" value="{{ old('giris') }}" aria-label="Giriş tarihi"
                                                   class="w-full rounded-[9px] border-stone-300 px-2 py-1.5 text-xs font-semibold text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                            <input name="cikis" type="date" value="{{ old('cikis') }}" aria-label="Çıkış tarihi"
                                                   class="w-full rounded-[9px] border-stone-300 px-2 py-1.5 text-xs font-semibold text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                        </div>
                                        <input name="kisi" type="number" min="1" max="50" value="{{ old('kisi') }}" placeholder="Kişi sayısı (ops.)"
                                               class="mt-2 w-full rounded-[9px] border-stone-300 px-2 py-1.5 text-xs font-semibold text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                                        @error('giris') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                        @error('cikis') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                                <textarea name="body" rows="3" required placeholder="{{ $listing->type->value === 'emlak' ? 'İlan sahibine bir mesaj yaz...' : 'Satıcıya bir mesaj yaz...' }}"
                                          class="w-full rounded-[13px] border-stone-300 px-3 py-2.5 text-sm text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">{{ old('body') }}</textarea>
                                @error('body') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                <button type="submit" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-[13px] bg-emerald-700 text-sm font-bold text-white shadow-[0_14px_26px_-14px_rgba(62,99,240,1)] transition hover:brightness-95 dark:bg-emerald-500 dark:text-stone-900 dark:shadow-none">
                                    <x-heroicon-o-chat-bubble-left class="h-4 w-4" /> Mesaj Gönder
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="block w-full rounded-[13px] bg-emerald-700 px-4 py-3 text-center text-sm font-bold text-white shadow-[0_14px_26px_-14px_rgba(62,99,240,1)] transition hover:brightness-95 dark:bg-emerald-500 dark:text-stone-900 dark:shadow-none">Mesaj göndermek için giriş yap</a>
                        @endif

                        @auth
                            @unless ($isOwner)
                                <form method="POST" action="{{ route('favorites.toggle', $listing) }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-[13px] border px-4 py-3 text-sm font-bold transition {{ $isFavorited ? 'border-red-200 bg-red-50 text-red-600 dark:border-red-800 dark:bg-red-950/30 dark:text-red-400' : 'border-stone-300 text-stone-800 hover:bg-stone-100 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800' }}">
                                        @if ($isFavorited)
                                            <x-heroicon-s-heart class="h-5 w-5" /> Favorilerde
                                        @else
                                            <x-heroicon-o-heart class="h-5 w-5" /> Favorilere ekle
                                        @endif
                                    </button>
                                </form>
                            @endunless
                        @endauth
                    </div>

                    <div class="mt-4 border-t border-stone-100 pt-3 dark:border-stone-800">
                        <p class="mb-2 text-xs font-semibold text-stone-500 dark:text-stone-400">Bu ilanı paylaş</p>
                        @include('partials.share-buttons', ['shareUrl' => route('listings.show', [$listing, $listing->slug]), 'shareText' => $listing->title, 'cardUrl' => route('listings.card', $listing)])
                    </div>
                </div>

                {{-- Emlak/vasıta müsaitlik takvimi --}}
                @if (in_array($listing->type->value, ['emlak', 'vasita'], true) && $listing->relationLoaded('unavailableRanges'))
                    <div class="rounded-2xl border border-stone-200/60 bg-white p-5 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                        <h2 class="text-sm font-extrabold tracking-[-0.015em] text-stone-800 dark:text-stone-100">📅 Müsaitlik</h2>
                        <div class="mt-3">
                            <x-availability-calendar :ranges="$listing->unavailableRanges" />
                        </div>
                    </div>
                @endif

                {{-- Satıcı kartı --}}
                <div class="rounded-2xl border border-stone-200/60 bg-white p-5 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$listing->user" size="h-12 w-12" text="text-lg" />
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5 text-base font-extrabold leading-tight text-stone-800 dark:text-stone-100">
                                {{ $listing->user->name }}
                                <x-trust-badge :user="$listing->user" />
                                @if ($listing->user->is_verified)<x-verified-badge />@endif
                            </div>
                            <div class="mt-1 text-xs font-semibold text-stone-500 dark:text-stone-400">Üyelik: {{ $listing->user->created_at->translatedFormat('F Y') }}</div>
                        </div>
                    </div>

                    {{-- KPI kutuları: yalnız GERÇEK veriden (puan + üyelik yılı) --}}
                    <div class="mt-4 grid grid-cols-2 gap-2.5">
                        <div class="rounded-xl bg-stone-100 p-3 dark:bg-stone-800">
                            <div class="text-2xs font-semibold text-stone-600 dark:text-stone-400">Puan</div>
                            <div class="mt-1 text-base font-extrabold text-stone-800 dark:text-stone-100">
                                @if ($sellerRating['count'] > 0)
                                    ★ {{ $sellerRating['avg'] }} <span class="text-2xs font-semibold text-stone-600">({{ $sellerRating['count'] }} değerlendirme)</span>
                                @else
                                    <span class="text-xs font-semibold text-stone-600">Henüz yok</span>
                                @endif
                            </div>
                        </div>
                        <div class="rounded-xl bg-stone-100 p-3 dark:bg-stone-800">
                            <div class="text-2xs font-semibold text-stone-600 dark:text-stone-400">Üyelik</div>
                            <div class="mt-1 text-base font-extrabold text-stone-800 dark:text-stone-100">{{ $listing->user->created_at->year }}</div>
                        </div>
                    </div>

                    @if ($listing->user->paymentLinks->isNotEmpty())
                        <div class="mt-3 flex flex-wrap items-center gap-1.5">
                            @foreach ($listing->user->paymentLinks as $link)
                                @if ($link->detailIsLink())
                                    <a href="{{ $link->detail }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-600 transition hover:bg-emerald-50 hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-300" title="{{ $link->method->getLabel() }} — kendi ödeme sayfasına git">
                                        <span aria-hidden="true">{{ $link->method->icon() }}</span>{{ $link->method->getLabel() }} ↗
                                    </a>
                                @elseif ($link->qr_path)
                                    <a href="{{ Storage::url($link->qr_path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-600 transition hover:bg-emerald-50 hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-300" title="{{ $link->method->getLabel() }} — QR kodu gör">
                                        <span aria-hidden="true">{{ $link->method->icon() }}</span>{{ $link->method->getLabel() }} 🔳
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                                        <span aria-hidden="true">{{ $link->method->icon() }}</span>{{ $link->method->getLabel() }}@if ($link->detail) — {{ $link->detail }}@endif
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @include('partials.seller-listing-links', ['seller' => $listing->user, 'counts' => $sellerListingCounts])

                    <a href="{{ route('profiles.show', $listing->user->username) }}" class="mt-3 block text-sm font-bold text-emerald-700 hover:underline dark:text-emerald-400">Profili ve değerlendirmeleri gör →</a>
                    @include('partials.payment-safety-card', ['seller' => $listing->user])
                </div>

                {{-- Benzer ilanlar (Faz P4) — aynı kategori, aynı şehir öncelikli.
                     Veri yalnız vitrin aktifken yükleniyor; yoksa blok basılmaz. --}}
                @if ($similarListings->isNotEmpty())
                    <div class="rounded-2xl border border-stone-200/60 bg-white px-5 py-[18px] shadow-brand dark:border-stone-800 dark:bg-stone-900">
                        <h2 class="text-sm font-extrabold tracking-[-0.015em] text-stone-800 dark:text-stone-100">Benzer ilanlar</h2>
                        <div class="mt-3.5 grid gap-3">
                            @foreach ($similarListings as $benzer)
                                <a href="{{ route('listings.show', [$benzer, $benzer->slug]) }}" class="group flex items-center gap-3">
                                    <div class="h-11 w-14 shrink-0 overflow-hidden rounded-[10px] bg-stone-100 dark:bg-stone-800">
                                        @if ($benzer->coverImage)
                                            <img src="{{ $benzer->coverImage->enIyiUrl('thumb') }}"
                                                 alt="" width="56" height="44" loading="lazy" decoding="async"
                                                 class="h-full w-full object-cover"
                                                 style="object-position: {{ $benzer->coverImage->objectPosition() }}">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-stone-300 dark:text-stone-400">
                                                <x-heroicon-o-photo class="h-4 w-4" />
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="line-clamp-2 text-xs font-bold leading-[1.35] text-stone-800 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-400" style="text-wrap: pretty">{{ $benzer->title }}</div>
                                        <div class="mt-1 flex flex-wrap items-baseline gap-2">
                                            @if ($benzer->country)
                                                <span class="text-2xs font-semibold text-stone-500 dark:text-stone-400">{{ $benzer->country->emoji }} {{ $benzer->city ?: $benzer->country->name_tr }}</span>
                                            @endif
                                            <span class="text-xs font-extrabold text-emerald-700 dark:text-emerald-400">
                                                @if ($benzer->price !== null)
                                                    {{ $benzer->bicimliFiyat() }} {{ $benzer->currency }}
                                                @else
                                                    Görüşülür
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Alan: sidebar alt (reklam/duyuru) --}}
                <x-zone zone-key="ilan_detay_yan" />
            </div>
        </div>
    </div>
</x-layouts.app>
