@php
    $this->kontrolVeBildir();
    $durum = $this->getDurum();
@endphp

<x-filament-widgets::widget wire:poll.3s>
    @if ($durum)
        @php
            $bulunanlar = $this->getSonBulunanlar();
            $tamamlandi = $durum['tamamlanan'] >= $durum['toplam'];
        @endphp

        <x-filament::section>
            <div class="flex items-center gap-2">
                <span class="text-lg" aria-hidden="true">{{ $tamamlandi ? '✅' : '🔎' }}</span>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    @if ($tamamlandi)
                        Keşif tamamlandı — {{ $durum['toplam'] }} arama bitti, {{ $bulunanlar->count() }}+ işletme bulundu.
                    @else
                        Keşif çalışıyor: {{ $durum['tamamlanan'] }} / {{ $durum['toplam'] }} arama bitti…
                    @endif
                </p>
            </div>

            {{-- Her arama bir nokta: bitenler yeşil tik, kalanlar nabız atıyor —
                 beklerken izlenecek bir şey olsun diye. --}}
            <div class="mt-3 flex flex-wrap gap-1.5">
                @for ($i = 0; $i < $durum['toplam']; $i++)
                    @if ($i < $durum['tamamlanan'])
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">✓</span>
                    @else
                        <span class="h-5 w-5 animate-pulse rounded-full bg-primary-200 dark:bg-primary-500/30"></span>
                    @endif
                @endfor
            </div>

            {{-- Bulunan işletmeler bir kez belirip kaybolur — sunucudan bağımsız,
                 saf Alpine: wire:key sabit kaldığı sürece "gorunur" state'i
                 sonraki poll'lerde de korunur, yani ikinci kez parlamaz. --}}
            @if ($bulunanlar->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($bulunanlar as $b)
                        <span
                            wire:key="kesif-bulunan-{{ $b['id'] }}"
                            x-data="{ gorunur: true }"
                            x-init="setTimeout(() => gorunur = false, 4000)"
                            x-show="gorunur"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-500"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-400/20"
                        >
                            ✨ {{ $b['name'] }}
                        </span>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
