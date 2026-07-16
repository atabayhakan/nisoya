<x-filament-panels::page>
    <div class="grid gap-4 sm:grid-cols-2">
        {{-- 1. Tasarım — Eski --}}
        <div @class([
            'rounded-xl border-2 p-6 transition',
            'border-primary-500 bg-primary-50/60 dark:bg-primary-500/10' => $aktifMod === 'eski',
            'border-gray-200 dark:border-gray-700' => $aktifMod !== 'eski',
        ])>
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-bold text-gray-950 dark:text-white">1. Tasarım</h3>
                @if ($aktifMod === 'eski')
                    <x-filament::badge color="success">Aktif</x-filament::badge>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Mevcut, bilinen tasarım. Zümrüt yeşili, taş grisi zemin, Instrument Sans başlıklar.
            </p>
            <div class="mt-4 flex items-center gap-2">
                <span class="h-8 w-8 rounded-full ring-1 ring-black/5" style="background:#059669"></span>
                <span class="h-8 w-8 rounded-full ring-1 ring-black/5" style="background:#fafaf9"></span>
                <span class="font-sans text-sm text-gray-400">Aa</span>
            </div>
            <x-filament::button
                class="mt-5"
                color="gray"
                :disabled="$aktifMod === 'eski'"
                wire:click="secModu('eski')"
            >
                1. Tasarımı Etkinleştir
            </x-filament::button>
        </div>

        {{-- 2. Tasarım — Yeni (2027 vizyon pilotu) --}}
        <div @class([
            'rounded-xl border-2 p-6 transition',
            'border-primary-500 bg-primary-50/60 dark:bg-primary-500/10' => $aktifMod === 'yeni',
            'border-gray-200 dark:border-gray-700' => $aktifMod !== 'yeni',
        ])>
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-bold text-gray-950 dark:text-white">2. Tasarım</h3>
                @if ($aktifMod === 'yeni')
                    <x-filament::badge color="success">Aktif</x-filament::badge>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                2027 vizyon pilotu: "Vitrin Yeşili" + sıcak "Tezgah Kremi" zemin + anasayfa başlığında Instrument Serif italik.
            </p>
            <div class="mt-4 flex items-center gap-2">
                <span class="h-8 w-8 rounded-full ring-1 ring-black/5" style="background:#0f5c42"></span>
                <span class="h-8 w-8 rounded-full ring-1 ring-black/5" style="background:#f3eee4"></span>
                <span class="font-serif text-sm italic text-gray-400">Aa</span>
            </div>
            <x-filament::button
                class="mt-5"
                :disabled="$aktifMod === 'yeni'"
                wire:click="secModu('yeni')"
            >
                2. Tasarımı Etkinleştir
            </x-filament::button>
        </div>
    </div>

    <p class="mt-6 text-xs text-gray-400">
        Değişiklik kaydedilir kaydedilmez canlı sitede görünür (ek bir yayınlama adımı yok).
        Bu ilk pilot yalnızca marka rengini, sayfa zeminini ve anasayfa başlık yazı tipini kapsar —
        tam vizyon (Nabız Haritası, Mühür rozeti) sonraki fazlarda.
    </p>
</x-filament-panels::page>
