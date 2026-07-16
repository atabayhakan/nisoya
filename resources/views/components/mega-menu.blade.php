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
            class="relative flex items-center gap-1 rounded-lg px-1 py-1 after:absolute after:-bottom-0.5 after:left-1 after:h-0.5 after:w-0 after:bg-emerald-600 after:transition-all after:duration-300 hover:text-emerald-700 hover:after:w-[calc(100%-0.5rem)] dark:after:bg-emerald-400 dark:hover:text-emerald-400"
            :aria-expanded="open ? 'true' : 'false'"
            aria-haspopup="true"
        >
            Keşfet
            {{-- Blade, x-bileşen etiketlerinde ":class" i PHP olarak yorumlar
                 (Alpine binding'i değil) — bu yüzden dinamik class düz bir
                 <span>'e uygulanır, ikon bileşeni statik kalır. --}}
            <span :class="open && 'rotate-180'" class="transition-transform">
                <x-heroicon-o-chevron-down class="h-4 w-4" />
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
