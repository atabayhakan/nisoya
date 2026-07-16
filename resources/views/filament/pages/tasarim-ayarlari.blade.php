<x-filament-panels::page>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Nisoya'nın genel görünümünü tek tıkla değiştir. Seçim anında kaydedilir ve canlı sitede
        hemen görünür — ayrı bir yayınlama adımı yok, istersen aynı kolaylıkla geri alabilirsin.
    </p>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- 1. Tasarım — Eski --}}
        <x-filament::section
            :icon="$aktifMod === 'eski' ? 'heroicon-o-check-circle' : 'heroicon-o-swatch'"
            :icon-color="$aktifMod === 'eski' ? 'success' : 'gray'"
            heading="1. Tasarım"
            description="Mevcut, bilinen tasarım."
            @class([
                'ring-2 ring-primary-600 ring-offset-2 dark:ring-offset-gray-900' => $aktifMod === 'eski',
            ])
        >
            <x-slot name="afterHeader">
                @if ($aktifMod === 'eski')
                    <x-filament::badge color="success" icon="heroicon-o-check">Şu an aktif</x-filament::badge>
                @endif
            </x-slot>

            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Zümrüt yeşili marka rengi, taş grisi zemin, Instrument Sans başlıklar — Nisoya'nın
                    açılıştan beri kullandığı, kullanıcıların alışık olduğu görünüm.
                </p>

                <div class="flex items-center gap-4 rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                    <div class="flex gap-2">
                        <span class="block h-9 w-9 rounded-full ring-2 ring-white dark:ring-gray-900" style="background:#059669" title="Marka rengi"></span>
                        <span class="block h-9 w-9 rounded-full ring-2 ring-white dark:ring-gray-900" style="background:#fafaf9" title="Zemin rengi"></span>
                    </div>
                    <div class="h-8 w-px bg-gray-200 dark:bg-gray-700"></div>
                    <p class="font-sans text-base font-bold text-gray-700 dark:text-gray-200">Ne İş Olursa Yaparım</p>
                </div>

                <x-filament::button
                    color="gray"
                    icon="heroicon-o-arrow-uturn-left"
                    :disabled="$aktifMod === 'eski'"
                    wire:click="secModu('eski')"
                    class="w-full justify-center"
                >
                    1. Tasarımı Etkinleştir
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- 2. Tasarım — Yeni (2027 vizyon pilotu) --}}
        <x-filament::section
            :icon="$aktifMod === 'yeni' ? 'heroicon-o-check-circle' : 'heroicon-o-sparkles'"
            :icon-color="$aktifMod === 'yeni' ? 'success' : 'gray'"
            heading="2. Tasarım"
            description="2027 vizyon pilotu."
            @class([
                'ring-2 ring-primary-600 ring-offset-2 dark:ring-offset-gray-900' => $aktifMod === 'yeni',
            ])
        >
            <x-slot name="afterHeader">
                @if ($aktifMod === 'yeni')
                    <x-filament::badge color="success" icon="heroicon-o-check">Şu an aktif</x-filament::badge>
                @endif
            </x-slot>

            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    "Vitrin Yeşili" marka rengi, sıcak "Tezgah Kremi" zemin ve anasayfa başlığında
                    Instrument Serif italik — esnaf/vitrin ruhunu modern bir dille taşıyan yeni yön.
                </p>

                <div class="flex items-center gap-4 rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                    <div class="flex gap-2">
                        <span class="block h-9 w-9 rounded-full ring-2 ring-white dark:ring-gray-900" style="background:#0f5c42" title="Marka rengi"></span>
                        <span class="block h-9 w-9 rounded-full ring-2 ring-white dark:ring-gray-900" style="background:#f3eee4" title="Zemin rengi"></span>
                    </div>
                    <div class="h-8 w-px bg-gray-200 dark:bg-gray-700"></div>
                    <p class="font-serif text-lg italic text-gray-700 dark:text-gray-200">Ne İş Olursa Yaparım</p>
                </div>

                <x-filament::button
                    icon="heroicon-o-sparkles"
                    :disabled="$aktifMod === 'yeni'"
                    wire:click="secModu('yeni')"
                    class="w-full justify-center"
                >
                    2. Tasarımı Etkinleştir
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>

    <p class="mt-6 flex items-start gap-2 text-xs text-gray-400 dark:text-gray-500">
        <x-heroicon-o-information-circle class="mt-0.5 h-4 w-4 shrink-0" />
        Bu ilk pilot yalnızca marka rengini, sayfa zeminini ve anasayfa başlık yazı tipini kapsar —
        tam vizyon (Nabız Haritası, Mühür rozeti) sonraki fazlarda gelecek.
    </p>
</x-filament-panels::page>
