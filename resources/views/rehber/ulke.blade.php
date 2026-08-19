{{--
    Rehber — ülke sayfası (/de). Ülkedeki Türk dış temsilciliklerini listeler.
    Tema-nötr: klasik ağaçta tek kopya, Vitrin layout override'ı kabuğu verir.
--}}
<x-layouts.app
    :title="$country->name_tr.' Türk Konsolosluk Rehberi — '.setting('genel.site_adi')"
    :description="$country->name_tr.'\'daki Türk büyükelçiliği ve başkonsolosluklarında vekaletname, pasaport, askerlik gibi işlemler için evrak listeleri, süreler ve resmî kaynaklar.'"
>
    <div class="mx-auto max-w-4xl px-4 py-12">
        <nav class="text-sm text-stone-500 dark:text-stone-400" aria-label="breadcrumb">
            <a href="{{ route('home') }}" class="hover:underline">Ana sayfa</a>
            <span aria-hidden="true"> / </span>
            <span class="text-stone-700 dark:text-stone-200">{{ $country->name_tr }} Rehberi</span>
        </nav>

        <h1 class="mt-4 text-3xl font-bold text-stone-900 dark:text-stone-50">
            <span aria-hidden="true">{{ $country->emoji }}</span>
            {{ $country->name_tr }} — Konsolosluk &amp; Resmî İşlem Rehberi
        </h1>
        <p class="mt-3 max-w-2xl text-stone-600 dark:text-stone-300">
            {{ $country->name_tr }}'daki Türk temsilciliklerinde hangi işlem için hangi evrak gerekiyor,
            ne kadar sürüyor, ne kadar tutuyor — Türkçe, tek yerde.
        </p>

        @if ($temsilcilikler->isEmpty())
            <div class="mt-10 rounded-2xl border border-stone-200 bg-stone-50 p-8 text-center dark:border-stone-700 dark:bg-stone-800/50">
                <p class="font-medium text-stone-700 dark:text-stone-200">Bu ülkenin rehberi henüz hazırlanıyor.</p>
                <x-rehber.ulke-secici :aktif="$country" baslik="Şu an hazır olan ülkeler:" class="mt-4 justify-center" />
            </div>
        @else
            <x-rehber.ulke-secici :aktif="$country" class="mt-6" />

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($temsilcilikler as $t)
                    <a href="{{ route('rehber.temsilcilik', [strtolower($country->code), $t->slug]) }}"
                        class="group rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow dark:border-stone-700 dark:bg-stone-800/50 dark:hover:border-emerald-700">
                        <p class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-400">{{ $t->turEtiketi() }}</p>
                        <h2 class="mt-1 text-lg font-semibold text-stone-900 group-hover:underline dark:text-stone-50">{{ $t->ad }}</h2>
                        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $t->sehir }}</p>
                        @if ($t->yayinda_islem_sayisi > 0)
                            <p class="mt-3 text-sm font-medium text-stone-700 dark:text-stone-300">{{ $t->yayinda_islem_sayisi }} işlem rehberi</p>
                        @else
                            <p class="mt-3 text-sm text-stone-600 dark:text-stone-400">İçerik hazırlanıyor</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        <p class="mt-10 rounded-xl bg-stone-100 p-4 text-xs leading-relaxed text-stone-500 dark:bg-stone-800 dark:text-stone-400">
            Bu sayfalar bilgilendirme amaçlıdır; evrak listeleri, ücretler ve süreler değişebilir.
            İşleme gitmeden önce lütfen ilgili temsilciliğin resmî sayfasından teyit edin.
        </p>
    </div>

    <x-json-ld type="BreadcrumbList" :data="[
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana sayfa', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $country->name_tr.' Rehberi', 'item' => route('rehber.ulke', strtolower($country->code))],
        ],
    ]" />
</x-layouts.app>
