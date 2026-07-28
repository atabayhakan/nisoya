    @if (\App\Support\HomeSections::visible('kategoriler'))
    {{-- Kategoriler --}}
    <section class="mx-auto max-w-6xl px-4 py-14" x-data x-reveal>
        <div class="flex items-end justify-between">
            <h2 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Kategoriler</h2>
            <a href="{{ route('listings.index') }}" class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">Tümünü gör →</a>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($categories as $cat)
                <a href="{{ route('listings.category', $cat->slug) }}"
                   class="group flex flex-col items-center gap-2 rounded-2xl border border-stone-200 bg-white p-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-brand dark:border-stone-800 dark:bg-stone-900 dark:shadow-none dark:hover:border-emerald-700">
                    <span class="grid h-12 w-12 place-items-center rounded-xl bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-800 group-hover:text-white dark:bg-emerald-900/40 dark:text-emerald-300">
                        <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($cat->icon)" class="h-6 w-6" />
                    </span>
                    <span class="text-sm font-medium text-stone-700 group-hover:text-emerald-700 dark:text-stone-200 dark:group-hover:text-emerald-400">{{ $cat->name }}</span>
                </a>
            @endforeach
        </div>
    </section>
    @endif
