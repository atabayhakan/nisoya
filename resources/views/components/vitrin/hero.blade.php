@props(['countries', 'stats', 'latestListings', 'activityFeed'])

{{-- VİTRİN HERO (P1) — handoff 01/2: sol tipografi + arama paneli + etiket
     çipleri + sayaç satırı; sağda (lg+) yüzen bento kartları. Tüm sayılar
     GERÇEK veridir (stats/aktivite/ilan) — prototipteki temsili rakamlar
     bilinçli olarak KULLANILMAZ. Metinler admin panelinden (home.hero_*).
     P3'te Hero Yöneticisi bu bileşeni hero_settings ile yönetecek. --}}
@php
    $populerEtiketler = Illuminate\Support\Str::of(setting('home.populer_metin', ''))
        ->after(':')
        ->explode('·')
        ->map(fn ($s) => trim($s))
        ->filter()
        ->take(4);
    $oneCikanIlan = $latestListings->first();
    $barlar = [46, 63, 39, 72, 88, 55, 67];
    $gunler = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
@endphp

<section class="relative overflow-hidden">
    {{-- Dekoratif radial (saf CSS, görsel dosyası yok) --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -left-32 -top-40 h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle,rgba(62,99,240,.13),transparent_68%)] dark:bg-[radial-gradient(circle,rgba(107,138,253,.12),transparent_68%)]"></div>
        <div class="absolute -right-24 top-1/3 h-80 w-80 rounded-full bg-[radial-gradient(circle,rgba(22,169,127,.08),transparent_70%)]"></div>
    </div>

    <div class="relative z-10 mx-auto grid max-w-6xl items-center gap-10 px-4 py-12 lg:grid-cols-[minmax(0,505px)_minmax(0,1fr)] lg:py-14">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full border border-stone-200 bg-white px-3.5 py-2 text-xs font-semibold text-emerald-600 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:text-emerald-400">
                <span class="relative inline-flex h-2 w-2" aria-hidden="true">
                    <span class="vitrin-pulse absolute inset-0 rounded-full bg-[#16a97f]"></span>
                    <span class="absolute inset-0 rounded-full bg-[#16a97f]"></span>
                </span>
                {{ setting('home.hero_badge') }}
            </span>

            <h1 class="mt-5 text-4xl font-extrabold tracking-[-0.032em] text-stone-800 sm:text-5xl dark:text-stone-50" style="text-wrap: pretty">
                {{ setting('home.hero_satir1') }}<br>
                <span class="text-emerald-600 dark:text-emerald-400">{{ setting('home.hero_vurgu') }}</span> {{ setting('home.hero_satir2') }}
            </h1>

            <p class="mt-4 max-w-md text-base font-medium leading-relaxed text-stone-600 dark:text-stone-300" style="text-wrap: pretty">
                {{ setting('home.hero_aciklama') }}
            </p>

            {{-- Arama paneli — klasikle aynı form sözleşmesi (GET /ilanlar, q + ulke) --}}
            <form action="{{ url('/ilanlar') }}" method="GET" class="mt-7 flex flex-col gap-1.5 rounded-2xl border border-stone-200/70 bg-white p-2 shadow-brand sm:flex-row sm:items-center dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
                <div class="flex min-w-0 flex-1 items-center gap-2 px-3">
                    <x-heroicon-o-magnifying-glass class="h-4 w-4 shrink-0 text-stone-400" />
                    <input type="text" name="q" placeholder="{{ setting('home.arama_placeholder') }}"
                           class="h-12 w-full min-w-0 border-0 bg-transparent p-0 text-[15px] font-medium text-stone-800 placeholder-stone-400 focus:outline-none focus:ring-0 dark:text-stone-100 dark:placeholder-stone-500">
                </div>
                <select name="ulke" aria-label="Ülke seç" class="h-12 rounded-xl border-0 bg-stone-100 px-3 text-sm font-semibold text-stone-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-stone-800 dark:text-stone-200">
                    <option value="">Tüm ülkeler</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->code }}">{{ $country->emoji }} {{ $country->name_tr }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 text-sm font-bold text-white shadow-[0_12px_22px_-12px_rgba(62,99,240,1)] transition hover:bg-emerald-700 dark:bg-emerald-500 dark:text-stone-900 dark:shadow-none dark:hover:bg-emerald-400">
                    Ara
                    <x-heroicon-o-arrow-right class="h-4 w-4" />
                </button>
            </form>

            @if ($populerEtiketler->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($populerEtiketler as $etiket)
                        <a href="{{ url('/ilanlar') }}?q={{ urlencode($etiket) }}"
                           class="inline-flex items-center gap-1.5 rounded-full border border-stone-200 bg-white px-3 py-1.5 text-xs font-semibold transition hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700 dark:hover:text-emerald-400 {{ $loop->first ? 'text-emerald-600 dark:text-emerald-400' : 'text-stone-600 dark:text-stone-300' }}">
                            @if ($loop->first)
                                <x-heroicon-o-arrow-trending-up class="h-3.5 w-3.5 text-[#16a97f]" />
                            @endif
                            {{ $etiket }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Sayaç satırı — gerçek platform istatistikleri --}}
            <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-3">
                @if ($latestListings->isNotEmpty())
                    <div class="flex" aria-hidden="true">
                        @foreach ($latestListings->take(4) as $ilan)
                            <span class="{{ $loop->first ? '' : '-ml-2.5' }} inline-block rounded-full border-2 border-stone-50 dark:border-stone-950">
                                <x-avatar :user="$ilan->user" size="h-8 w-8" text="text-[10px]" />
                            </span>
                        @endforeach
                    </div>
                @endif
                <div class="flex items-baseline gap-1.5">
                    <span class="text-lg font-extrabold text-stone-800 dark:text-stone-50">{{ $stats['countries'] }}</span>
                    <span class="text-xs font-medium text-stone-500 dark:text-stone-400">ülke</span>
                </div>
                <div class="h-5 w-px bg-stone-300/60 dark:bg-stone-700" aria-hidden="true"></div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-lg font-extrabold text-stone-800 dark:text-stone-50">{{ $stats['categories'] }}</span>
                    <span class="text-xs font-medium text-stone-500 dark:text-stone-400">kategori</span>
                </div>
                <div class="h-5 w-px bg-stone-300/60 dark:bg-stone-700" aria-hidden="true"></div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-lg font-extrabold text-[#16a97f]">{{ $stats['cities'] }}</span>
                    <span class="text-xs font-medium text-stone-500 dark:text-stone-400">şehir</span>
                </div>
            </div>
        </div>

        {{-- Sağ bento — yalnız lg+ (mobilde hero + arama ilk ekranda kalmalı) --}}
        <div class="relative hidden min-h-[460px] lg:block" x-data x-reveal>
            <div class="absolute inset-0 rounded-[26px] border border-stone-200/70 bg-gradient-to-br from-emerald-100/60 via-stone-100 to-emerald-50/40 dark:border-stone-800 dark:from-emerald-950/40 dark:via-stone-900 dark:to-stone-950" aria-hidden="true"></div>

            <div class="relative grid grid-cols-2 gap-3.5 p-5">
                {{-- (a) Dekoratif haftalık ritim çubukları — eksen/rakam iddiası yok --}}
                <div class="col-span-2 rounded-[18px] border border-stone-200/60 bg-white p-4 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-bold text-stone-800 dark:text-stone-100">Topluluk her gün büyüyor</div>
                            <div class="mt-0.5 text-xs font-medium text-stone-500 dark:text-stone-400">Son 7 gün · tüm kategoriler</div>
                        </div>
                        <span class="rounded-full bg-[#e7f7f1] px-2 py-1 text-[11px] font-bold text-[#0f9d76] dark:bg-emerald-950/60 dark:text-emerald-400">canlı</span>
                    </div>
                    <div class="mt-4 flex items-end gap-3" aria-hidden="true">
                        @foreach ($barlar as $i => $yuzde)
                            <div class="flex flex-1 flex-col items-center gap-2">
                                <div class="relative h-[74px] w-2 rounded-full bg-stone-100 dark:bg-stone-800">
                                    <div class="vitrin-bar absolute inset-x-0 bottom-0 rounded-full {{ $i === 4 ? 'bg-gradient-to-b from-[#4fd2a4] to-[#16a97f]' : 'bg-gradient-to-b from-emerald-400 to-emerald-600' }}"
                                         style="height: {{ $yuzde }}%; animation-delay: {{ 50 + $i * 60 }}ms"></div>
                                </div>
                                <span class="text-[10px] font-semibold {{ $i === 4 ? 'text-[#16a97f]' : 'text-stone-400 dark:text-stone-500' }}">{{ $gunler[$i] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- (b) Canlı akış — gerçek son ilanlar (activityTicker yeniden kullanımı) --}}
                @if (\App\Support\HomeSections::visible('canli_akis') && $activityFeed->isNotEmpty())
                    <div x-data="activityTicker({{ $activityFeed->count() }})" class="relative min-h-[132px] overflow-hidden rounded-[18px] border border-stone-200/60 bg-white p-4 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">
                            <span class="relative inline-flex h-1.5 w-1.5" aria-hidden="true"><span class="vitrin-pulse absolute inset-0 rounded-full bg-emerald-500"></span><span class="absolute inset-0 rounded-full bg-emerald-500"></span></span>
                            Canlı akış
                        </div>
                        @foreach ($activityFeed as $i => $item)
                            <a href="{{ $item['href'] }}" x-show="index === {{ $i }}" x-transition.opacity.duration.400ms class="absolute inset-x-4 bottom-4 top-10 flex flex-col justify-center" @if ($i > 0) x-cloak @endif>
                                <span class="line-clamp-2 text-sm font-semibold text-stone-800 dark:text-stone-100">{{ $item['firstName'] }} yeni bir ilan paylaştı</span>
                                <span class="mt-1 text-xs font-medium text-stone-500 dark:text-stone-400">{{ $item['categoryName'] }} @if ($item['place'])· {{ $item['flag'] }} {{ $item['place'] }}@endif · {{ $item['timeAgo'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- (c) Yüzen mini ilan kartı — gerçek son ilan --}}
                @if ($oneCikanIlan)
                    <a href="{{ route('listings.show', [$oneCikanIlan, $oneCikanIlan->slug]) }}" class="vitrin-float block rounded-[18px] border border-stone-200/60 bg-white p-3 shadow-brand-lg transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700 {{ \App\Support\HomeSections::visible('canli_akis') && $activityFeed->isNotEmpty() ? '' : 'col-span-2' }}">
                        <div class="relative h-24 overflow-hidden rounded-xl bg-stone-100 dark:bg-stone-800">
                            @if ($oneCikanIlan->coverImage)
                                <img src="{{ $oneCikanIlan->coverImage->srcset()['thumb'] ?? Storage::url($oneCikanIlan->coverImage->path) }}"
                                     alt="{{ $oneCikanIlan->title }}" loading="lazy" decoding="async"
                                     class="h-full w-full object-cover" style="object-position: {{ $oneCikanIlan->coverImage->objectPosition() }}">
                            @else
                                <div class="flex h-full items-center justify-center text-stone-300 dark:text-stone-600">
                                    <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($oneCikanIlan->category?->parent?->icon ?? $oneCikanIlan->category?->icon)" class="h-8 w-8" />
                                </div>
                            @endif
                            @if ($oneCikanIlan->category)
                                <span class="absolute left-2 top-2 rounded-full bg-white/95 px-2 py-1 text-[10px] font-bold text-emerald-600 dark:bg-stone-900/95 dark:text-emerald-400">{{ $oneCikanIlan->category->name }}</span>
                            @endif
                        </div>
                        <div class="mt-2.5 line-clamp-1 text-[13px] font-bold text-stone-800 dark:text-stone-100">{{ $oneCikanIlan->title }}</div>
                        <div class="mt-0.5 text-[11.5px] font-medium text-stone-500 dark:text-stone-400">
                            @if ($oneCikanIlan->country){{ $oneCikanIlan->country->emoji }} {{ $oneCikanIlan->city ?: $oneCikanIlan->country->name_tr }}@endif
                        </div>
                        <div class="mt-1.5 text-base font-extrabold text-emerald-600 dark:text-emerald-400">
                            @if ($oneCikanIlan->price !== null)
                                {{ number_format((float) $oneCikanIlan->price, 0) }} {{ $oneCikanIlan->currency }}<span class="text-[11px] font-semibold text-stone-400">{{ $oneCikanIlan->price_unit->suffix() }}</span>
                            @else
                                Görüşülür
                            @endif
                        </div>
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
