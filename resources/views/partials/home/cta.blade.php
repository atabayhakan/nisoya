    @if (\App\Support\HomeSections::visible('cta'))
    {{-- CTA --}}
    <section class="mx-auto max-w-6xl px-4 py-14" x-data x-reveal>
        <div class="rounded-3xl bg-emerald-700 px-6 py-12 text-center text-white sm:px-12 dark:bg-emerald-700">
            <h2 class="text-2xl font-bold sm:text-3xl">{{ setting('home.cta_baslik') }}</h2>
            <p class="mx-auto mt-3 max-w-xl text-emerald-50 dark:text-emerald-50">{{ setting('home.cta_metin') }}</p>
            <a href="{{ url('/kayit') }}" class="mt-6 inline-block rounded-xl bg-white px-6 py-3 font-semibold text-emerald-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-50 hover:shadow-xl dark:bg-stone-50 dark:hover:bg-stone-100 dark:text-emerald-700">{{ setting('home.cta_buton') }}</a>
        </div>
    </section>
    @endif
