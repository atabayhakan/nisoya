{{--
    Yaşam Rehberi — kategori listesi (/de/yasam). Ülke Rehberi'nin ulke.blade.php
    ile aynı kabuk/stil; yalnız o ülkede yayında içeriği OLAN kategoriler
    listelenir (controller'da whereHas ile filtrelendi) — boş kategori linki
    verip "hazırlanıyor" yalanı söylemiyoruz.
--}}
<x-layouts.app
    :title="$country->name_tr.' için Yaşam Rehberi — '.setting('genel.site_adi')"
    :description="$country->name_tr.'\'da bankacılık, barınma, sağlık, iş ve daha fazlası için pratik, Türkçe rehber.'"
>
    <div class="mx-auto max-w-4xl px-4 py-12">
        <nav class="text-sm text-stone-500 dark:text-stone-400" aria-label="breadcrumb">
            <a href="{{ route('home') }}" class="hover:underline">Ana sayfa</a>
            <span aria-hidden="true"> / </span>
            <span class="text-stone-700 dark:text-stone-200">{{ $country->name_tr }} Yaşam Rehberi</span>
        </nav>

        <h1 class="mt-4 text-3xl font-bold text-stone-900 dark:text-stone-50">
            <span aria-hidden="true">{{ $country->emoji }}</span>
            {{ $country->name_tr }} — Yaşam Rehberi
        </h1>
        <p class="mt-3 max-w-2xl text-stone-600 dark:text-stone-300">
            Bankacılıktan barınmaya, sağlık sigortasından okula — {{ $country->name_tr }}'da gündelik
            hayatı kolaylaştıran pratik bilgiler, Türkçe.
        </p>

        @if ($kategoriler->isEmpty())
            <div class="mt-10 rounded-2xl border border-stone-200 bg-stone-50 p-8 text-center dark:border-stone-700 dark:bg-stone-800/50">
                <p class="font-medium text-stone-700 dark:text-stone-200">Bu ülke için Yaşam Rehberi henüz hazırlanıyor.</p>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($kategoriler as $kategori)
                    <a href="{{ route('yasam-rehberi.konular', [strtolower($country->code), $kategori->slug]) }}"
                        class="group rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow dark:border-stone-700 dark:bg-stone-800/50 dark:hover:border-emerald-700">
                        @if ($kategori->ikon)
                            <span class="text-2xl" aria-hidden="true">{{ $kategori->ikon }}</span>
                        @endif
                        <h2 class="mt-1 text-lg font-semibold text-stone-900 group-hover:underline dark:text-stone-50">{{ $kategori->ad }}</h2>
                        <p class="mt-3 text-sm font-medium text-stone-700 dark:text-stone-300">{{ $kategori->yayinda_konu_sayisi }} konu</p>
                    </a>
                @endforeach
            </div>
        @endif

        <p class="mt-10 rounded-xl bg-stone-100 p-4 text-xs leading-relaxed text-stone-500 dark:bg-stone-800 dark:text-stone-400">
            Bu sayfalar bilgilendirme amaçlıdır ve zamanla değişebilir. Önemli kararlardan önce
            lütfen resmî/güncel kaynaktan teyit edin.
        </p>
    </div>

    <x-json-ld type="BreadcrumbList" :data="[
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana sayfa', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $country->name_tr.' Yaşam Rehberi', 'item' => route('yasam-rehberi.kategoriler', strtolower($country->code))],
        ],
    ]" />
</x-layouts.app>
