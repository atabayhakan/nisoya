@php $veri = $this->getVeri(); @endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <h2 class="text-base font-bold text-gray-950 dark:text-white">Kategori dağılımı</h2>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Aktif ilanların tipe göre payı</p>

        @if ($veri['toplam'] === 0)
            <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">Henüz aktif ilan yok.</p>
        @else
            <div class="mt-4 flex items-center gap-5">
                {{-- Saf SVG halka — grafik kütüphanesi yok --}}
                <div class="relative shrink-0">
                    <svg width="104" height="104" viewBox="0 0 96 96" class="-rotate-90" aria-hidden="true">
                        <circle cx="48" cy="48" r="36" fill="none" stroke="currentColor"
                                class="text-gray-100 dark:text-white/10" stroke-width="12" />
                        @foreach ($veri['dilimler'] as $dilim)
                            <circle cx="48" cy="48" r="36" fill="none"
                                    stroke="{{ $dilim['renk'] }}" stroke-width="12" stroke-linecap="butt"
                                    stroke-dasharray="{{ $dilim['uzunluk'] }} {{ $veri['cevre'] }}"
                                    stroke-dashoffset="{{ $dilim['kayma'] }}" />
                        @endforeach
                    </svg>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-lg font-bold leading-none text-gray-950 dark:text-white">{{ $veri['toplam'] }}</span>
                        <span class="mt-0.5 text-[10px] font-medium text-gray-500 dark:text-gray-400">ilan</span>
                    </div>
                </div>

                <div class="grid min-w-0 flex-1 gap-1.5">
                    @foreach ($veri['dilimler'] as $dilim)
                        <div class="flex items-center gap-2 text-xs">
                            <span class="h-2 w-2 shrink-0 rounded-sm" style="background: {{ $dilim['renk'] }}"></span>
                            <span class="truncate font-medium text-gray-700 dark:text-gray-300">{{ $dilim['etiket'] }}</span>
                            <span class="ml-auto shrink-0 font-semibold text-gray-500 dark:text-gray-400">%{{ $dilim['yuzde'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
