{{--
    Yaşam Rehberi — bir kategorideki konu listesi (/de/yasam/bankacilik-finans).
--}}
<x-layouts.app
    :title="$kategori->ad.' — '.$country->name_tr.' Yaşam Rehberi — '.setting('genel.site_adi')"
    :description="$country->name_tr.'\'da '.$kategori->ad.' konusunda pratik, Türkçe rehber sayfaları.'"
>
    <div class="mx-auto max-w-3xl px-4 py-12">
        <nav class="text-sm text-stone-500 dark:text-stone-400" aria-label="breadcrumb">
            <a href="{{ route('home') }}" class="hover:underline">Ana sayfa</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('yasam-rehberi.kategoriler', strtolower($country->code)) }}" class="hover:underline">{{ $country->name_tr }} Yaşam Rehberi</a>
            <span aria-hidden="true"> / </span>
            <span class="text-stone-700 dark:text-stone-200">{{ $kategori->ad }}</span>
        </nav>

        <h1 class="mt-4 text-3xl font-bold text-stone-900 dark:text-stone-50">{{ $kategori->ad }}</h1>
        <p class="mt-2 text-stone-600 dark:text-stone-300">{{ $country->name_tr }} için</p>

        <ul class="mt-8 space-y-3">
            @foreach ($konular as $konu)
                <li>
                    <a href="{{ route('yasam-rehberi.icerik', [strtolower($country->code), $kategori->slug, $konu->slug]) }}"
                        class="block rounded-xl border border-stone-200 bg-white p-4 transition hover:border-emerald-300 hover:shadow dark:border-stone-700 dark:bg-stone-800/50 dark:hover:border-emerald-700">
                        <span class="font-medium text-stone-900 dark:text-stone-50">{{ $konu->baslik }}</span>
                        @if ($konu->kisa_aciklama)
                            <span class="mt-1 block text-sm text-stone-500 dark:text-stone-400">{{ $konu->kisa_aciklama }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <x-json-ld type="BreadcrumbList" :data="[
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana sayfa', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $country->name_tr.' Yaşam Rehberi', 'item' => route('yasam-rehberi.kategoriler', strtolower($country->code))],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $kategori->ad, 'item' => route('yasam-rehberi.konular', [strtolower($country->code), $kategori->slug])],
        ],
    ]" />
</x-layouts.app>
