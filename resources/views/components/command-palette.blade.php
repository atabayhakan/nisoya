@props(['navLinks'])

@php
    // Statik girdiler (Faz H2): nav linkleri + sık kullanılan sayfalar.
    // Client-side alt-dize eşleşir (bkz. resources/js/app.js commandPalette) —
    // ağ isteği gerektirmez, canlı sonuçlardan (ilan/iş/aday) önce görünür.
    $staticEntries = $navLinks
        ->map(fn ($link) => ['category' => 'Menü', 'title' => $link->label, 'url' => $link->url])
        ->push(['category' => 'Sayfa', 'title' => 'İlan Ver', 'url' => url('/panel/ilan/yeni')]);

    if (auth()->check()) {
        $staticEntries->push(['category' => 'Sayfa', 'title' => 'Panelim', 'url' => route('dashboard')]);
        $staticEntries->push(['category' => 'Sayfa', 'title' => 'Bildirimler', 'url' => route('panel.notifications.index')]);
    }

    // Obsidian teması koyu moda kilitli — tema değiştirme aksiyonu o modda no-op olur.
    if (! \App\Support\Tema::koyuKilit()) {
        $staticEntries->push(['category' => 'Aksiyon', 'title' => 'Temayı Değiştir', 'action' => 'toggleTheme']);
    }
@endphp

<div
    x-data="commandPalette(@js($staticEntries->values()))"
    @keydown.cmd.k.window.prevent="openPalette()"
    @keydown.ctrl.k.window.prevent="openPalette()"
    @keydown.escape.window="open && closePalette()"
>
    <button
        type="button"
        @click="openPalette()"
        class="inline-flex h-9 items-center gap-1.5 rounded-lg px-2 text-stone-600 transition hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800"
        aria-label="Ara (Cmd/Ctrl+K)"
        title="Ara (Cmd/Ctrl+K)"
    >
        <x-heroicon-o-magnifying-glass class="h-5 w-5" />
        <kbd class="hidden rounded border border-stone-300 px-1.5 py-0.5 text-2xs font-semibold text-stone-600 md:inline dark:border-stone-700 dark:text-stone-400">⌘K</kbd>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-transition.opacity.duration.150ms
            class="fixed inset-0 z-50 flex items-start justify-center bg-stone-900/60 p-4 pt-[10vh]"
            @click.self="closePalette()"
            role="dialog"
            aria-modal="true"
            aria-label="Hızlı arama"
            x-cloak
        >
            <div
                x-ref="panel"
                x-show="open"
                x-transition.duration.150ms
                @keydown.arrow-down.prevent="move(1)"
                @keydown.arrow-up.prevent="move(-1)"
                @keydown.enter.prevent="choose()"
                @keydown.tab="trapFocus($event)"
                class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-stone-200 dark:bg-stone-900 dark:ring-stone-800"
            >
                <div class="flex items-center gap-3 border-b border-stone-100 px-4 py-3 dark:border-stone-800">
                    <x-heroicon-o-magnifying-glass class="h-5 w-5 shrink-0 text-stone-600" />
                    <input
                        x-ref="input"
                        x-model="query"
                        @input="onInput()"
                        type="text"
                        placeholder="Ne arıyorsun? İlan, iş ilanı, yetenek..."
                        class="w-full border-0 bg-transparent p-0 text-sm text-stone-800 placeholder-stone-400 focus:ring-0 dark:text-stone-100"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="true"
                        aria-controls="command-palette-listbox"
                    >
                    <kbd class="hidden shrink-0 rounded border border-stone-300 px-1.5 py-0.5 text-2xs font-semibold text-stone-600 sm:inline dark:border-stone-700 dark:text-stone-400">Esc</kbd>
                </div>

                <div id="command-palette-listbox" role="listbox" class="max-h-96 overflow-y-auto p-2">
                    <template x-if="query.trim().length === 0">
                        <p class="px-3 py-6 text-center text-sm text-stone-600 dark:text-stone-400">Aramaya başlamak için yazmaya başla.</p>
                    </template>

                    <template x-if="query.trim().length > 0 && results.length === 0 && !loading">
                        <p class="px-3 py-6 text-center text-sm text-stone-500 dark:text-stone-400">Sonuç bulunamadı.</p>
                    </template>

                    <template x-for="(item, index) in results" :key="index + '-' + item.title">
                        <a
                            :href="item.url ?? '#'"
                            @click.prevent="choose(index)"
                            @mousemove="activeIndex = index"
                            :class="activeIndex === index ? 'bg-emerald-50 dark:bg-emerald-900/30' : ''"
                            class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm transition"
                            role="option"
                            :aria-selected="activeIndex === index ? 'true' : 'false'"
                        >
                            <span class="flex min-w-0 flex-col">
                                <span class="truncate font-medium text-stone-800 dark:text-stone-100" x-text="item.title"></span>
                                <span class="truncate text-xs text-stone-600 dark:text-stone-400" x-show="item.subtitle" x-text="item.subtitle"></span>
                            </span>
                            <span class="shrink-0 rounded-full bg-stone-100 px-2 py-0.5 text-2xs font-semibold text-stone-600 dark:bg-stone-800 dark:text-stone-400" x-text="item.category"></span>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
