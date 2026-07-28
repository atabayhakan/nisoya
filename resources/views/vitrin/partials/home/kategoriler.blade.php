    {{-- Kategori şeridi --}}
    @if (\App\Support\HomeSections::visible('kategoriler') && $categories->isNotEmpty())
        @php
            // Handoff kategori renk döngüsü: mavi/mint/amber/coral/mor.
            $kategoriRenkleri = [
                ['bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400'],
                ['bg-[#e7f7f1] text-[#0f9d76] dark:bg-teal-950/60 dark:text-teal-300'],
                ['bg-[#fff6e8] text-[#b9741a] dark:bg-amber-950/60 dark:text-amber-300'],
                ['bg-[#fdeeeb] text-[#c2452f] dark:bg-rose-950/60 dark:text-rose-300'],
                ['bg-[#f1ecfe] text-[#8a6bf2] dark:bg-violet-950/60 dark:text-violet-300'],
            ];
        @endphp
        <section class="mx-auto max-w-6xl px-4" x-data x-reveal>
            <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($categories->take(10) as $category)
                    <a href="{{ route('listings.category', $category) }}"
                       class="group rounded-[18px] border border-stone-200/60 bg-white p-4 shadow-brand transition hover:-translate-y-0.5 hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                        <div class="flex items-center justify-between">
                            <span class="grid h-10 w-10 place-items-center rounded-xl {{ $kategoriRenkleri[$loop->index % 5][0] }}">
                                <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($category->icon)" class="h-5 w-5" />
                            </span>
                            <x-heroicon-o-arrow-right class="h-4 w-4 text-stone-300 transition group-hover:translate-x-0.5 group-hover:text-emerald-600 dark:text-stone-600 dark:group-hover:text-emerald-400" />
                        </div>
                        <div class="mt-3 text-base font-extrabold tracking-tight text-stone-800 dark:text-stone-100">{{ $category->name }}</div>
                    </a>
                @endforeach
            </div>
            <div class="mt-4 text-right">
                <a href="{{ url('/ilanlar') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Tüm kategoriler <x-heroicon-o-arrow-right class="h-4 w-4" /></a>
            </div>
        </section>
    @endif
