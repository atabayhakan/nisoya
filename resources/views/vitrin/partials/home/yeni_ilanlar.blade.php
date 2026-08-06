    {{-- Öne çıkan / yeni ilanlar --}}
    @if (\App\Support\HomeSections::visible('yeni_ilanlar'))
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-stone-800 sm:text-3xl dark:text-stone-50">Öne çıkan ilanlar</h2>
                    <p class="mt-1.5 text-sm font-medium text-stone-500 dark:text-stone-400">Şehrindeki Türk esnaf ve satıcılardan en yeniler.</p>
                </div>
                <a href="{{ url('/ilanlar') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-emerald-700 hover:text-emerald-700 dark:text-emerald-400">Tümünü gör <x-heroicon-o-arrow-right class="h-4 w-4" /></a>
            </div>

            @if ($latestListings->isEmpty())
                <div class="mt-6 rounded-[22px] border border-stone-200/60 bg-white p-10 text-center shadow-brand dark:border-stone-800 dark:bg-stone-900">
                    <p class="font-semibold text-stone-600 dark:text-stone-300">Henüz ilan yok — ilk ilanı sen ver!</p>
                    <a href="{{ url('/panel/ilan/yeni') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900">
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
                            @php $vSrc = $vitrinOne->coverImage->srcset(); @endphp
                            <img src="{{ $vitrinOne->coverImage->enIyiUrl('medium') }}"
                                 srcset="{{ $vSrc['medium'] ?? '' }} 800w, {{ $vSrc['large'] ?? '' }} 1600w"
                                 sizes="(min-width: 768px) 420px, 100vw"
                                 alt="{{ $vitrinOne->title }}" loading="lazy" decoding="async"
                                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                 style="object-position: {{ $vitrinOne->coverImage->objectPosition() }}">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-stone-300 dark:text-stone-400">
                                <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($vitrinOne->category?->parent?->icon ?? $vitrinOne->category?->icon)" class="h-14 w-14" />
                            </div>
                        @endif
                        @if ($vitrinOne->isCurrentlyFeatured())
                            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-stone-800 px-2.5 py-1.5 text-2xs font-bold uppercase tracking-wide text-white dark:bg-stone-950">
                                <x-heroicon-s-star class="h-3 w-3 text-amber-400" /> Öne çıkan
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-col p-2 md:p-3">
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            @if ($vitrinOne->category)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">{{ $vitrinOne->category->name }}</span>
                            @endif
                            @if ($vitrinOne->is_remote)
                                <span class="rounded-full bg-[#e7f7f1] px-2.5 py-1 font-bold text-[#0f9d76] dark:bg-teal-950/60 dark:text-teal-300">Online</span>
                            @endif
                        </div>
                        <h3 class="mt-2.5 text-xl font-extrabold text-stone-800 group-hover:text-emerald-700 sm:text-2xl dark:text-stone-50 dark:group-hover:text-emerald-400">{{ $vitrinOne->title }}</h3>
                        @if ($vitrinOne->description)
                            <p class="mt-2 line-clamp-2 text-sm font-medium leading-relaxed text-stone-500 dark:text-stone-400">{{ $vitrinOne->description }}</p>
                        @endif
                        <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-4">
                            <div class="text-lg font-extrabold text-stone-800 dark:text-stone-50">
                                @if ($vitrinOne->price !== null)
                                    {{ $vitrinOne->bicimliFiyat() }} {{ $vitrinOne->currency }}<span class="text-xs font-semibold text-stone-600">{{ $vitrinOne->price_unit->suffix() }}</span>
                                @else
                                    <span class="text-emerald-700 dark:text-emerald-400">Görüşülür</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs font-medium text-stone-500 dark:text-stone-400">
                                <x-avatar :user="$vitrinOne->user" size="h-7 w-7" text="text-2xs" />
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
