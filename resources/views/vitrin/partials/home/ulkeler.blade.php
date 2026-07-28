    {{-- Ülkeler --}}
    @if (\App\Support\HomeSections::visible('ulkeler') && $countries->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <div class="rounded-[22px] border border-stone-200/60 bg-white p-6 shadow-brand sm:p-8 dark:border-stone-800 dark:bg-stone-900">
                <h2 class="text-xl font-extrabold tracking-tight text-stone-800 dark:text-stone-50">Nerede yaşıyorsan orada</h2>
                <p class="mt-1 text-sm font-medium text-stone-500 dark:text-stone-400">Ülkeni seç, şehrindeki Türkçe konuşan satıcıları gör.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($countries as $country)
                        <a href="{{ url('/ilanlar') }}?ulke={{ $country->code }}"
                           class="inline-flex items-center gap-1.5 rounded-full border border-stone-200 bg-stone-50 px-3 py-1.5 text-sm font-semibold text-stone-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                            {{ $country->emoji }} {{ $country->name_tr }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
