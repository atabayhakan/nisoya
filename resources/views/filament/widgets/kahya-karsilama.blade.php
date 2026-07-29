<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🤵</span>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Kâhya</h2>
                </div>

                {{-- whitespace-pre-line: karşılama metni madde satırları içeriyor. --}}
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-200">{{ $this->getKarsilama() }}</p>
            </div>

            @if (auth()->user()?->isAdmin())
                <x-filament::button
                    tag="a"
                    :href="\App\Filament\Pages\KahyaSohbet::getUrl()"
                    icon="heroicon-o-chat-bubble-left-right"
                    color="primary"
                >
                    Kâhya ile konuş
                </x-filament::button>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
