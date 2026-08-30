    {{-- Öne çıkan / yeni ilanlar (Vitrin Teması — Çerçeveli Bento Düzeni) --}}
    @if (\App\Support\HomeSections::visible('yeni_ilanlar'))
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <div class="rounded-3xl border border-stone-200/90 bg-white p-6 sm:p-10 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-6">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300">
                            ✨ Güncel Pazar Yeri
                        </span>
                        <h2 class="mt-2 text-xl sm:text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                            Öne çıkan ilanlar
                        </h2>
                        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                            Şehrindeki Türk esnaf ve satıcılardan en yeniler.
                        </p>
                    </div>
                    <a href="{{ url('/ilanlar') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-xs font-semibold text-stone-700 transition hover:border-emerald-300 hover:bg-stone-100 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-600 dark:hover:text-emerald-300">
                        <span>Tümünü gör</span>
                        <x-heroicon-o-arrow-right class="h-3.5 w-3.5" />
                    </a>
                </div>

                @if ($latestListings->isEmpty())
                    <div class="rounded-2xl border border-stone-100 bg-stone-50/50 p-10 text-center dark:border-stone-800 dark:bg-stone-800/40">
                        <p class="font-semibold text-stone-600 dark:text-stone-300">Henüz ilan yok — ilk ilanı sen ver!</p>
                        <a href="{{ url('/panel/ilan/yeni') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900">
                            <x-heroicon-o-plus class="h-4 w-4" />{{ setting('home.cta_buton') }}
                        </a>
                    </div>
                @else
                    @php
                        // Yatay büyük kart: ücretli "öne çıkan" varsa o; yoksa en yeni.
                        $vitrinOne = $latestListings->first(fn ($l) => $l->isCurrentlyFeatured()) ?? $latestListings->first();
                        $vitrinGrid = $latestListings->reject(fn ($l) => $l->is($vitrinOne))->take(6);
                    @endphp

                    {{-- Büyük Öne Çıkan Hero İlan Kartı --}}
                    <a href="{{ route('listings.show', [$vitrinOne, $vitrinOne->slug]) }}"
                       class="group grid gap-6 rounded-2xl border border-stone-200/80 bg-stone-50/60 p-4 sm:p-5 shadow-2xs transition hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-white hover:shadow-md md:grid-cols-[minmax(0,380px)_minmax(0,1fr)] dark:border-stone-800 dark:bg-stone-800/40 dark:hover:border-emerald-700 dark:hover:bg-stone-800">
                        <div class="relative aspect-[16/10] overflow-hidden rounded-xl bg-stone-100 md:aspect-auto md:min-h-[240px] dark:bg-stone-800">
                            @if ($vitrinOne->coverImage)
                                @php $vSrc = $vitrinOne->coverImage->srcset(); @endphp
                                <img src="{{ $vitrinOne->coverImage->enIyiUrl('medium') }}"
                                     srcset="{{ $vSrc['medium'] ?? '' }} 800w, {{ $vSrc['large'] ?? '' }} 1600w"
                                     sizes="(min-width: 768px) 380px, 100vw"
                                     alt="{{ $vitrinOne->title }}" loading="lazy" decoding="async"
                                     class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                     style="object-position: {{ $vitrinOne->coverImage->objectPosition() }}">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-stone-300 dark:text-stone-400">
                                    <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($vitrinOne->category?->parent?->icon ?? $vitrinOne->category?->icon)" class="h-14 w-14" />
                                </div>
                            @endif
                            @if ($vitrinOne->isCurrentlyFeatured())
                                <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-stone-900/90 px-2.5 py-1 text-[11px] font-bold text-white shadow-xs backdrop-blur-xs dark:bg-stone-950/90">
                                    <x-heroicon-s-star class="h-3 w-3 text-amber-400" /> Öne çıkan
                                </span>
                            @endif
                        </div>
                        <div class="flex flex-col justify-between py-1">
                            <div>
                                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                    @if ($vitrinOne->category)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300">{{ $vitrinOne->category->name }}</span>
                                    @endif
                                    @if ($vitrinOne->is_remote)
                                        <x-chip tone="teal">Online</x-chip>
                                    @endif
                                </div>
                                <h3 class="mt-2.5 text-lg sm:text-xl font-bold text-stone-900 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-300">{{ $vitrinOne->title }}</h3>
                                @if ($vitrinOne->description)
                                    <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-stone-500 dark:text-stone-400">{{ $vitrinOne->description }}</p>
                                @endif
                            </div>
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-stone-200/60 pt-3 dark:border-stone-700/60">
                                <div class="text-lg font-extrabold text-stone-900 dark:text-stone-50">
                                    @if ($vitrinOne->price !== null)
                                        {{ $vitrinOne->bicimliFiyat() }} {{ $vitrinOne->currency }}<span class="text-xs font-medium text-stone-500 dark:text-stone-400">{{ $vitrinOne->price_unit->suffix() }}</span>
                                    @else
                                        <span class="text-emerald-700 dark:text-emerald-400">Görüşülür</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-xs font-medium text-stone-500 dark:text-stone-400">
                                    <x-avatar :user="$vitrinOne->user" size="h-6 w-6" text="text-2xs" />
                                    <span class="max-w-[140px] truncate font-semibold text-stone-700 dark:text-stone-300">{{ $vitrinOne->user->name }}</span>
                                    @if ($vitrinOne->country)<span>· {{ $vitrinOne->country->emoji }} {{ $vitrinOne->city ?: $vitrinOne->country->name_tr }}</span>@endif
                                </div>
                            </div>
                        </div>
                    </a>

                    {{-- İlan Kartları Izgarası --}}
                    @if ($vitrinGrid->isNotEmpty())
                        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($vitrinGrid as $listing)
                                @include('partials.listing-card', ['listing' => $listing])
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </section>
    @endif
