<x-layouts.app>
    {{-- VİTRİN ANA SAYFA (P1) — klasik home'un aynı-ad override'ı. Aynı
         HomeController verisiyle çalışır (sıfır controller dokunuşu) ve
         bölüm göster/gizle için klasikle AYNI HomeSections anahtarlarını
         kullanır (admin tercihi iki temada da geçerli). Vurgu kartları
         (home_highlights) bilinçli P3 kapsamında — Hero Yöneticisi ile
         birlikte gelecek. --}}

    <x-vitrin.hero :countries="$countries" :stats="$stats" :latest-listings="$latestListings" :activity-feed="$activityFeed" />

    {{-- Alan: anasayfa üst (klasikle aynı zone anahtarı — reklam sözleşmesi) --}}
    <div class="mx-auto max-w-6xl px-4">
        <x-zone zone-key="anasayfa_ust" />
    </div>

    {{-- Kategori şeridi --}}
    @if (\App\Support\HomeSections::visible('kategoriler') && $categories->isNotEmpty())
        @php
            // Handoff kategori renk döngüsü: mavi/mint/amber/coral/mor.
            $kategoriRenkleri = [
                ['bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400'],
                ['bg-[#e7f7f1] text-[#0f9d76] dark:bg-teal-950/60 dark:text-teal-300'],
                ['bg-[#fff6e8] text-[#b9741a] dark:bg-amber-950/60 dark:text-amber-300'],
                ['bg-[#fdeeeb] text-[#c2452f] dark:bg-rose-950/60 dark:text-rose-300'],
                ['bg-[#f1ecfe] text-[#8a6bf2] dark:bg-violet-950/60 dark:text-violet-300'],
            ];
        @endphp
        <section class="mx-auto max-w-6xl px-4" x-data x-reveal>
            <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($categories->take(10) as $category)
                    <a href="{{ route('listings.category', $category) }}"
                       class="group rounded-[18px] border border-stone-200/60 bg-white p-4 shadow-brand transition hover:-translate-y-0.5 hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                        <div class="flex items-center justify-between">
                            <span class="grid h-10 w-10 place-items-center rounded-xl {{ $kategoriRenkleri[$loop->index % 5][0] }}">
                                <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($category->icon)" class="h-5 w-5" />
                            </span>
                            <x-heroicon-o-arrow-right class="h-4 w-4 text-stone-300 transition group-hover:translate-x-0.5 group-hover:text-emerald-600 dark:text-stone-600 dark:group-hover:text-emerald-400" />
                        </div>
                        <div class="mt-3 text-[15px] font-extrabold tracking-tight text-stone-800 dark:text-stone-100">{{ $category->name }}</div>
                    </a>
                @endforeach
            </div>
            <div class="mt-4 text-right">
                <a href="{{ url('/ilanlar') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Tüm kategoriler <x-heroicon-o-arrow-right class="h-4 w-4" /></a>
            </div>
        </section>
    @endif

    {{-- Öne çıkan / yeni ilanlar --}}
    @if (\App\Support\HomeSections::visible('yeni_ilanlar'))
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-stone-800 sm:text-3xl dark:text-stone-50">Öne çıkan ilanlar</h2>
                    <p class="mt-1.5 text-sm font-medium text-stone-500 dark:text-stone-400">Şehrindeki Türk esnaf ve satıcılardan en yeniler.</p>
                </div>
                <a href="{{ url('/ilanlar') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Tümünü gör <x-heroicon-o-arrow-right class="h-4 w-4" /></a>
            </div>

            @if ($latestListings->isEmpty())
                <div class="mt-6 rounded-[22px] border border-stone-200/60 bg-white p-10 text-center shadow-brand dark:border-stone-800 dark:bg-stone-900">
                    <p class="font-semibold text-stone-600 dark:text-stone-300">Henüz ilan yok — ilk ilanı sen ver!</p>
                    <a href="{{ url('/panel/ilan/yeni') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:text-stone-900">
                        <x-heroicon-o-plus class="h-4 w-4" />{{ setting('home.cta_buton') }}
                    </a>
                </div>
            @else
                @php
                    // Yatay büyük kart: ücretli "öne çıkan" varsa o; yoksa en yeni.
                    $vitrinOne = $latestListings->first(fn ($l) => $l->isCurrentlyFeatured()) ?? $latestListings->first();
                    $vitrinGrid = $latestListings->reject(fn ($l) => $l->is($vitrinOne))->take(6);
                @endphp

                <a href="{{ route('listings.show', [$vitrinOne, $vitrinOne->slug]) }}"
                   class="group mt-6 grid gap-4 rounded-[22px] border border-stone-200/60 bg-white p-3.5 shadow-brand-lg transition hover:border-emerald-300 md:grid-cols-[minmax(0,420px)_minmax(0,1fr)] dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                    <div class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-stone-100 md:aspect-auto md:min-h-[250px] dark:bg-stone-800">
                        @if ($vitrinOne->coverImage)
                            @php($vSrc = $vitrinOne->coverImage->srcset())
                            <img src="{{ $vSrc['medium'] ?? Storage::url($vitrinOne->coverImage->path) }}"
                                 srcset="{{ $vSrc['medium'] ?? '' }} 800w, {{ $vSrc['large'] ?? '' }} 1600w"
                                 sizes="(min-width: 768px) 420px, 100vw"
                                 alt="{{ $vitrinOne->title }}" loading="lazy" decoding="async"
                                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                 style="object-position: {{ $vitrinOne->coverImage->objectPosition() }}">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-stone-300 dark:text-stone-600">
                                <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($vitrinOne->category?->parent?->icon ?? $vitrinOne->category?->icon)" class="h-14 w-14" />
                            </div>
                        @endif
                        @if ($vitrinOne->isCurrentlyFeatured())
                            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-stone-800 px-2.5 py-1.5 text-[10.5px] font-bold uppercase tracking-wide text-white dark:bg-stone-950">
                                <x-heroicon-s-star class="h-3 w-3 text-amber-400" /> Öne çıkan
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-col p-2 md:p-3">
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            @if ($vitrinOne->category)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 font-bold text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">{{ $vitrinOne->category->name }}</span>
                            @endif
                            @if ($vitrinOne->is_remote)
                                <span class="rounded-full bg-[#e7f7f1] px-2.5 py-1 font-bold text-[#0f9d76] dark:bg-teal-950/60 dark:text-teal-300">Online</span>
                            @endif
                        </div>
                        <h3 class="mt-2.5 text-xl font-extrabold tracking-tight text-stone-800 group-hover:text-emerald-700 sm:text-2xl dark:text-stone-50 dark:group-hover:text-emerald-400">{{ $vitrinOne->title }}</h3>
                        @if ($vitrinOne->description)
                            <p class="mt-2 line-clamp-2 text-sm font-medium leading-relaxed text-stone-500 dark:text-stone-400">{{ $vitrinOne->description }}</p>
                        @endif
                        <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-4">
                            <div class="text-lg font-extrabold text-stone-800 dark:text-stone-50">
                                @if ($vitrinOne->price !== null)
                                    {{ number_format((float) $vitrinOne->price, 0) }} {{ $vitrinOne->currency }}<span class="text-xs font-semibold text-stone-400">{{ $vitrinOne->price_unit->suffix() }}</span>
                                @else
                                    <span class="text-emerald-600 dark:text-emerald-400">Görüşülür</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs font-medium text-stone-500 dark:text-stone-400">
                                <x-avatar :user="$vitrinOne->user" size="h-7 w-7" text="text-[10px]" />
                                <span class="max-w-[140px] truncate">{{ $vitrinOne->user->name }}</span>
                                @if ($vitrinOne->country)<span>· {{ $vitrinOne->country->emoji }} {{ $vitrinOne->city ?: $vitrinOne->country->name_tr }}</span>@endif
                            </div>
                        </div>
                    </div>
                </a>

                @if ($vitrinGrid->isNotEmpty())
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($vitrinGrid as $listing)
                            @include('partials.listing-card', ['listing' => $listing])
                        @endforeach
                    </div>
                @endif
            @endif
        </section>
    @endif

    {{-- Nisoya Nabzı — klasikle aynı veri, Vitrin yüzeyi --}}
    @if ($nabizGoal)
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <a href="{{ route('nabiz') }}" class="block rounded-[22px] border border-emerald-200 bg-emerald-50/60 p-6 transition hover:border-emerald-300 hover:shadow-brand sm:p-8 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:hover:border-emerald-700">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                            <span class="relative flex h-2 w-2" aria-hidden="true">
                                <span class="vitrin-pulse absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            Nisoya Nabzı
                        </span>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-stone-800 dark:text-stone-50">{{ $nabizGoal['baslik'] }}: {{ $nabizGoal['mevcut'] }} / {{ $nabizGoal['hedef'] }}</h2>
                        @if ($nabizGoal['odul'])
                            <p class="mt-1 text-sm font-medium text-stone-600 dark:text-stone-300">{{ $nabizGoal['odul'] }}</p>
                        @endif
                    </div>
                    <span class="text-sm font-bold text-emerald-700 hover:underline dark:text-emerald-400">Tümünü gör →</span>
                </div>
                <div class="mt-4 h-3 w-full overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                    <div class="h-full rounded-full bg-emerald-600 transition-all duration-700 dark:bg-emerald-500" style="width: {{ $nabizGoal['yuzde'] }}%"></div>
                </div>
                @if ($nabizAmbassadors->isNotEmpty())
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold text-stone-500 dark:text-stone-400">Bu ayın şehir elçileri:</span>
                        @foreach ($nabizAmbassadors as $ambassador)
                            <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-stone-700 shadow-sm dark:bg-stone-800 dark:text-stone-200">
                                🏅 {{ $ambassador->name }} · {{ $ambassador->city }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </a>
        </section>
    @endif

    {{-- Değer önerileri --}}
    @if (\App\Support\HomeSections::visible('deger_onerileri'))
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <div class="grid gap-3.5 sm:grid-cols-3">
                @foreach ([1, 2, 3] as $i)
                    <div class="rounded-[18px] border border-stone-200/60 bg-white p-5 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            @if ($i === 1)<x-heroicon-o-language class="h-5 w-5" />@elseif ($i === 2)<x-heroicon-o-shield-check class="h-5 w-5" />@else<x-heroicon-o-gift class="h-5 w-5" />@endif
                        </span>
                        <div class="mt-3 text-[15px] font-extrabold tracking-tight text-stone-800 dark:text-stone-100">{{ setting("home.deger{$i}_baslik") }}</div>
                        <p class="mt-1 text-sm font-medium text-stone-500 dark:text-stone-400">{{ setting("home.deger{$i}_metin") }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Ülkeler --}}
    @if (\App\Support\HomeSections::visible('ulkeler') && $countries->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <div class="rounded-[22px] border border-stone-200/60 bg-white p-6 shadow-brand sm:p-8 dark:border-stone-800 dark:bg-stone-900">
                <h2 class="text-xl font-extrabold tracking-tight text-stone-800 dark:text-stone-50">Nerede yaşıyorsan orada</h2>
                <p class="mt-1 text-sm font-medium text-stone-500 dark:text-stone-400">Ülkeni seç, şehrindeki Türkçe konuşan satıcıları gör.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($countries as $country)
                        <a href="{{ url('/ilanlar') }}?ulke={{ $country->code }}"
                           class="inline-flex items-center gap-1.5 rounded-full border border-stone-200 bg-stone-50 px-3 py-1.5 text-[13px] font-semibold text-stone-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                            {{ $country->emoji }} {{ $country->name_tr }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Nasıl çalışır --}}
    @if (\App\Support\HomeSections::visible('nasil_calisir'))
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <h2 class="text-2xl font-extrabold tracking-tight text-stone-800 sm:text-3xl dark:text-stone-50">{{ setting('home.nasil_baslik') }}</h2>
            <div class="mt-6 grid gap-3.5 md:grid-cols-3">
                @foreach ([1, 2, 3] as $i)
                    <div class="rounded-[18px] border border-stone-200/60 bg-white p-6 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                        <span class="text-3xl font-extrabold text-[#dbe3f7] dark:text-stone-700" aria-hidden="true">{{ str_pad((string) $i, 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="mt-2 text-[15px] font-extrabold tracking-tight text-stone-800 dark:text-stone-100">{{ setting("home.adim{$i}_baslik") }}</div>
                        <p class="mt-1.5 text-sm font-medium leading-relaxed text-stone-500 dark:text-stone-400">{{ setting("home.adim{$i}_metin") }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Satıcı bandı (CTA) --}}
    @if (\App\Support\HomeSections::visible('cta'))
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <div class="relative overflow-hidden rounded-[24px] bg-stone-800 p-8 text-white sm:p-10 dark:bg-stone-900 dark:ring-1 dark:ring-stone-800">
                <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[radial-gradient(circle,rgba(62,99,240,.35),transparent_70%)]" aria-hidden="true"></div>
                <div class="relative grid items-center gap-8 md:grid-cols-[minmax(0,1fr)_minmax(0,320px)]">
                    <div>
                        <span class="inline-flex rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-emerald-300">Satıcı ol</span>
                        <h2 class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl" style="text-wrap: pretty">{{ setting('home.cta_baslik') }}</h2>
                        <p class="mt-2 max-w-lg text-sm font-medium leading-relaxed text-white/70">{{ setting('home.cta_metin') }}</p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ url('/panel/ilan/yeni') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-stone-800 transition hover:bg-stone-100">
                                <x-heroicon-o-plus class="h-4 w-4" />{{ setting('home.cta_buton') }}
                            </a>
                            <a href="{{ url('/nasil-calisir') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/25 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">Nasıl çalışır?</a>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3" aria-hidden="false">
                        <div class="rounded-2xl bg-white/[.07] p-4"><div class="text-2xl font-extrabold">{{ $stats['countries'] }}</div><div class="mt-0.5 text-xs font-semibold text-white/60">ülke</div></div>
                        <div class="rounded-2xl bg-white/[.07] p-4"><div class="text-2xl font-extrabold">{{ $stats['cities'] }}</div><div class="mt-0.5 text-xs font-semibold text-white/60">şehir</div></div>
                        <div class="rounded-2xl bg-white/[.07] p-4"><div class="text-2xl font-extrabold">{{ $stats['categories'] }}</div><div class="mt-0.5 text-xs font-semibold text-white/60">kategori</div></div>
                        <div class="rounded-2xl bg-white/[.07] p-4"><div class="text-2xl font-extrabold text-emerald-300">%0</div><div class="mt-0.5 text-xs font-semibold text-white/60">komisyon</div></div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Alan: anasayfa alt (sitewide reklam/duyuru) --}}
    <div class="mx-auto max-w-6xl px-4 pt-14">
        <x-zone zone-key="anasayfa_alt" />
    </div>
</x-layouts.app>
