@if (\App\Support\HomeSections::visible('kategoriler'))
    @php
        $oneCikanlar = $categories->take(8);
        $digerKategoriler = $categories->skip(8);
    @endphp

    <section class="mx-auto max-w-6xl px-4 py-10 sm:py-14" x-data="{ tumunuGoster: false }" x-reveal>
        <div class="flex items-end justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Keşfet & Bul</span>
                <h2 class="mt-1 text-2xl font-bold text-stone-900 md:text-3xl dark:text-stone-50">Popüler Kategoriler</h2>
            </div>
            <a href="{{ route('listings.index') }}" class="text-sm font-semibold text-emerald-700 transition hover:text-emerald-800 hover:underline dark:text-emerald-400 dark:hover:text-emerald-300">Tümünü gör →</a>
        </div>

        {{-- Öne Çıkan Ana Kategori Kartları --}}
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @if (\App\Support\Modules::enabled('hali_saha'))
                <a href="{{ route('football.index') }}"
                   class="group relative flex items-center gap-3.5 rounded-2xl border border-emerald-300/80 bg-gradient-to-br from-emerald-50/90 to-white p-4 shadow-sm transition hover:-translate-y-1 hover:border-emerald-500 hover:shadow-md dark:border-emerald-800/80 dark:from-emerald-950/40 dark:to-stone-900">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-emerald-100/80 text-xl shadow-2xs dark:bg-emerald-900/60">⚽</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-bold text-emerald-950 group-hover:text-emerald-700 dark:text-emerald-100 dark:group-hover:text-emerald-300 truncate">Halı Saha & Spor</span>
                            <span class="rounded-md bg-emerald-700 px-1.5 py-0.5 text-3xs font-extrabold text-white uppercase tracking-wider dark:bg-emerald-500 dark:text-stone-950">Yeni</span>
                        </div>
                        <p class="mt-0.5 text-2xs text-stone-500 dark:text-stone-400 truncate">Takım, maç ve oyuncular</p>
                    </div>
                </a>
            @endif

            @foreach ($oneCikanlar as $cat)
                <a href="{{ route('listings.category', $cat->slug) }}"
                   class="group flex items-center gap-3.5 rounded-2xl border border-stone-200/90 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:border-emerald-400 hover:shadow-md dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-stone-100/90 text-stone-700 transition group-hover:bg-emerald-50 group-hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-300 dark:group-hover:bg-emerald-950/60 dark:group-hover:text-emerald-400">
                        <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($cat->icon)" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <span class="text-sm font-bold text-stone-900 transition group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-400 truncate block">
                            {{ $cat->name }}
                        </span>
                        <p class="mt-0.5 text-2xs text-stone-500 dark:text-stone-400 truncate">
                            İlanları ve hizmetleri keşfet
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Diğer Kategoriler (Genişletilebilir Akıllı Şerit) --}}
        @if ($digerKategoriler->isNotEmpty())
            <div class="mt-5">
                <button
                    type="button"
                    @click="tumunuGoster = !tumunuGoster"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:text-emerald-800 transition dark:text-emerald-400"
                >
                    <span x-text="tumunuGoster ? 'Daha az kategori göster ↑' : 'Tüm kategorileri keşfet (' + {{ $categories->count() }} + ') ↓'"></span>
                </button>

                <div x-show="tumunuGoster" x-collapse x-cloak class="mt-4 flex flex-wrap gap-2 pt-3 border-t border-stone-200/60 dark:border-stone-800/60">
                    @foreach ($digerKategoriler as $cat)
                        <a href="{{ route('listings.category', $cat->slug) }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-stone-200/80 bg-white/90 px-3.5 py-2 text-xs font-medium text-stone-700 shadow-2xs transition hover:border-emerald-400 hover:bg-emerald-50/60 hover:text-emerald-800 dark:border-stone-800 dark:bg-stone-900/90 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                            <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($cat->icon)" class="h-4 w-4 text-stone-500 transition group-hover:text-emerald-700 dark:text-stone-400 dark:group-hover:text-emerald-400" />
                            <span>{{ $cat->name }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endif
