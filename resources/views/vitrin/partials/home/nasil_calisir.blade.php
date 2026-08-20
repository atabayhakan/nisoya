    {{-- Nasıl çalışır --}}
    @if (\App\Support\HomeSections::visible('nasil_calisir'))
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <h2 class="text-2xl font-extrabold text-stone-800 sm:text-3xl dark:text-stone-50">{{ setting('home.nasil_baslik') }}</h2>
            <div class="mt-6 grid gap-3.5 md:grid-cols-3">
                @foreach ([1, 2, 3] as $i)
                    <div class="rounded-2xl border border-stone-200/60 bg-white p-6 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                        <span class="text-3xl font-extrabold text-[#dbe3f7] dark:text-stone-700" aria-hidden="true">{{ str_pad((string) $i, 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="mt-2 text-base font-extrabold text-stone-800 dark:text-stone-100">{{ setting("home.adim{$i}_baslik") }}</div>
                        <p class="mt-1.5 text-sm font-medium leading-relaxed text-stone-500 dark:text-stone-400">{{ setting("home.adim{$i}_metin") }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
