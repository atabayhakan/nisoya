    @if (\App\Support\HomeSections::visible('yeni_ilanlar'))
    {{-- Yeni ilanlar (Konteyner İçinde) --}}
    <section class="mx-auto max-w-6xl px-4 py-8 sm:py-12" x-data x-reveal>
        <div class="flex items-end justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Son İlanlar</span>
                <h2 class="mt-1 text-2xl font-bold text-stone-900 md:text-3xl dark:text-stone-50">Yeni İlanlar</h2>
            </div>
            <a href="{{ route('listings.index') }}" class="text-sm font-semibold text-emerald-700 transition hover:text-emerald-800 hover:underline dark:text-emerald-400 dark:hover:text-emerald-300">Tümünü gör →</a>
        </div>
        @if ($latestListings->isNotEmpty())
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($latestListings as $listing)
                    @include('partials.listing-card', ['listing' => $listing])
                @endforeach
            </div>
        @else
            <div class="mt-6 rounded-3xl border border-dashed border-emerald-300 bg-emerald-50/50 px-6 py-12 text-center dark:border-emerald-700 dark:bg-emerald-950/20">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900">
                    <x-heroicon-o-rocket-launch class="h-7 w-7" />
                </span>
                <h3 class="mt-4 text-xl font-bold text-stone-900 dark:text-stone-50">Burada ilk ilan senin olsun</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-stone-600 dark:text-stone-300">
                    Nisoya yeni açıldı. İlk ilanı vererek bulunduğun ülkedeki Türklere yeteneğini duyur.
                </p>
                <a href="{{ url('/panel/ilan/yeni') }}" class="mt-6 inline-block rounded-xl bg-emerald-700 px-6 py-3 font-semibold text-white transition hover:-translate-y-0.5 hover:bg-emerald-800 hover:shadow-brand dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">İlk ilanı sen ver</a>
            </div>
        @endif
    </section>
    @endif
