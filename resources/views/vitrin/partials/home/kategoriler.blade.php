    {{-- Kategori şeridi (Vitrin Teması — Modern Bento & Kart Tasarımı) --}}
    @if (\App\Support\HomeSections::visible('kategoriler') && $categories->isNotEmpty())
        @php
            $kategoriRenkleri = [
                ['bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300'],
                ['bg-teal-50 text-teal-700 ring-1 ring-teal-600/20 dark:bg-teal-950/60 dark:text-teal-300'],
                ['bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/60 dark:text-amber-300'],
                ['bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/60 dark:text-rose-300'],
                ['bg-violet-50 text-violet-700 ring-1 ring-violet-600/20 dark:bg-violet-950/60 dark:text-violet-300'],
            ];
        @endphp
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            {{-- MOBİL: yatay kaydırılan ikon şeridi ("uygulama" düzeni). --}}
            <div class="mb-3 flex items-baseline justify-between sm:hidden">
                <h2 class="text-lg font-extrabold text-stone-900 dark:text-stone-50">Ne arıyorsun?</h2>
                <a href="{{ url('/ilanlar') }}" class="text-xs font-bold text-emerald-700 dark:text-emerald-400">Tümünü gör →</a>
            </div>

            <div class="-mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto px-4 pb-1 sm:hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @if (\App\Support\Modules::enabled('hali_saha'))
                    <a href="{{ route('football.index') }}"
                       class="flex w-[4.75rem] shrink-0 snap-start flex-col items-center gap-1.5 text-center">
                        <span class="grid h-[4.25rem] w-[4.25rem] place-items-center rounded-2xl bg-emerald-700 text-2xl text-white shadow-xs">
                            ⚽
                        </span>
                        <span class="text-2xs font-semibold leading-tight text-emerald-800 dark:text-emerald-300">Halı Saha</span>
                    </a>
                @endif
                @foreach ($categories->take(10) as $category)
                    <a href="{{ route('listings.category', $category) }}"
                       class="flex w-[4.75rem] shrink-0 snap-start flex-col items-center gap-1.5 text-center">
                        <span class="grid h-[4.25rem] w-[4.25rem] place-items-center rounded-2xl {{ $kategoriRenkleri[$loop->index % 5][0] }}">
                            <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($category->icon)" class="h-7 w-7" />
                        </span>
                        <span class="text-2xs font-semibold leading-tight text-stone-600 dark:text-stone-300">{{ $category->name }}</span>
                    </a>
                @endforeach
                <a href="{{ url('/ilanlar') }}" class="flex w-[4.75rem] shrink-0 snap-start flex-col items-center gap-1.5 text-center">
                    <span class="grid h-[4.25rem] w-[4.25rem] place-items-center rounded-2xl bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-400">
                        <x-heroicon-o-ellipsis-horizontal class="h-7 w-7" />
                    </span>
                    <span class="text-2xs font-semibold leading-tight text-stone-600 dark:text-stone-300">Tümü</span>
                </a>
            </div>

            {{-- MASAÜSTÜ: Çerçeveli & Yüksek Kaliteli Bento Kart Izgarası --}}
            <div class="hidden sm:block rounded-3xl border border-stone-200/90 bg-white p-6 sm:p-10 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300">
                            ⚡ Hızlı Keşif &amp; İlanlar
                        </span>
                        <h2 class="mt-2 text-xl sm:text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                            Popüler Kategoriler &amp; Hizmetler
                        </h2>
                        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                            Yurt dışında aradığın güvenilir usta, Türkçe hizmetler, iş ve topluluk alanları.
                        </p>
                    </div>
                    <a href="{{ url('/ilanlar') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-xs font-semibold text-stone-700 transition hover:border-emerald-300 hover:bg-stone-100 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-600 dark:hover:text-emerald-300">
                        <span>Tüm Kategoriler ({{ $categories->count() }})</span>
                        <x-heroicon-o-arrow-right class="h-3.5 w-3.5" />
                    </a>
                </div>

                <div class="hidden grid-cols-2 gap-3.5 sm:grid sm:grid-cols-3 lg:grid-cols-5">
                    @if (\App\Support\Modules::enabled('hali_saha'))
                        <a href="{{ route('football.index') }}"
                           class="group relative flex flex-col justify-between rounded-2xl border border-emerald-300/90 bg-gradient-to-br from-emerald-50/80 to-emerald-100/40 p-4 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:border-emerald-500 hover:shadow-md dark:border-emerald-800 dark:from-emerald-950/40 dark:to-emerald-900/20">
                            <div class="flex items-center justify-between">
                                <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-700 text-lg text-white shadow-xs transition group-hover:scale-105">
                                    ⚽
                                </span>
                                <span class="rounded-md bg-emerald-700 px-2 py-0.5 text-[10px] font-bold text-white uppercase tracking-wider dark:bg-emerald-500 dark:text-stone-950">Yeni</span>
                            </div>
                            <div class="mt-4">
                                <div class="text-sm font-bold text-emerald-950 dark:text-emerald-100">Halı Saha &amp; Spor</div>
                                <div class="mt-0.5 text-[11px] font-medium text-emerald-700 dark:text-emerald-400">Takım Kur &amp; Maç Yap →</div>
                            </div>
                        </a>
                    @endif
                    @foreach ($categories->take(9) as $category)
                        <a href="{{ route('listings.category', $category) }}"
                           class="group relative flex flex-col justify-between rounded-2xl border border-stone-200/80 bg-stone-50/60 p-4 shadow-2xs transition-all duration-200 hover:-translate-y-1 hover:border-emerald-300 hover:bg-white hover:shadow-md dark:border-stone-800 dark:bg-stone-800/40 dark:hover:border-emerald-700 dark:hover:bg-stone-800">
                            <div class="flex items-center justify-between">
                                <span class="grid h-10 w-10 place-items-center rounded-xl {{ $kategoriRenkleri[$loop->index % 5][0] }} transition group-hover:scale-105">
                                    <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($category->icon)" class="h-5 w-5" />
                                </span>
                                <span class="grid h-6 w-6 place-items-center rounded-lg bg-white/80 text-stone-400 shadow-2xs transition group-hover:bg-emerald-50 group-hover:text-emerald-700 dark:bg-stone-900/80 dark:text-stone-400 dark:group-hover:bg-emerald-950/60 dark:group-hover:text-emerald-300">
                                    <x-heroicon-o-arrow-right class="h-3 w-3 transition group-hover:translate-x-0.5" />
                                </span>
                            </div>
                            <div class="mt-4">
                                <div class="text-sm font-bold text-stone-900 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-300">{{ $category->name }}</div>
                                <div class="mt-0.5 text-[11px] font-medium text-stone-500 group-hover:text-emerald-700 dark:text-stone-400 dark:group-hover:text-emerald-300">İlanları Gör →</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
