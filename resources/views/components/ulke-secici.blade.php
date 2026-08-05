@props(['country' => null, 'countries'])

{{-- BAŞLIKTAKİ ÜLKE SEÇİCİ (2026-08-05).

     Eskiden burada `x-visitor-country-badge` vardı: `hidden md:inline-flex`
     olduğu için MOBİLDE HİÇ GÖRÜNMÜYORDU ve masaüstünde de tıklanamayan bir
     etiketti — yalnız "neredesin" diyordu, hiçbir şey yapmıyordu.

     Artık gerçek bir işi var: dokununca ülke listesi açılır ve seçilen ülkenin
     ilanlarına gider. Dekoratif bir "seçici" görüntüsü vermiyoruz; tıklanan
     her şey bir yere götürür.

     Mobilde yalnız bayrak + ok basılır (isim sm:'den itibaren): 390px'lik
     başlıkta arama, acil yardım ve hesap düğmesiyle birlikte yer paylaşıyor —
     ülke adı orada taşmaya yol açıyordu. Tam ad listede zaten görünür. --}}
<div x-data="{ acik: false }" @keydown.escape.window="acik = false" class="shrink-0">
    <button
        type="button"
        @click="acik = true"
        class="inline-flex items-center gap-1 rounded-full border border-stone-200 px-2.5 py-1.5 text-sm font-medium text-stone-700 transition hover:bg-stone-50 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800"
        :aria-expanded="acik ? 'true' : 'false'"
        aria-haspopup="dialog"
        aria-label="Ülke seç"
    >
        <span aria-hidden="true" class="text-base leading-none">{{ $country?->emoji ?? '🌍' }}</span>
        <span class="hidden max-w-[92px] truncate sm:inline">{{ $country?->name ?? 'Ülke' }}</span>
        <x-heroicon-o-chevron-down class="h-3.5 w-3.5 text-stone-600" />
    </button>

    <template x-teleport="body">
        <div
            x-show="acik"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-50 bg-stone-900/60"
            @click.self="acik = false"
            role="dialog"
            aria-modal="true"
            aria-label="Ülke seç"
            x-cloak
        >
            <div
                x-show="acik"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0"
                x-transition:enter-end="translate-y-0 sm:opacity-100"
                class="fixed inset-x-0 bottom-0 max-h-[75vh] overflow-y-auto rounded-t-3xl bg-white p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:inset-x-auto sm:bottom-auto sm:right-4 sm:top-16 sm:w-72 sm:rounded-2xl sm:shadow-brand dark:bg-stone-900"
            >
                <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-stone-200 sm:hidden dark:bg-stone-700"></div>
                <h2 class="mb-1 text-base font-bold text-stone-900 dark:text-stone-50">Hangi ülke?</h2>
                <p class="mb-3 text-xs text-stone-500 dark:text-stone-400">Seçtiğin ülkedeki ilanlara gider.</p>

                <ul class="space-y-0.5">
                    @foreach ($countries as $ulke)
                        <li>
                            <a
                                href="{{ url('/ilanlar') }}?ulke={{ $ulke->code }}"
                                class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 text-sm font-medium text-stone-700 hover:bg-stone-50 dark:text-stone-200 dark:hover:bg-stone-800 {{ $country?->code === $ulke->code ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}"
                            >
                                <span aria-hidden="true" class="text-base leading-none">{{ $ulke->emoji }}</span>
                                <span class="min-w-0 flex-1 truncate">{{ $ulke->name_tr }}</span>
                                @if ($country?->code === $ulke->code)
                                    <span class="shrink-0 text-2xs font-semibold">buradasın</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </template>
</div>
