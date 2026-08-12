{{-- Arşiv sayfası arama motoruna kapalı — bkz. vitrin/listings/show.blade.php --}}
<x-layouts.app :title="$listing->title.' — Nisoya'" :description="\Illuminate\Support\Str::limit(strip_tags($listing->description), 150)" :ogImage="$listing->coverImage?->enIyiUrl('large')" :noindex="$noindex">
    {{-- ÖRNEK İLANDA YAPILANDIRILMIŞ VERİ BASILMAZ.

         `noindex` sayfayı arama sonucundan çıkarır; JSON-LD ise ayrı bir
         kanaldır — "şu ürün, şu fiyata, şu satıcıdan, stokta" iddiası zengin
         sonuç beslemelerine sayfa etiketinden bağımsız girebilir. Uydurma bir
         ilan için fiyat/stok/sağlayıcı yayınlamak, örnek satıcı profilinde
         AggregateRating basmakla aynı şey (bkz. profiles/show.blade.php).
         Kaynağında kesilir. --}}
    @unless ($listing->is_demo)
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
    @endunless

    <div class="mx-auto max-w-5xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        {{-- Breadcrumb --}}
        <nav class="text-sm text-stone-500 dark:text-stone-400">
            <a href="{{ url('/ilanlar') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İlanlar</a>
            @if ($listing->category)
                <span class="mx-1">/</span>
                <a href="{{ url('/ilanlar/kategori/'.$listing->category->slug) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">{{ $listing->category->name }}</a>
            @endif
        </nav>

        {{-- Arşiv bandı — vitrin temasıyla aynı sözleşme. --}}
        @if ($isArchived && ! $isOwner)
            <div class="mt-4 flex items-start gap-3 rounded-2xl border border-stone-300 bg-stone-100 px-4 py-3 text-sm text-stone-700 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-300">
                <x-heroicon-o-archive-box class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <strong class="text-stone-900 dark:text-stone-100">Bu ilan artık yayında değil.</strong>
                    Geçmiş kaydı olarak görüntülüyorsun — mesaj gönderilemez.
                    <a href="{{ route('profiles.show', $listing->user->username) }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">{{ $listing->user->name }} kullanıcısının güncel ilanlarına bak →</a>
                </div>
            </div>
        @elseif ($isOwner && $listing->status->value !== 'aktif')
            <div class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                Bu ilan şu an <strong>{{ $listing->status->getLabel() }}</strong> durumunda — yalnızca sen görüyorsun.
            </div>
        @endif

        <div class="mt-4 grid gap-8 lg:grid-cols-3">
            {{-- Sol: görseller + açıklama --}}
            <div class="lg:col-span-2">
                @if ($listing->images->isNotEmpty())
                    @php
                        $hero = $listing->coverImage ?? $listing->images->first();
                        $heroSrcset = $hero->srcset();
                        $heroLarge = $hero->enIyiUrl('large');
                        $heroMedium = $hero->enIyiUrl('medium');
                    @endphp
                    {{-- GÖRSEL KIRPILMAZ.

                         Eskiden `object-cover max-h-[420px]` idi: fotoğraflarda
                         düzgün duruyordu ama satıcıların yüklediği afiş/
                         infografik türü görsellerin ÜST KISMI kesiliyordu —
                         canlıda bir ilanın başlığı görselin içinde kaybolmuştu.
                         İlan görselinin işi bilgi taşımak; kırpmak o bilgiyi yok
                         eder.

                         Çözüm: görsel `object-contain` ile TAM görünür, arkada
                         aynı görselin bulanık ve büyütülmüş bir kopyası zemini
                         doldurur. Böylece ne kırpma olur ne de dar görsellerin
                         yanında boş gri bantlar kalır. Arka kopya `aria-hidden`
                         ve `alt=""` — ekran okuyucuya aynı görseli iki kez
                         okutmaz. --}}
                    <div class="relative overflow-hidden rounded-2xl bg-stone-100 ring-1 ring-stone-200/70 dark:bg-stone-800 dark:ring-stone-700/60">
                        <img src="{{ $heroMedium }}" alt="" aria-hidden="true" loading="lazy"
                             class="pointer-events-none absolute inset-0 h-full w-full scale-110 object-cover blur-2xl saturate-150 opacity-60 dark:opacity-40">
                        <img src="{{ $heroLarge }}"
                             srcset="{{ $heroMedium }} 800w, {{ $heroLarge }} 1600w"
                             sizes="(min-width: 1024px) 800px, 100vw"
                             alt="{{ $listing->title }}"
                             width="800"
                             height="420"
                             fetchpriority="high"
                             style="--listing-transition-name: listing-image-{{ $listing->id }}"
                             class="listing-cover-transition relative mx-auto max-h-[420px] w-full object-contain">
                        {{-- Detayda BÜYÜK: alıcı kararını burada veriyor. --}}
                        <x-temsili-rozet :gorsel="$hero" buyuk class="absolute bottom-3 left-3" />
                    </div>
                    @if ($listing->images->count() > 1)
                        <div class="mt-3 grid grid-cols-4 gap-3 sm:grid-cols-5">
                            @foreach ($listing->images as $image)
                                @php $thumb = $image->enIyiUrl('thumb'); @endphp
                                <img src="{{ $thumb }}"
                                     srcset="{{ ($image->enIyiUrl('thumb')) }} 300w, {{ ($image->enIyiUrl('medium')) }} 800w"
                                     sizes="120px"
                                     alt=""
                                     width="120"
                                     height="120"
                                     loading="lazy"
                                     decoding="async"
                                     style="object-position: {{ $image->objectPosition() }}"
                                     class="aspect-square w-full rounded-lg object-cover">
                            @endforeach
                        </div>
                    @endif
                @else
                    @php($fallbackIcon = \App\Support\CategoryIcon::heroicon($listing->category?->parent?->icon ?? $listing->category?->icon))
                    <div style="--listing-transition-name: listing-image-{{ $listing->id }}" class="listing-cover-transition flex h-56 items-center justify-center rounded-2xl bg-stone-100 text-stone-300 dark:bg-stone-800 dark:text-stone-400">
                        <x-dynamic-component :component="'heroicon-o-'.$fallbackIcon" class="h-16 w-16" />
                    </div>
                @endif

                <x-ornek-isareti :listing="$listing" bicim="bant" />
                <h1 class="mt-6 text-2xl font-bold text-stone-900 dark:text-stone-50">{{ $listing->title }}</h1>

                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                    @if ($listing->isCurrentlyFeatured())
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                            <x-heroicon-s-star class="h-3.5 w-3.5" /> Öne çıkan
                        </span>
                    @endif
                    @if ($listing->country)
                        <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-3 py-1 text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                            {{ $listing->country->emoji }} @if ($listing->city){{ $listing->city }}, @endif{{ $listing->country->name_tr }}
                        </span>
                    @endif
                    @if ($listing->is_remote)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            <x-heroicon-o-globe-alt class="h-3.5 w-3.5" /> Uzaktan / Online
                        </span>
                    @endif
                    @if ($listing->type->value === 'urun' && $listing->stock !== null)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                            <x-heroicon-o-archive-box class="h-3.5 w-3.5" /> Stokta {{ $listing->stock }} adet
                        </span>
                    @endif
                </div>

                {{-- İlan numarası — Vitrin şablonuyla AYNI sözleşme (NS-{id}).
                     Destek bileti/şikayet açan kişinin ilanı tarif etmek yerine
                     tek bir numarayla gösterebilmesi için herkese görünür. --}}
                <p class="mt-2 text-xs font-semibold text-stone-500 dark:text-stone-400">
                    İlan no <b class="text-stone-700 dark:text-stone-200">NS-{{ $listing->id }}</b>
                </p>

                {{-- Emlak özellikleri --}}
                @if ($listing->type->value === 'emlak' && $listing->propertyDetail)
                    @php($detail = $listing->propertyDetail)
                    <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">🏡 Emlak Özellikleri</h2>
                        <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                            @if ($detail->rooms)
                                <div><dt class="text-stone-600 dark:text-stone-400">Oda sayısı</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $detail->rooms }}</dd></div>
                            @endif
                            @if ($detail->area_m2)
                                <div><dt class="text-stone-600 dark:text-stone-400">Brüt alan</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $detail->area_m2 }} m²</dd></div>
                            @endif
                            @if ($detail->floor !== null)
                                <div><dt class="text-stone-600 dark:text-stone-400">Bulunduğu kat</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $detail->floor }}</dd></div>
                            @endif
                            <div><dt class="text-stone-600 dark:text-stone-400">Eşyalı</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $detail->furnished ? 'Evet' : 'Hayır' }}</dd></div>
                            @if ($detail->deposit !== null)
                                <div><dt class="text-stone-600 dark:text-stone-400">Depozito</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ number_format((float) $detail->deposit, 0) }} {{ $listing->currency }}</dd></div>
                            @endif
                            @if ($detail->available_from)
                                <div><dt class="text-stone-600 dark:text-stone-400">Müsait tarih</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $detail->available_from->format('d.m.Y') }}</dd></div>
                            @endif
                            @if ($detail->max_guests)
                                <div><dt class="text-stone-600 dark:text-stone-400">Konuk kapasitesi</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $detail->max_guests }} kişi</dd></div>
                            @endif
                            @if ($detail->min_stay_nights)
                                <div><dt class="text-stone-600 dark:text-stone-400">Min. konaklama</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $detail->min_stay_nights }} gece</dd></div>
                            @endif
                        </dl>
                        @if ($detail->badgeLabels())
                            <div class="mt-4 flex flex-wrap gap-1.5 border-t border-stone-100 pt-3 dark:border-stone-800">
                                @foreach ($detail->badgeLabels() as $badge)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">✓ {{ $badge }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Vasıta özellikleri --}}
                @if ($listing->type->value === 'vasita' && $listing->vehicleDetail)
                    @php($vehicle = $listing->vehicleDetail)
                    <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">🚗 Araç Özellikleri</h2>
                        <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                            @if ($vehicle->brand)
                                <div><dt class="text-stone-600 dark:text-stone-400">Marka</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $vehicle->brand }}</dd></div>
                            @endif
                            @if ($vehicle->model)
                                <div><dt class="text-stone-600 dark:text-stone-400">Model</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $vehicle->model }}</dd></div>
                            @endif
                            @if ($vehicle->year)
                                <div><dt class="text-stone-600 dark:text-stone-400">Model yılı</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $vehicle->year }}</dd></div>
                            @endif
                            @if ($vehicle->mileage_km !== null)
                                <div><dt class="text-stone-600 dark:text-stone-400">Kilometre</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ number_format($vehicle->mileage_km, 0, ',', '.') }} km</dd></div>
                            @endif
                            @if ($vehicle->fuelLabel())
                                <div><dt class="text-stone-600 dark:text-stone-400">Yakıt</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $vehicle->fuelLabel() }}</dd></div>
                            @endif
                            @if ($vehicle->transmissionLabel())
                                <div><dt class="text-stone-600 dark:text-stone-400">Vites</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $vehicle->transmissionLabel() }}</dd></div>
                            @endif
                            @if ($vehicle->bodyTypeLabel())
                                <div><dt class="text-stone-600 dark:text-stone-400">Kasa tipi</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $vehicle->bodyTypeLabel() }}</dd></div>
                            @endif
                            @if ($vehicle->color)
                                <div><dt class="text-stone-600 dark:text-stone-400">Renk</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $vehicle->color }}</dd></div>
                            @endif
                            @if ($vehicle->min_rental_days)
                                <div><dt class="text-stone-600 dark:text-stone-400">Min. kiralama</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $vehicle->min_rental_days }} gün</dd></div>
                            @endif
                            @if ($vehicle->deposit !== null)
                                <div><dt class="text-stone-600 dark:text-stone-400">Depozito</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ number_format((float) $vehicle->deposit, 0) }} {{ $listing->currency }}</dd></div>
                            @endif
                            @if ($vehicle->km_limit_per_day)
                                <div><dt class="text-stone-600 dark:text-stone-400">Günlük km sınırı</dt><dd class="font-semibold text-stone-800 dark:text-stone-100">{{ number_format($vehicle->km_limit_per_day, 0, ',', '.') }} km</dd></div>
                            @endif
                        </dl>
                        @if ($vehicle->badgeLabels())
                            <div class="mt-4 flex flex-wrap gap-1.5 border-t border-stone-100 pt-3 dark:border-stone-800">
                                @foreach ($vehicle->badgeLabels() as $badge)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">✓ {{ $badge }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <div class="prose prose-stone mt-6 max-w-none text-stone-700 dark:prose-invert dark:text-stone-300">
                    {!! nl2br(e($listing->description)) !!}
                </div>

                {{-- Mobilde satıcı kimliği — vitrin şablonuyla AYNI partial.
                     Bu şablon canlıda basılan şablondur; şerit ilk yazışta
                     yalnız vitrin'e eklenmiş ve hiç görünmemişti. --}}
                <div class="mt-6">
                    @include('partials.seller-mobile-strip')
                </div>

                {{-- Faz M5: boyut karşılaştırma (yalnızca ürün + en az bir ölçü girilmişse) --}}
                @if ($listing->type->value === 'urun' && ($listing->width_cm || $listing->height_cm))
                    <div class="mt-6">
                        <x-size-compare :width="$listing->width_cm" :height="$listing->height_cm" />
                    </div>
                @endif

                {{-- Emlak/vasıta dolandırıcılık uyarısı --}}
                @if ($listing->type->value === 'emlak')
                    <div class="mt-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                        <x-heroicon-o-shield-exclamation class="mt-0.5 h-5 w-5 shrink-0" />
                        <div>
                            <strong>Güvenlik hatırlatması:</strong> Evi görmeden asla kapora, depozito veya kira ödemesi yapmayın.
                            Anahtar "postayla gönderilecek" diyen ilan sahiplerine itibar etmeyin; şüpheli ilanı şikayet edin.
                        </div>
                    </div>
                @elseif ($listing->type->value === 'vasita')
                    <div class="mt-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                        <x-heroicon-o-shield-exclamation class="mt-0.5 h-5 w-5 shrink-0" />
                        <div>
                            <strong>Güvenlik hatırlatması:</strong> Aracı görmeden asla kapora veya ödeme yapmayın.
                            Km ve hasar beyanı ilan sahibinin sorumluluğundadır — alım öncesi bağımsız ekspertiz önerilir; şüpheli ilanı şikayet edin.
                        </div>
                    </div>
                @endif

                @auth
                    @unless ($isOwner)
                        <details class="mt-8 text-sm">
                            <summary class="inline-flex cursor-pointer items-center gap-1 text-stone-600 hover:text-stone-600 dark:text-stone-400 dark:hover:text-stone-300">
                                <x-heroicon-o-flag class="h-4 w-4" /> Bu ilanı şikayet et
                            </summary>
                            <form method="POST" action="{{ route('reports.store', $listing) }}" class="mt-3 max-w-md space-y-2 rounded-lg border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
                                @csrf
                                <select name="reason" required class="w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                    <option value="">Sebep seç...</option>
                                    <option value="Yanıltıcı / sahte ilan">Yanıltıcı / sahte ilan</option>
                                    <option value="Uygunsuz içerik">Uygunsuz içerik</option>
                                    <option value="Dolandırıcılık şüphesi">Dolandırıcılık şüphesi</option>
                                    <option value="Yanlış kategori">Yanlış kategori</option>
                                    <option value="Diğer">Diğer</option>
                                </select>
                                <textarea name="note" rows="2" placeholder="Eklemek istediğin not (ops.)" class="w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500"></textarea>
                                <button type="submit" class="rounded-lg bg-stone-800 px-4 py-2 text-sm font-medium text-white hover:bg-stone-900 dark:bg-stone-700 dark:hover:bg-stone-600">Şikayeti gönder</button>
                            </form>
                        </details>
                    @endunless
                @else
                    {{-- Misafir de şüpheli ilanı bildirebilmeli. Şikayet kaydı
                         şikayet edeni zorunlu tuttuğu için (reports.reporter_id)
                         form değil, girişe yönlendiren bir yol gösteriyoruz —
                         yoksa giriş yapmamış kişinin hiçbir çıkışı yok. --}}
                    <p class="mt-8 text-sm">
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-stone-600 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200">
                            <x-heroicon-o-flag class="h-4 w-4" /> Bu ilanı şikayet et
                        </a>
                    </p>
                @endauth

                {{-- GÜVENLİ ÖDEME KARTI — MASAÜSTÜNDE BURADA, MOBİLDE YAN SÜTUNDA.

                     Sorun yerleşimseldi: yan sütun (fiyat + satıcı + ödeme
                     kartı) sol sütundan uzun olduğu için sol taraf erken
                     bitiyor ve altında yüzlerce piksel boşluk kalıyordu.
                     Boşluk ızgaranın AYNI SATIRI içinde olduğundan, alta yeni
                     bir satır eklemek onu doldurmaz — dolduracak şeyin sol
                     sütunun İÇİNDE olması gerekir.

                     Kartın en uzun sidebar parçası olması iki işi birden
                     görüyor: solu doldururken sağı kısaltıyor.

                     Neden iki yerde: mobilde tek sütuna inildiğinde bu kart
                     fiyat/mesaj kutusundan ÖNCE gelirdi ve birincil eylemi
                     aşağı iterdi. `hidden lg:block` / `lg:hidden` ile her
                     ekranda yalnız BİRİ basılır (`hidden` erişilebilirlik
                     ağacından da çıkarır, ekran okuyucu iki kez okumaz). --}}
                <div class="mt-8 hidden lg:block">
                    @include('partials.payment-safety-card', ['seller' => $listing->user])
                </div>
            </div>

            {{-- Sağ: fiyat + satıcı + iletişim --}}
            <div class="lg:col-span-1">
                {{-- id: mobil şeritteki "Satıcıya mesaj yaz" kısayolunun hedefi. --}}
                <div id="satici-iletisim" class="scroll-mt-24 sticky top-20 space-y-4">
                    {{-- Birincil eylem kartı bir kademe yukarıda — Vitrin
                         şablonuyla aynı sözleşme (gerekçe orada). Kenar
                         çubuğundaki diğer kartlar `shadow-sm`de kalır; hepsi
                         eşit yükseklikte olsaydı hiçbiri öne çıkmazdı. --}}
                    <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-lg shadow-stone-900/10 dark:border-stone-700/70 dark:bg-stone-900 dark:shadow-black/40">
                        <div class="text-2xl font-bold text-stone-900 dark:text-stone-50">
                            @if ($listing->price !== null)
                                {{ $listing->bicimliFiyat() }} {{ $listing->currency }}
                                <span class="text-base font-normal text-stone-600 dark:text-stone-400">{{ $listing->price_unit->suffix() }}</span>
                            @else
                                <span class="text-emerald-700 dark:text-emerald-400">Görüşülür</span>
                            @endif
                        </div>

                        <div class="mt-4 space-y-3">
                            {{-- Arşivde iletişim kapalı — vitrin ile aynı sözleşme. --}}
                            @if ($isArchived && ! $isOwner)
                                <div class="rounded-lg bg-stone-100 px-4 py-3 text-center text-sm font-semibold text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                                    Bu ilan yayından kalktığı için mesaj gönderilemez.
                                </div>
                            @elseif ($isOwner)
                                <a href="{{ route('panel.listings.edit', $listing) }}" class="block w-full rounded-lg border border-stone-300 px-4 py-2.5 text-center font-semibold text-stone-700 hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800">İlanı Düzenle</a>
                            @elseif (auth()->check())
                                @php($isShortTerm = ($listing->type->value === 'emlak' && ($listing->category?->slug === 'kisa-donem-tatil' || $listing->propertyDetail?->max_guests))
                                    || ($listing->type->value === 'vasita' && $listing->category?->slug === 'kiralik-arac'))
                                <form method="POST" action="{{ route('messages.start', $listing) }}" class="space-y-2">
                                    @csrf
                                    @if ($isShortTerm)
                                        <div class="rounded-lg bg-stone-50 p-3 dark:bg-stone-800/60">
                                            <p class="text-xs font-medium text-stone-500 dark:text-stone-400">📅 Tarih seç, talep mesajına eklensin <span class="font-normal">(ops.)</span></p>
                                            <div class="mt-2 grid grid-cols-2 gap-2">
                                                <input name="giris" type="date" value="{{ old('giris') }}" aria-label="Giriş tarihi"
                                                       class="w-full rounded-lg border-stone-300 px-2 py-1.5 text-xs text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                                <input name="cikis" type="date" value="{{ old('cikis') }}" aria-label="Çıkış tarihi"
                                                       class="w-full rounded-lg border-stone-300 px-2 py-1.5 text-xs text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                            </div>
                                            <input name="kisi" type="number" min="1" max="50" value="{{ old('kisi') }}" placeholder="Kişi sayısı (ops.)"
                                                   class="mt-2 w-full rounded-lg border-stone-300 px-2 py-1.5 text-xs text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                                            @error('giris') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            @error('cikis') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                        </div>
                                    @endif
                                    @include('partials.quick-reply-chips')
                                    <textarea name="body" rows="3" required placeholder="{{ $listing->type->value === 'emlak' ? 'İlan sahibine bir mesaj yaz...' : 'Satıcıya bir mesaj yaz...' }}"
                                              class="w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">{{ old('body') }}</textarea>
                                    @error('body') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Mesaj Gönder</button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="block w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-center font-semibold text-white hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Mesaj göndermek için giriş yap</a>
                            @endif

                            @auth
                                @unless ($isOwner)
                                    <form method="POST" action="{{ route('favorites.toggle', $listing) }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg border px-4 py-2.5 font-medium transition {{ $isFavorited ? 'border-red-200 bg-red-50 text-red-600 dark:border-red-800 dark:bg-red-950/30 dark:text-red-400' : 'border-stone-300 text-stone-700 hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800' }}">
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
                            <p class="mb-2 text-xs font-medium text-stone-500 dark:text-stone-400">Bu ilanı paylaş</p>
                            @include('partials.share-buttons', ['shareUrl' => route('listings.show', [$listing, $listing->slug]), 'shareText' => $listing->title, 'cardUrl' => route('listings.card', $listing)])
                        </div>
                    </div>

                    {{-- Emlak/vasıta müsaitlik takvimi --}}
                    @if (in_array($listing->type->value, ['emlak', 'vasita'], true) && $listing->relationLoaded('unavailableRanges'))
                        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">📅 Müsaitlik</h2>
                            <div class="mt-3">
                                <x-availability-calendar :ranges="$listing->unavailableRanges" />
                            </div>
                        </div>
                    @endif

                    <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex items-center gap-3">
                            <x-avatar :user="$listing->user" size="h-12 w-12" text="text-lg" />
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5 font-semibold text-stone-800 dark:text-stone-100">
                                    {{ $listing->user->name }}
                                    <x-trust-badge :user="$listing->user" />
                                    @if ($listing->user->is_verified)<x-verified-badge />@endif
                                </div>
                                <div class="text-xs text-stone-500 dark:text-stone-400">Üyelik: {{ $listing->user->created_at->translatedFormat('F Y') }}</div>
                                @if ($sellerRating['count'] > 0)
                                    <div class="text-xs font-medium text-amber-500 dark:text-amber-400">★ {{ $sellerRating['avg'] }} <span class="text-stone-600 dark:text-stone-400">({{ $sellerRating['count'] }} değerlendirme)</span></div>
                                @endif
                            </div>
                        </div>
                        @if ($listing->user->paymentLinks->isNotEmpty())
                            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                @foreach ($listing->user->paymentLinks as $link)
                                    @if ($link->detailIsLink())
                                        <a href="{{ $link->detail }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600 hover:bg-emerald-100 hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-300" title="{{ $link->method->getLabel() }} — kendi ödeme sayfasına git">
                                            <span aria-hidden="true">{{ $link->method->icon() }}</span>{{ $link->method->getLabel() }} ↗
                                        </a>
                                    @elseif ($link->qr_path)
                                        <a href="{{ Storage::url($link->qr_path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600 hover:bg-emerald-100 hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-300" title="{{ $link->method->getLabel() }} — QR kodu gör">
                                            <span aria-hidden="true">{{ $link->method->icon() }}</span>{{ $link->method->getLabel() }} 🔳
                                        </a>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                                            <span aria-hidden="true">{{ $link->method->icon() }}</span>{{ $link->method->getLabel() }}@if ($link->detail) — {{ $link->detail }}@endif
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @include('partials.seller-listing-links', ['seller' => $listing->user, 'counts' => $sellerListingCounts])

                        <a href="{{ route('profiles.show', $listing->user->username) }}" class="mt-3 block text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">Profili ve değerlendirmeleri gör →</a>
                        {{-- Masaüstünde bu kart sol sütunda basılır (gerekçe orada). --}}
                        <div class="lg:hidden">
                            @include('partials.payment-safety-card', ['seller' => $listing->user])
                        </div>
                    </div>

                    {{-- Alan: sidebar alt (reklam/duyuru) --}}
                    <x-zone zone-key="ilan_detay_yan" />
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
