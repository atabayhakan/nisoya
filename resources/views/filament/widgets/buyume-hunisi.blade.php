@php
    $metrikler = $this->getMetrikler();
    $sehirler = $this->getSehirler();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-base" aria-hidden="true">🚀</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">Kâhya Büyüme & Tersine Katılım Hunisi</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                        %{{ $metrikler['donusum_orani'] }} Sahiplenme Oranı
                    </span>
                </div>
            </div>
        </x-slot>

        {{-- 4 Aşamalı Büyüme Hunisi --}}
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            {{-- 1. Keşif --}}
            <div class="rounded-xl border border-gray-200/80 bg-gray-50/50 p-4 dark:border-white/10 dark:bg-white/5">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">1. Keşfedilen Türk İşletmeleri</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                        {{ number_format($metrikler['turk_isletmeler']) }}
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-400">/ {{ number_format($metrikler['toplam_kesif']) }} aday</span>
                </div>
                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Google & Harici Keşif
                </div>
            </div>

            {{-- 2. Hazırlanan Vitrin --}}
            <div class="rounded-xl border border-amber-200/80 bg-amber-50/40 p-4 dark:border-amber-700/40 dark:bg-amber-950/20">
                <div class="text-xs font-medium text-amber-800 dark:text-amber-300">2. Hazırlanan Vitrinler</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-black tracking-tight text-amber-900 dark:text-amber-100">
                        {{ number_format($metrikler['hazirlanan_vitrinler']) }}
                    </span>
                    <span class="text-xs text-amber-700 dark:text-amber-400">vitrin yayında</span>
                </div>
                <div class="mt-1 text-[11px] text-amber-800 dark:text-amber-400">
                    {{ $metrikler['bekleyen_vitrinler'] }} sahiplenme bekliyor
                </div>
            </div>

            {{-- 3. Sahiplenilen Dükkanlar --}}
            <div class="rounded-xl border border-emerald-300/80 bg-emerald-50/50 p-4 dark:border-emerald-700/60 dark:bg-emerald-950/30">
                <div class="text-xs font-medium text-emerald-800 dark:text-emerald-300">3. Sahiplenilen Vitrinler</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-black tracking-tight text-emerald-900 dark:text-emerald-100">
                        {{ number_format($metrikler['sahiplenilen_vitrinler']) }}
                    </span>
                    <span class="rounded-md bg-emerald-200/60 px-1.5 py-0.5 text-[10px] font-bold text-emerald-900 dark:bg-emerald-800/60 dark:text-emerald-200">
                        %{{ $metrikler['donusum_orani'] }}
                    </span>
                </div>
                <div class="mt-1 text-[11px] text-emerald-800 dark:text-emerald-400">
                    Aktif üyeye dönüştü
                </div>
            </div>

            {{-- 4. Onay Bekleyen Hamleler --}}
            <div class="rounded-xl border border-blue-200/80 bg-blue-50/40 p-4 dark:border-blue-700/40 dark:bg-blue-950/20">
                <div class="text-xs font-medium text-blue-800 dark:text-blue-300">4. Kâhya Onay Kuyruğu</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-black tracking-tight text-blue-900 dark:text-blue-100">
                        {{ number_format($metrikler['onay_bekleyen_hamleler']) }}
                    </span>
                    <span class="text-xs text-blue-700 dark:text-blue-400">davet mektubu</span>
                </div>
                <div class="mt-1 text-[11px] text-blue-800 dark:text-blue-400">
                    Onay bekleyen e-posta
                </div>
            </div>
        </div>

        {{-- Şehir Dağılımı Özeti --}}
        @if (!empty($sehirler))
            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 dark:border-white/5">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Lider Şehirler:</span>
                @foreach ($sehirler as $s)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-xs text-gray-700 dark:bg-white/5 dark:text-gray-300">
                        <span class="font-medium text-gray-900 dark:text-white">{{ $s['sehir'] }} ({{ $s['ulke'] }})</span>
                        <span class="text-gray-400 dark:text-gray-400">·</span>
                        <span>{{ $s['toplam_vitrin'] }} vitrin</span>
                        @if ($s['sahiplenilen'] > 0)
                            <span class="font-bold text-emerald-700 dark:text-emerald-400">({{ $s['sahiplenilen'] }} sahipli · %{{ $s['oran'] }})</span>
                        @endif
                    </span>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
