{{--
    Rehber — temsilcilik sayfası (/de/koeln). Temsilcilik künyesi +
    yayındaki işlem rehberlerinin listesi.
--}}
<x-layouts.app
    :title="$temsilcilik->ad.' İşlem Rehberi — '.setting('genel.site_adi')"
    :description="$temsilcilik->ad.' için vekaletname, pasaport, askerlik gibi işlemlerin evrak listeleri, süreleri ve resmî kaynakları.'"
>
    <div class="mx-auto max-w-4xl px-4 py-12">
        <nav class="text-sm text-stone-500 dark:text-stone-400" aria-label="breadcrumb">
            <a href="{{ route('home') }}" class="hover:underline">Ana sayfa</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('rehber.ulke', strtolower($country->code)) }}" class="hover:underline">{{ $country->name_tr }} Rehberi</a>
            <span aria-hidden="true"> / </span>
            <span class="text-stone-700 dark:text-stone-200">{{ $temsilcilik->sehir }}</span>
        </nav>

        <p class="mt-4 text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-400">{{ $temsilcilik->turEtiketi() }}</p>
        <h1 class="mt-1 text-3xl font-bold text-stone-900 dark:text-stone-50">{{ $temsilcilik->ad }}</h1>

        <div class="mt-4 space-y-1 text-sm text-stone-600 dark:text-stone-300">
            @if ($temsilcilik->adres)
                <p>{{ $temsilcilik->adres }}</p>
            @endif
            @if ($temsilcilik->resmi_url)
                <p>
                    <a href="{{ $temsilcilik->resmi_url }}" target="_blank" rel="noopener nofollow"
                        class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">
                        Resmî site ↗
                    </a>
                </p>
            @endif
        </div>

        <h2 class="mt-10 text-xl font-semibold text-stone-900 dark:text-stone-50">İşlem rehberleri</h2>

        @if ($islemler->isEmpty())
            <div class="mt-4 rounded-2xl border border-stone-200 bg-stone-50 p-6 dark:border-stone-700 dark:bg-stone-800/50">
                <p class="text-stone-600 dark:text-stone-300">Bu temsilcilik için işlem rehberleri hazırlanıyor.</p>
            </div>
        @else
            <ul class="mt-4 divide-y divide-stone-200 overflow-hidden rounded-2xl border border-stone-200 bg-white dark:divide-stone-700 dark:border-stone-700 dark:bg-stone-800/50">
                @foreach ($islemler as $islem)
                    <li>
                        <a href="{{ route('rehber.islem', [strtolower($country->code), $temsilcilik->slug, $islem->islemTuru->slug]) }}"
                            class="group flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-stone-50 dark:hover:bg-stone-800">
                            <span>
                                <span class="font-medium text-stone-900 group-hover:underline dark:text-stone-50">{{ $islem->islemTuru->ad }}</span>
                                @if ($islem->islemTuru->aciklama)
                                    <span class="mt-0.5 block text-sm text-stone-500 dark:text-stone-400">{{ $islem->islemTuru->aciklama }}</span>
                                @endif
                            </span>
                            <span class="shrink-0 text-stone-600 dark:text-stone-400" aria-hidden="true">→</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <p class="mt-10 rounded-xl bg-stone-100 p-4 text-xs leading-relaxed text-stone-500 dark:bg-stone-800 dark:text-stone-400">
            Bu sayfalar bilgilendirme amaçlıdır; evrak listeleri, ücretler ve süreler değişebilir.
            İşleme gitmeden önce lütfen temsilciliğin resmî sayfasından teyit edin.
        </p>
    </div>

    <x-json-ld type="BreadcrumbList" :data="[
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana sayfa', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $country->name_tr.' Rehberi', 'item' => route('rehber.ulke', strtolower($country->code))],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $temsilcilik->ad, 'item' => route('rehber.temsilcilik', [strtolower($country->code), $temsilcilik->slug])],
        ],
    ]" />
</x-layouts.app>
