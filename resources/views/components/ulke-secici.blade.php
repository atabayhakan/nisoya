@props(['country' => null, 'countries'])

<div
    x-data="{
        acik: false,
        arama: '',
        ac() {
            this.acik = true;
            this.$nextTick(() => this.$refs.aramaGirdisi?.focus());
        },
        kapat() {
            this.acik = false;
            this.arama = '';
        }
    }"
    @keydown.escape.window="kapat()"
    @click.outside="kapat()"
    class="relative shrink-0"
>
    {{-- Tetikleyici Buton --}}
    <button
        type="button"
        @click="acik ? kapat() : ac()"
        class="inline-flex h-9 items-center gap-1.5 rounded-full border border-stone-200/90 bg-stone-50/80 px-2.5 sm:px-3 text-xs font-semibold text-stone-700 shadow-2xs transition hover:border-emerald-300 hover:bg-white hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-800/60 dark:text-stone-200 dark:hover:border-emerald-600 dark:hover:text-emerald-300"
        :aria-expanded="acik ? 'true' : 'false'"
        aria-haspopup="true"
        aria-label="Ülke seç"
    >
        <span class="rounded bg-stone-200/80 px-1 py-0.5 text-[10px] font-bold text-stone-700 dark:bg-stone-700 dark:text-stone-300">{{ $country?->code ?? '🌍' }}</span>
        <span class="hidden max-w-[92px] truncate sm:inline">{{ $country?->name_tr ?? $country?->name ?? 'Ülke' }}</span>
        <x-heroicon-o-chevron-down class="h-3 w-3 text-stone-500 transition-transform duration-200 dark:text-stone-400" ::class="acik ? 'rotate-180' : ''" />
    </button>

    {{-- Masaüstü Açılır Menü (Doğrudan Butonun Altında) --}}
    <div
        x-show="acik"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="hidden sm:block absolute right-0 top-full mt-2 w-80 overflow-hidden rounded-2xl border border-stone-200/90 bg-white p-3 shadow-xl ring-1 ring-black/5 dark:border-stone-800 dark:bg-stone-900 z-50"
        x-cloak
    >
        <div class="mb-2 flex items-center justify-between px-1">
            <span class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Ülke Seçimi</span>
            <span class="text-[11px] text-stone-500 dark:text-stone-400">{{ count($countries) }} Ülke</span>
        </div>

        {{-- Arama Çubuğu --}}
        <div class="relative mb-2">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-stone-500 dark:text-stone-400" />
            <input
                type="text"
                x-ref="aramaGirdisi"
                x-model="arama"
                placeholder="Ülke ara..."
                class="w-full rounded-xl border border-stone-200 bg-stone-50/80 py-1.5 pl-8 pr-3 text-xs text-stone-800 placeholder-stone-400 transition focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500"
            >
        </div>

        <ul class="max-h-60 overflow-y-auto space-y-0.5 pr-1">
            @foreach ($countries as $ulke)
                <li x-show="!arama || '{{ Str::lower($ulke->name_tr) }} {{ Str::lower($ulke->code) }}'.includes(arama.toLowerCase())">
                    <a
                        href="{{ url('/ilanlar') }}?ulke={{ $ulke->code }}"
                        class="flex items-center justify-between rounded-xl px-2.5 py-2 text-xs font-medium transition {{ $country?->code === $ulke->code ? 'bg-emerald-50 text-emerald-800 font-bold dark:bg-emerald-950/40 dark:text-emerald-300' : 'text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800' }}"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-stone-100 text-[10px] font-bold text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                {{ $ulke->code }}
                            </span>
                            <span class="truncate">{{ $ulke->name_tr }}</span>
                        </div>
                        @if ($country?->code === $ulke->code)
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-400">
                                <x-heroicon-s-check class="h-3.5 w-3.5" />
                                <span>Seçili</span>
                            </span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Mobil Alt Çekmece (Bottom Drawer) --}}
    <template x-teleport="body">
        <div
            x-show="acik"
            x-transition.opacity.duration.200ms
            class="sm:hidden fixed inset-0 z-50 bg-stone-900/60"
            @click.self="kapat()"
            role="dialog"
            aria-modal="true"
            aria-label="Ülke seç"
            x-cloak
        >
            <div
                x-show="acik"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                class="fixed inset-x-0 bottom-0 max-h-[80vh] overflow-y-auto rounded-t-3xl bg-white p-4 pb-[calc(1.5rem+env(safe-area-inset-bottom))] shadow-2xl dark:bg-stone-900"
            >
                <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-stone-200 dark:bg-stone-700"></div>
                <div class="mb-2 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-stone-900 dark:text-stone-50">Hangi ülke?</h2>
                        <p class="text-xs text-stone-500 dark:text-stone-400">Seçtiğin ülkedeki ilanlara yönlendirilirsin.</p>
                    </div>
                    <button type="button" @click="kapat()" class="-mr-1 grid h-9 w-9 place-items-center rounded-full text-stone-500 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800" aria-label="Kapat">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <ul class="mt-3 space-y-1">
                    @foreach ($countries as $ulke)
                        <li>
                            <a
                                href="{{ url('/ilanlar') }}?ulke={{ $ulke->code }}"
                                class="flex min-h-12 items-center justify-between rounded-2xl px-3 text-sm font-medium transition {{ $country?->code === $ulke->code ? 'bg-emerald-50 text-emerald-800 font-bold dark:bg-emerald-950/40 dark:text-emerald-300' : 'text-stone-700 hover:bg-stone-50 dark:text-stone-200 dark:hover:bg-stone-800' }}"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-xl bg-stone-100 text-xs font-bold text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                        {{ $ulke->code }}
                                    </span>
                                    <span class="truncate">{{ $ulke->name_tr }}</span>
                                </div>
                                @if ($country?->code === $ulke->code)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-bold text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                                        <x-heroicon-s-check class="h-3.5 w-3.5" />
                                        <span>Buradasın</span>
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </template>
</div>
