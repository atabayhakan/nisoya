<x-layouts.app>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-emerald-50 to-stone-50 dark:from-emerald-950/30 dark:to-stone-950">
        {{-- Soyut arka plan dekorasyonu (saf CSS, görsel dosyası yok) --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-emerald-300/30 blur-3xl dark:bg-emerald-700/20"></div>
            <div class="absolute -right-16 top-1/4 h-80 w-80 rounded-full bg-amber-200/30 blur-3xl dark:bg-amber-900/10"></div>
            <div class="absolute -bottom-16 left-1/3 h-64 w-64 rounded-full bg-emerald-200/40 blur-3xl dark:bg-emerald-800/10"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-6xl px-4 py-16 sm:py-24">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    {{ setting('home.hero_badge') }}
                </span>
                {{-- "2. Tasarım" modunda (bkz. /yonetim Tasarım Modu) başlık Instrument
                     Serif italik ile — 2027 vizyon pilotunun tek somut tipografi izi. --}}
                <h1 class="mt-5 text-4xl tracking-tight text-stone-900 sm:text-5xl md:text-6xl dark:text-stone-50 {{ setting('gorunum.tasarim_modu', 'eski') === 'yeni' ? 'font-serif italic font-normal' : 'font-extrabold' }}">
                    {{ setting('home.hero_satir1') }}<br>
                    <span class="text-emerald-600 dark:text-emerald-400">{{ setting('home.hero_vurgu') }}</span> {{ setting('home.hero_satir2') }}
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg text-stone-600 dark:text-stone-300">
                    {{ setting('home.hero_aciklama') }}
                </p>

                {{-- Arama kutusu --}}
                <form action="{{ url('/ilanlar') }}" method="GET" class="mx-auto mt-8 flex max-w-2xl flex-col gap-2 rounded-2xl bg-white p-2 shadow-lg ring-1 ring-stone-200 sm:flex-row dark:bg-stone-900 dark:ring-stone-800">
                    <input type="text" name="q" placeholder="{{ setting('home.arama_placeholder') }}"
                           class="flex-1 rounded-xl border-0 bg-transparent px-4 py-3 text-stone-800 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:text-stone-100 dark:placeholder-stone-500">
                    <select name="ulke" class="rounded-xl border-0 bg-stone-50 px-4 py-3 text-stone-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-stone-800 dark:text-stone-200">
                        <option value="">Tüm ülkeler</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->code }}">{{ $country->emoji }} {{ $country->name_tr }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                        Ara
                    </button>
                </form>
                <p class="mt-3 text-sm text-stone-400 dark:text-stone-500">{{ setting('home.populer_metin') }}</p>
            </div>
        </div>
    </section>

    {{-- Canlı Akış: son ilanlar arasında geçiş yapan şerit --}}
    @if (\App\Support\HomeSections::visible('canli_akis') && $activityFeed->isNotEmpty())
        <div
            x-data="activityTicker({{ $activityFeed->count() }})"
            class="border-y border-stone-200 bg-white/80 backdrop-blur dark:border-stone-800 dark:bg-stone-900/80"
        >
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-2.5">
                <span class="flex shrink-0 items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    Canlı
                </span>
                <div class="relative h-5 flex-1 overflow-hidden">
                    @foreach ($activityFeed as $i => $item)
                        <a
                            href="{{ $item['href'] }}"
                            x-show="index === {{ $i }}"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="absolute inset-0 flex items-center gap-2 truncate text-sm text-stone-600 hover:text-emerald-700 dark:text-stone-300 dark:hover:text-emerald-400"
                            @if ($i > 0) style="display: none" @endif
                        >
                            <x-dynamic-component :component="'heroicon-o-'.$item['categoryIcon']" class="h-4 w-4 shrink-0 text-emerald-500" />
                            <span class="truncate">
                                <strong class="font-semibold text-stone-800 dark:text-stone-100">{{ $item['firstName'] }}</strong>
                                yeni bir ilan paylaştı ·
                                <span class="text-stone-500 dark:text-stone-400">{{ $item['categoryName'] }}</span>
                                @if ($item['place'])
                                    · {{ $item['flag'] }} {{ $item['place'] }}
                                @endif
                                · {{ $item['timeAgo'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Nisoya Nabzı: topluluk hedefi + şehir elçileri (admin panelden açık/kapalı) --}}
    @if ($nabizGoal)
        <section class="border-b border-stone-200 bg-white py-10 dark:border-stone-800 dark:bg-stone-900" x-data x-reveal>
            <div class="mx-auto max-w-6xl px-4">
                <a href="{{ route('nabiz') }}" class="block rounded-3xl border border-emerald-200 bg-emerald-50/50 p-6 transition hover:border-emerald-300 hover:shadow-brand dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:hover:border-emerald-700 sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                </span>
                                Nisoya Nabzı
                            </span>
                            <h2 class="mt-1 text-xl font-bold text-stone-900 dark:text-stone-50">{{ $nabizGoal['baslik'] }}: {{ $nabizGoal['mevcut'] }} / {{ $nabizGoal['hedef'] }}</h2>
                            @if ($nabizGoal['odul'])
                                <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">{{ $nabizGoal['odul'] }}</p>
                            @endif
                        </div>
                        <span class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">Tümünü gör →</span>
                    </div>

                    <div class="mt-4 h-3 w-full overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                        <div class="h-full rounded-full bg-emerald-600 transition-all duration-700 dark:bg-emerald-500" style="width: {{ $nabizGoal['yuzde'] }}%"></div>
                    </div>

                    @if ($nabizAmbassadors->isNotEmpty())
                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            <span class="text-xs font-medium text-stone-500 dark:text-stone-400">Bu ayın şehir elçileri:</span>
                            @foreach ($nabizAmbassadors as $ambassador)
                                <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-stone-700 shadow-sm dark:bg-stone-800 dark:text-stone-200">
                                    🏅 {{ $ambassador->name }} · {{ $ambassador->city }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </a>
            </div>
        </section>
    @endif

    @if (\App\Support\HomeSections::visible('deger_onerileri'))
    {{-- Değer önerileri + istatistik şeridi (bento grid) --}}
    <section class="border-y border-stone-200 bg-stone-50 dark:border-stone-800 dark:bg-stone-950">
        <div class="mx-auto max-w-6xl px-4 py-14">
            <div class="grid gap-4 lg:grid-cols-4 lg:grid-rows-2">
                {{-- Öne çıkan büyük kutu — otomatik dönen vurgu mesajları (bkz.
                     App\Models\HomeHighlight, admin: Site Yönetimi → Ana Sayfa —
                     Büyük Kart). activityTicker "Canlı Akış" şeridiyle aynı Alpine
                     bileşeni + geçiş deseni yeniden kullanılıyor; tek mesaj varken
                     (count<2) otomatik olarak sabit kalır. min-h: içerik mutlak
                     konumlandığı için (kartlar üst üste biner) ebeveynin doğal
                     yüksekliğe düşmesini engeller. --}}
                <div
                    @if ($bigHighlights->count()) x-data="activityTicker({{ $bigHighlights->count() }})" @endif
                    class="relative min-h-56 overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 to-emerald-700 text-white shadow-brand-lg lg:col-span-2 lg:row-span-2 dark:from-emerald-700 dark:to-emerald-800"
                >
                    @forelse ($bigHighlights as $i => $highlight)
                        <div
                            class="absolute inset-0 p-6 lg:p-8"
                            x-show="index === {{ $i }}"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            @if ($i > 0) style="display: none" @endif
                        >
                            @if ($highlight->hasMedia())
                                @php $media = $highlight->media; @endphp
                                <div
                                    class="relative h-32 w-full overflow-hidden rounded-2xl bg-black/10 lg:h-40"
                                    @if (count($media) > 1) x-data="activityTicker({{ count($media) }})" @endif
                                >
                                    @foreach ($media as $mi => $item)
                                        <div
                                            class="absolute inset-0"
                                            @if (count($media) > 1)
                                                x-show="index === {{ $mi }}"
                                                x-transition:enter="transition ease-out duration-500"
                                                x-transition:enter-start="opacity-0"
                                                x-transition:enter-end="opacity-100"
                                                x-transition:leave="transition ease-in duration-300"
                                                x-transition:leave-start="opacity-100"
                                                x-transition:leave-end="opacity-0"
                                                @if ($mi > 0) style="display: none" @endif
                                            @endif
                                        >
                                            @include('partials.highlight-media', ['item' => $item])
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/15">
                                    <x-dynamic-component :component="'heroicon-o-'.$highlight->heroicon()" class="h-6 w-6" />
                                </span>
                            @endif
                            <h3 class="mt-6 text-2xl font-bold">{{ $highlight->title }}</h3>
                            <p class="mt-2 max-w-xs text-emerald-50">{{ $highlight->text }}</p>
                        </div>
                    @empty
                        <div class="p-6 lg:p-8">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/15">
                                <x-heroicon-o-language class="h-6 w-6" />
                            </span>
                            <h3 class="mt-6 text-2xl font-bold">Tamamen Türkçe</h3>
                            <p class="mt-2 max-w-xs text-emerald-50">Kendi dilinde, kendi insanınla.</p>
                        </div>
                    @endforelse
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm lg:col-span-2 dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <x-heroicon-o-shield-check class="h-5 w-5" />
                    </span>
                    <h3 class="mt-4 font-semibold text-stone-900 dark:text-stone-100">{{ setting('home.deger2_baslik') }}</h3>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ setting('home.deger2_metin') }}</p>
                </div>

                {{-- Küçük kart — aynı desen, bkz. App\Models\HomeHighlight
                     (admin: Site Yönetimi → Ana Sayfa — Küçük Kart). --}}
                <div
                    @if ($smallHighlights->count()) x-data="activityTicker({{ $smallHighlights->count() }})" @endif
                    class="relative min-h-36 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none"
                >
                    @forelse ($smallHighlights as $i => $highlight)
                        <div
                            class="absolute inset-0 p-6"
                            x-show="index === {{ $i }}"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            @if ($i > 0) style="display: none" @endif
                        >
                            @if ($highlight->hasMedia())
                                @php $media = $highlight->media; @endphp
                                <div
                                    class="relative h-20 w-full overflow-hidden rounded-xl bg-stone-100 dark:bg-stone-800"
                                    @if (count($media) > 1) x-data="activityTicker({{ count($media) }})" @endif
                                >
                                    @foreach ($media as $mi => $item)
                                        <div
                                            class="absolute inset-0"
                                            @if (count($media) > 1)
                                                x-show="index === {{ $mi }}"
                                                x-transition:enter="transition ease-out duration-500"
                                                x-transition:enter-start="opacity-0"
                                                x-transition:enter-end="opacity-100"
                                                x-transition:leave="transition ease-in duration-300"
                                                x-transition:leave-start="opacity-100"
                                                x-transition:leave-end="opacity-0"
                                                @if ($mi > 0) style="display: none" @endif
                                            @endif
                                        >
                                            @include('partials.highlight-media', ['item' => $item])
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    <x-dynamic-component :component="'heroicon-o-'.$highlight->heroicon()" class="h-5 w-5" />
                                </span>
                            @endif
                            <h3 class="mt-4 font-semibold text-stone-900 dark:text-stone-100">{{ $highlight->title }}</h3>
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $highlight->text }}</p>
                        </div>
                    @empty
                        <div class="p-6">
                            <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                                <x-heroicon-o-sparkles class="h-5 w-5" />
                            </span>
                            <h3 class="mt-4 font-semibold text-stone-900 dark:text-stone-100">Ücretsiz ilan</h3>
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">İlan vermek tamamen ücretsiz.</p>
                        </div>
                    @endforelse
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <x-heroicon-o-globe-alt class="h-5 w-5" />
                    </span>
                    <h3 class="mt-4 font-semibold text-stone-900 dark:text-stone-100">{{ $stats['countries'] }} ülke · {{ $stats['cities'] }} şehir</h3>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $stats['categories'] }} kategoride hizmet ve ürün.</p>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Faz İ2 ("2. Tasarım" pilotu): istatistik kartındaki "22 ülke" metnini
         gerçek veriyle gösteren Nabız Haritası. Sadece yeni tasarım modunda —
         bkz. /yonetim Tasarım Modu. --}}
    @if (setting('gorunum.tasarim_modu', 'eski') === 'yeni')
        <section class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-950">
            <div class="mx-auto max-w-6xl px-4 py-14">
                <x-pulse-map :countries="$pulseCountries" />
            </div>
        </section>
    @endif

    @if (\App\Support\HomeSections::visible('kategoriler'))
    {{-- Kategoriler --}}
    <section class="mx-auto max-w-6xl px-4 py-14" x-data x-reveal>
        <div class="flex items-end justify-between">
            <h2 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Kategoriler</h2>
            <a href="{{ route('listings.index') }}" class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">Tümünü gör →</a>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($categories as $cat)
                <a href="{{ route('listings.category', $cat->slug) }}"
                   class="group flex flex-col items-center gap-2 rounded-2xl border border-stone-200 bg-white p-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-brand dark:border-stone-800 dark:bg-stone-900 dark:shadow-none dark:hover:border-emerald-700">
                    <span class="grid h-12 w-12 place-items-center rounded-xl bg-emerald-50 text-emerald-600 transition group-hover:bg-emerald-600 group-hover:text-white dark:bg-emerald-900/40 dark:text-emerald-300">
                        <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($cat->icon)" class="h-6 w-6" />
                    </span>
                    <span class="text-sm font-medium text-stone-700 group-hover:text-emerald-700 dark:text-stone-200 dark:group-hover:text-emerald-400">{{ $cat->name }}</span>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Popüler ülkeler --}}
    @if (\App\Support\HomeSections::visible('ulkeler') && $countries->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 pb-2" x-data x-reveal>
            <h2 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Ülkeler</h2>
            <div class="mt-5 flex flex-wrap gap-2">
                @foreach ($countries as $country)
                    <a href="{{ url('/ilanlar') }}?ulke={{ $country->code }}"
                       class="inline-flex items-center gap-1.5 rounded-full border border-stone-200 bg-white px-4 py-2 text-sm font-medium text-stone-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:text-emerald-700 hover:shadow-brand dark:border-stone-800 dark:bg-stone-900 dark:text-stone-200 dark:shadow-none dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                        <span>{{ $country->emoji }}</span>
                        <span>{{ $country->name_tr }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Alan: Ülkeler listesi ile yeni ilanlar arasında (reklam/duyuru) --}}
    <div class="mx-auto max-w-6xl px-4 pt-6">
        <x-zone zone-key="anasayfa_ust" />
    </div>

    @if (\App\Support\HomeSections::visible('yeni_ilanlar'))
    {{-- Yeni ilanlar --}}
    <section class="mt-14 bg-white py-14 dark:bg-stone-900" x-data x-reveal>
        <div class="mx-auto max-w-6xl px-4">
            <div class="flex items-end justify-between">
                <h2 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Yeni ilanlar</h2>
                <a href="{{ route('listings.index') }}" class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">Tümünü gör →</a>
            </div>
            @if ($latestListings->isNotEmpty())
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($latestListings as $listing)
                        @include('partials.listing-card', ['listing' => $listing])
                    @endforeach
                </div>
            @else
                <div class="mt-6 rounded-3xl border border-dashed border-emerald-300 bg-emerald-50/50 px-6 py-14 text-center dark:border-emerald-700 dark:bg-emerald-950/20">
                    <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-600 text-white dark:bg-emerald-500 dark:text-stone-900">
                        <x-heroicon-o-rocket-launch class="h-7 w-7" />
                    </span>
                    <h3 class="mt-4 text-xl font-bold text-stone-900 dark:text-stone-50">Burada ilk ilan senin olsun</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-stone-600 dark:text-stone-300">
                        Nisoya yeni açıldı. İlk ilanı vererek bulunduğun ülkedeki Türklere yeteneğini duyur.
                    </p>
                    <a href="{{ url('/panel/ilan/yeni') }}" class="mt-6 inline-block rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-brand dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">İlk ilanı sen ver</a>
                </div>
            @endif
        </div>
    </section>
    @endif

    @if (\App\Support\HomeSections::visible('nasil_calisir'))
    {{-- Nasıl çalışır --}}
    <section class="bg-white py-14 dark:bg-stone-900" x-data x-reveal>
        <div class="mx-auto max-w-6xl px-4">
            <h2 class="text-center text-2xl font-bold text-stone-900 dark:text-stone-50">{{ setting('home.nasil_baslik') }}</h2>
            <div class="mt-10 grid gap-8 md:grid-cols-3">
                @php
                    $adimlar = [
                        ['no' => '1', 'ikon' => 'user-plus', 'baslik' => setting('home.adim1_baslik'), 'metin' => setting('home.adim1_metin')],
                        ['no' => '2', 'ikon' => 'megaphone', 'baslik' => setting('home.adim2_baslik'), 'metin' => setting('home.adim2_metin')],
                        ['no' => '3', 'ikon' => 'chat-bubble-left-right', 'baslik' => setting('home.adim3_baslik'), 'metin' => setting('home.adim3_metin')],
                    ];
                @endphp
                @foreach ($adimlar as $a)
                    <div class="text-center">
                        <div class="relative mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-600 text-white dark:bg-emerald-500 dark:text-stone-900">
                            <x-dynamic-component :component="'heroicon-o-'.$a['ikon']" class="h-6 w-6" />
                            <span class="absolute -right-2 -top-2 grid h-6 w-6 place-items-center rounded-full bg-white text-xs font-bold text-emerald-700 ring-2 ring-emerald-600 dark:bg-stone-900 dark:text-emerald-400 dark:ring-emerald-500">{{ $a['no'] }}</span>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-stone-900 dark:text-stone-100">{{ $a['baslik'] }}</h3>
                        <p class="mt-2 text-sm text-stone-600 dark:text-stone-300">{{ $a['metin'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @if (\App\Support\HomeSections::visible('cta'))
    {{-- CTA --}}
    <section class="mx-auto max-w-6xl px-4 py-14" x-data x-reveal>
        <div class="rounded-3xl bg-emerald-600 px-6 py-12 text-center text-white sm:px-12 dark:bg-emerald-700">
            <h2 class="text-2xl font-bold sm:text-3xl">{{ setting('home.cta_baslik') }}</h2>
            <p class="mx-auto mt-3 max-w-xl text-emerald-50 dark:text-emerald-50">{{ setting('home.cta_metin') }}</p>
            <a href="{{ url('/kayit') }}" class="mt-6 inline-block rounded-xl bg-white px-6 py-3 font-semibold text-emerald-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-50 hover:shadow-xl dark:bg-stone-50 dark:hover:bg-stone-100 dark:text-emerald-700">{{ setting('home.cta_buton') }}</a>
        </div>
    </section>
    @endif

    {{-- Alan: sayfa sonu (reklam/duyuru) --}}
    <div class="mx-auto max-w-6xl px-4 pb-6">
        <x-zone zone-key="anasayfa_alt" />
    </div>
</x-layouts.app>
