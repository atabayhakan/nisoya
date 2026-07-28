@php $veri = $this->getVeri(); @endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-gray-950 dark:text-white">İlan hareketleri</h2>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Son 8 ay · açılan ve kapanan ilanlar</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-medium">
                <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                    <span class="h-2.5 w-2.5 rounded-sm bg-primary-500"></span> Açılan
                </span>
                <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                    <span class="h-2.5 w-2.5 rounded-sm bg-gray-300 dark:bg-gray-600"></span> Kapanan
                </span>
            </div>
        </div>

        @if ($veri['toplamAcilan'] === 0)
            <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">Son 8 ayda ilan hareketi yok.</p>
        @else
            {{-- Saf CSS bar grafiği — grafik kütüphanesi/npm bağımlılığı yok --}}
            <div class="mt-5 flex h-40 items-end gap-2 sm:gap-3">
                @foreach ($veri['aylar'] as $ay)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <div class="flex h-32 w-full items-end justify-center gap-1">
                            <div class="group relative flex h-full flex-1 items-end justify-center">
                                <div class="w-full max-w-[14px] rounded-t-md bg-primary-500 transition-all"
                                     style="height: {{ max(2, round($ay['acilan'] / $veri['enYuksek'] * 100)) }}%"
                                     title="{{ $ay['acilan'] }} açılan"></div>
                            </div>
                            <div class="group relative flex h-full flex-1 items-end justify-center">
                                <div class="w-full max-w-[14px] rounded-t-md bg-gray-300 transition-all dark:bg-gray-600"
                                     style="height: {{ max(2, round($ay['kapanan'] / $veri['enYuksek'] * 100)) }}%"
                                     title="{{ $ay['kapanan'] }} kapanan"></div>
                            </div>
                        </div>
                        <span class="text-2xs font-medium text-gray-500 dark:text-gray-400">{{ $ay['etiket'] }}</span>
                    </div>
                @endforeach
            </div>

            <p class="mt-4 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
                Bu dönemde toplam <strong class="text-gray-950 dark:text-white">{{ $veri['toplamAcilan'] }}</strong> yeni ilan açıldı.
            </p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
