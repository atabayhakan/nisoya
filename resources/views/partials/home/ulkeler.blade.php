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
