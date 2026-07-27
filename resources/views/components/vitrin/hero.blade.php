@props(['countries', 'stats', 'latestListings', 'activityFeed'])

{{-- VİTRİN HERO (P1 → P3) — artık Hero Yöneticisi'nden yönetilir
     (App\Support\Hero / Filament: İçerik & Tasarım → Hero Yöneticisi):
     düzen varyantı (bento|sahne), metinler, butonlar, arka plan
     (görsel/video + karartma + odak) ve blokların sırası/açıklığı.
     Boş bırakılan metinler klasik `home.hero_*` değerlerine düşer, yani
     panel hiç açılmadan da doğru çalışır.

     Tüm sayılar GERÇEK veridir (stats/aktivite/ilan) — prototipteki temsili
     rakamlar bilinçli olarak KULLANILMAZ. Kapalı bloklar DOM'a hiç basılmaz. --}}
@php
    $hero = \App\Support\Hero::class;
    $duzen = $hero::duzen();
    $bloklar = $hero::aktifBloklar();
    $arkaplanTipi = $hero::arkaplanTipi();
    $gorsel = $arkaplanTipi === 'gorsel' ? $hero::arkaplanGorseli() : null;
    $gorselMobil = $arkaplanTipi === 'gorsel' ? $hero::arkaplanGorseli(true) : null;
    $video = $arkaplanTipi === 'video' ? $hero::videoUrl() : null;
    $medyaVar = $gorsel !== null || $video !== null;
    // "Sahne" düzeni metni medyanın üstüne koyar → yalnız medya varken koyu zemin.
    $sahne = $duzen === 'sahne';
    $koyu = $sahne && $medyaVar;
    $overlay = $hero::overlay();
    $cta1 = $hero::cta(1);
    $cta2 = $hero::cta(2);
    $oneCikanIlan = $latestListings->first();
    $barlar = [46, 63, 39, 72, 88, 55, 67];
    $gunler = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
@endphp

<section class="relative overflow-hidden {{ $koyu ? 'bg-stone-900' : '' }}">
    @if ($medyaVar)
        {{-- Arka plan medyası (Hero Yöneticisi) --}}
        <div class="absolute inset-0" aria-hidden="true">
            @if ($gorsel)
                <picture>
                    @if ($gorselMobil && $gorselMobil !== $gorsel)
                        <source media="(max-width: 639px)" srcset="{{ $gorselMobil }}">
                    @endif
                    <img src="{{ $gorsel }}" alt="" width="2400" height="1200" fetchpriority="high"
                         class="h-full w-full object-cover" style="object-position: {{ $hero::odakCss() }}">
                </picture>
            @else
                <video class="h-full w-full object-cover" autoplay muted loop playsinline preload="metadata">
                    <source src="{{ $video }}" type="video/mp4">
                </video>
            @endif
            @if ($overlay > 0)
                <div class="absolute inset-0 bg-stone-950" style="opacity: {{ $overlay / 100 }}"></div>
            @endif
        </div>
    @else
        {{-- Dekoratif radial (saf CSS, görsel dosyası yok) --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-32 -top-40 h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle,rgba(62,99,240,.13),transparent_68%)] dark:bg-[radial-gradient(circle,rgba(107,138,253,.12),transparent_68%)]"></div>
            <div class="absolute -right-24 top-1/3 h-80 w-80 rounded-full bg-[radial-gradient(circle,rgba(22,169,127,.08),transparent_70%)]"></div>
        </div>
    @endif

    <div class="relative z-10 mx-auto max-w-6xl px-4 py-12 lg:py-14 {{ $sahne ? '' : 'grid items-center gap-10 lg:grid-cols-[minmax(0,505px)_minmax(0,1fr)]' }}">
        <div class="{{ $sahne ? 'mx-auto max-w-2xl text-center' : '' }}">
            @if ($hero::rozet())
                <span class="inline-flex items-center gap-2 rounded-full border px-3.5 py-2 text-xs font-semibold shadow-sm {{ $koyu
                    ? 'border-white/25 bg-white/10 text-white'
                    : 'border-stone-200 bg-white text-emerald-600 dark:border-stone-800 dark:bg-stone-900 dark:text-emerald-400' }}">
                    <span class="relative inline-flex h-2 w-2" aria-hidden="true">
                        <span class="vitrin-pulse absolute inset-0 rounded-full bg-[#16a97f]"></span>
                        <span class="absolute inset-0 rounded-full bg-[#16a97f]"></span>
                    </span>
                    {{ $hero::rozet() }}
                </span>
            @endif

            <h1 class="mt-5 text-4xl font-extrabold tracking-[-0.032em] sm:text-5xl {{ $koyu ? 'text-white' : 'text-stone-800 dark:text-stone-50' }}" style="text-wrap: pretty">
                {{ $hero::baslik() }}
                @if ($hero::vurgu())
                    <br><span class="{{ $koyu ? 'text-emerald-300' : 'text-emerald-600 dark:text-emerald-400' }}">{{ $hero::vurgu() }}</span>
                @endif
            </h1>

            @if ($hero::altBaslik())
                <p class="mt-4 text-base font-medium leading-relaxed {{ $koyu ? 'mx-auto max-w-xl text-white/80' : 'max-w-md text-stone-600 dark:text-stone-300' }}" style="text-wrap: pretty">
                    {{ $hero::altBaslik() }}
                </p>
            @endif

            @foreach ($bloklar as $blokAnahtari)
                <x-vitrin.hero-blok
                    :anahtar="$blokAnahtari"
                    :countries="$countries"
                    :stats="$stats"
                    :latest-listings="$latestListings"
                    :koyu="$koyu"
                />
            @endforeach

            @if ($cta1 || $cta2)
                <div class="mt-6 flex flex-wrap gap-3 {{ $sahne ? 'justify-center' : '' }}">
                    @if ($cta1)
                        <a href="{{ $cta1['url'] }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-[0_12px_22px_-12px_rgba(62,99,240,1)] transition hover:brightness-95 dark:bg-emerald-500 dark:text-stone-900 dark:shadow-none">
                            {{ $cta1['etiket'] }}
                        </a>
                    @endif
                    @if ($cta2)
                        <a href="{{ $cta2['url'] }}" class="inline-flex items-center gap-2 rounded-xl border px-5 py-3 text-sm font-bold transition {{ $koyu
                            ? 'border-white/30 text-white hover:bg-white/10'
                            : 'border-stone-300 text-stone-800 hover:bg-stone-100 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800' }}">
                            {{ $cta2['etiket'] }}
                        </a>
                    @endif
                </div>
            @endif
        </div>

        {{-- Bento düzeninin sağ sütunu: yüzen canlı veri kartları (yalnız lg+;
             mobilde hero + arama ilk ekranda kalmalı). "Sahne" düzeninde
             sayfa tek kolon olduğu için hiç basılmaz. --}}
        @unless ($sahne)
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
        @endunless
    </div>
</section>
