<x-filament-panels::page>
    <div class="text-sm text-gray-500 dark:text-gray-400">
        Bu panelden neredeyse her şeyi kendin yönetebilirsin — geliştirici ya da yapay zekâ olmadan.
        Aşağıda sık ihtiyaçlar ve nereye gideceğin var.
    </div>

    @foreach ($this->sections() as $section)
        <x-filament::section>
            <x-slot name="heading">{{ $section['baslik'] }}</x-slot>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($section['kartlar'] as $kart)
                    <a href="{{ $kart['url'] }}"
                       class="group flex items-start gap-3 rounded-lg border border-gray-200 p-3 transition hover:border-primary-400 hover:bg-gray-50 dark:border-gray-700 dark:hover:border-primary-500 dark:hover:bg-white/5">
                        <x-filament::icon :icon="$kart['ikon']" class="mt-0.5 h-6 w-6 shrink-0 text-primary-500" />
                        <span>
                            <span class="block font-medium text-gray-950 group-hover:text-primary-600 dark:text-white dark:group-hover:text-primary-400">{{ $kart['baslik'] }}</span>
                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ $kart['aciklama'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </x-filament::section>
    @endforeach

    {{-- Acil durum yönergeleri --}}
    <x-filament::section>
        <x-slot name="heading">Acil durum: sen yokken</x-slot>
        <x-slot name="description">Sana veya yapay zekâya ulaşılamadığında izlenecek adımlar.</x-slot>

        <div class="space-y-3">
            @foreach ($this->emergency() as $item)
                <div class="flex items-start gap-3 rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <x-filament::icon icon="heroicon-o-shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-primary-500" />
                    <div>
                        <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $item['baslik'] }}</div>
                        <div class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">{{ $item['adim'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
