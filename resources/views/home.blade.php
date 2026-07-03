<x-layouts.app>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-emerald-50 to-stone-50 dark:from-emerald-950/30 dark:to-stone-950">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:py-24">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    {{ setting('home.hero_badge') }}
                </span>
                <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-stone-900 sm:text-5xl dark:text-stone-50">
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

    {{-- Değer önerileri + istatistik şeridi --}}
    <section class="border-y border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
        <div class="mx-auto max-w-6xl px-4 py-8">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <x-heroicon-o-language class="h-5 w-5" />
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">{{ setting('home.deger1_baslik') }}</h3>
                        <p class="mt-0.5 text-sm text-stone-500 dark:text-stone-400">{{ setting('home.deger1_metin') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <x-heroicon-o-shield-check class="h-5 w-5" />
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">{{ setting('home.deger2_baslik') }}</h3>
                        <p class="mt-0.5 text-sm text-stone-500 dark:text-stone-400">{{ setting('home.deger2_metin') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <x-heroicon-o-sparkles class="h-5 w-5" />
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">{{ setting('home.deger3_baslik') }}</h3>
                        <p class="mt-0.5 text-sm text-stone-500 dark:text-stone-400">{{ setting('home.deger3_metin') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <x-heroicon-o-globe-alt class="h-5 w-5" />
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">{{ $stats['countries'] }} ülke · {{ $stats['cities'] }} şehir</h3>
                        <p class="mt-0.5 text-sm text-stone-500 dark:text-stone-400">{{ $stats['categories'] }} kategoride hizmet ve ürün.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Kategoriler --}}
    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="flex items-end justify-between">
            <h2 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Kategoriler</h2>
            <a href="{{ route('listings.index') }}" class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">Tümünü gör →</a>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($categories as $cat)
                <a href="{{ route('listings.category', $cat->slug) }}"
                   class="group flex flex-col items-center gap-2 rounded-2xl border border-stone-200 bg-white p-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-brand dark:border-stone-800 dark:bg-stone-900 dark:shadow-none dark:hover:border-emerald-700">
                    <span class="text-3xl">{{ $cat->icon }}</span>
                    <span class="text-sm font-medium text-stone-700 group-hover:text-emerald-700 dark:text-stone-200 dark:group-hover:text-emerald-400">{{ $cat->name }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Popüler ülkeler --}}
    @if ($countries->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 pb-2">
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

        {{-- Reklam: Ülkeler listesi ile yeni ilanlar arasında --}}
        <div class="mx-auto max-w-6xl px-4 pt-6">
            <x-ad-slot slot="1111111111" format="horizontal" />
        </div>
    @endif

    {{-- Yeni ilanlar --}}
    <section class="mt-14 bg-white py-14 dark:bg-stone-900">
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

    {{-- Nasıl çalışır --}}
    <section class="bg-white py-14 dark:bg-stone-900">
        <div class="mx-auto max-w-6xl px-4">
            <h2 class="text-center text-2xl font-bold text-stone-900 dark:text-stone-50">{{ setting('home.nasil_baslik') }}</h2>
            <div class="mt-10 grid gap-8 md:grid-cols-3">
                @php
                    $adimlar = [
                        ['no' => '1', 'baslik' => setting('home.adim1_baslik'), 'metin' => setting('home.adim1_metin')],
                        ['no' => '2', 'baslik' => setting('home.adim2_baslik'), 'metin' => setting('home.adim2_metin')],
                        ['no' => '3', 'baslik' => setting('home.adim3_baslik'), 'metin' => setting('home.adim3_metin')],
                    ];
                @endphp
                @foreach ($adimlar as $a)
                    <div class="text-center">
                        <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-600 text-lg font-bold text-white dark:bg-emerald-500 dark:text-stone-900">{{ $a['no'] }}</div>
                        <h3 class="mt-4 text-lg font-semibold text-stone-900 dark:text-stone-100">{{ $a['baslik'] }}</h3>
                        <p class="mt-2 text-sm text-stone-600 dark:text-stone-300">{{ $a['metin'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="rounded-3xl bg-emerald-600 px-6 py-12 text-center text-white sm:px-12 dark:bg-emerald-700">
            <h2 class="text-2xl font-bold sm:text-3xl">{{ setting('home.cta_baslik') }}</h2>
            <p class="mx-auto mt-3 max-w-xl text-emerald-50 dark:text-emerald-50">{{ setting('home.cta_metin') }}</p>
            <a href="{{ url('/kayit') }}" class="mt-6 inline-block rounded-xl bg-white px-6 py-3 font-semibold text-emerald-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-50 hover:shadow-xl dark:bg-stone-50 dark:hover:bg-stone-100 dark:text-emerald-700">{{ setting('home.cta_buton') }}</a>
        </div>
    </section>
</x-layouts.app>
