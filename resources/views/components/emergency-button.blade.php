@props(['categories'])

@if ($categories->isNotEmpty())
    <div x-data="{ open: false }" @keydown.escape.window="open = false">
        <button
            type="button"
            @click="open = true"
            class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-2.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-rose-700 hover:shadow-lg dark:bg-rose-500 dark:hover:bg-rose-400"
            aria-label="Acil yardım — hızlı erişim"
            title="Acil yardım — hızlı erişim"
        >
            <x-heroicon-s-exclamation-triangle class="h-4 w-4" />
            <span class="hidden sm:inline">Acil</span>
        </button>

        <div
            x-show="open"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-50 flex items-end justify-center bg-stone-900/60 p-3 sm:items-center sm:p-6"
            @click.self="open = false"
            role="dialog"
            aria-modal="true"
            aria-labelledby="emergency-title"
            x-cloak
        >
            <div
                x-show="open"
                x-transition.duration.200ms
                class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-stone-200 dark:bg-stone-900 dark:ring-stone-800"
            >
                <div class="flex items-start justify-between gap-4 border-b border-rose-100 bg-rose-50 px-5 py-4 dark:border-rose-900/40 dark:bg-rose-950/30">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-600 text-white dark:bg-rose-500">
                            <x-heroicon-s-exclamation-triangle class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 id="emergency-title" class="text-lg font-bold text-rose-900 dark:text-rose-200">Acil Yardım</h2>
                            <p class="mt-0.5 text-sm text-rose-800/80 dark:text-rose-300/80">Bulunduğun ülkede hızlıca ulaşabileceğin Türkler</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="open = false"
                        class="rounded-full p-1.5 text-rose-700 transition hover:bg-rose-100 dark:text-rose-300 dark:hover:bg-rose-900/40"
                        aria-label="Kapat"
                    >
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-2 px-5 py-5">
                    @foreach ($categories as $cat)
                        <a
                            href="{{ route('listings.category', $cat->slug) }}"
                            class="flex items-center justify-between gap-3 rounded-xl border border-stone-200 px-4 py-3 transition hover:border-rose-300 hover:bg-rose-50 dark:border-stone-700 dark:hover:border-rose-700 dark:hover:bg-rose-950/20"
                        >
                            <span class="flex items-center gap-3">
                                <span class="grid h-9 w-9 place-items-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300">
                                    <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($cat->icon)" class="h-5 w-5" />
                                </span>
                                <span class="font-semibold text-stone-800 dark:text-stone-100">{{ $cat->name }}</span>
                            </span>
                            <x-heroicon-o-arrow-right class="h-4 w-4 text-stone-400" />
                        </a>
                    @endforeach
                </div>

                <div class="border-t border-stone-100 bg-stone-50 px-5 py-3 text-center text-xs text-stone-500 dark:border-stone-800 dark:bg-stone-800 dark:text-stone-400">
                    Nisoya bir ilan platformudur — acil durumda önce resmi acil servisleri (112 vb.) aramayı unutma.
                </div>
            </div>
        </div>
    </div>
@endif
