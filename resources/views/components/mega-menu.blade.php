@props(['items'])

{{-- Faz H1: büyüyen dikeyleri (Emlak, Vasıta, Davetiye, ...) tek bir "Keşfet"
     açılırında toplar — header'ın düz liste olarak büyümesini durdurur. Yeni
     bir dikey eklendiğinde admin sadece navigation_links'te group_key seçer,
     bu bileşene dokunulmaz. --}}
@if ($items->isNotEmpty())
    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
        <button
            type="button"
            @click="open = !open"
            class="inline-flex h-9 items-center gap-1.5 rounded-full border border-stone-200/90 bg-stone-50/80 px-3 text-xs font-semibold text-stone-700 shadow-2xs transition hover:border-emerald-300 hover:bg-white hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-800/60 dark:text-stone-200 dark:hover:border-emerald-600 dark:hover:text-emerald-300"
            :aria-expanded="open ? 'true' : 'false'"
            aria-haspopup="true"
        >
            <x-heroicon-o-squares-2x2 class="h-3.5 w-3.5 text-stone-500 dark:text-stone-400" />
            <span>Keşfet</span>
            <span :class="open && 'rotate-180'" class="transition-transform duration-200">
                <x-heroicon-o-chevron-down class="h-3 w-3 text-stone-500 dark:text-stone-400" />
            </span>
        </button>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute left-0 top-full z-40 mt-2 w-[min(90vw,32rem)] rounded-2xl border border-stone-200 bg-white p-2 shadow-xl dark:border-stone-800 dark:bg-stone-900"
            role="menu"
            aria-label="Keşfet"
            x-cloak
        >
            <x-nav-link-cards :items="$items" on-select="open = false" />
        </div>
    </div>
@endif
