<div class="min-w-0 border-b border-gray-200 bg-white px-4 py-2 dark:border-gray-800 dark:bg-gray-900">
    <div class="flex min-w-0 flex-wrap items-center gap-3">
        <span class="min-w-0 break-words text-sm text-gray-600 dark:text-gray-300" aria-live="polite">Coğrafi görünüm: {{ $context->label() }}</span>
        <x-filament::modal id="global-geo-lens" width="3xl">
            <x-slot name="trigger"><x-filament::button size="sm" color="gray">Görünümü değiştir</x-filament::button></x-slot>
            <x-slot name="heading">Dünya, bölge veya şehir seçin</x-slot>
            <x-slot name="description">Uygula sizi operasyon merkezine götürür. Açık formdaki değişiklikleri önce kaydedin.</x-slot>
            <form wire:submit="apply" class="grid min-w-0 gap-4 sm:grid-cols-2">
                <label class="grid min-w-0 gap-1 text-sm">Görünüm düzeyi
                    <x-filament::input.wrapper><x-filament::input.select wire:model.live="mode">
                        <option value="global">Tüm dünya</option><option value="region">Bölge</option>
                        <option value="country">Ülke</option><option value="city">Şehir</option>
                    </x-filament::input.select></x-filament::input.wrapper>
                </label>
                @if($mode === 'region')
                    <label class="grid min-w-0 gap-1 text-sm">Bölge veya operasyon kümesi
                        <x-filament::input.wrapper><x-filament::input.select wire:model="regionId">
                            <option value="">Bölge seçin</option>
                            @foreach($regions as $region)<option value="{{ $region->id }}">{{ $region->name_tr }}</option>@endforeach
                        </x-filament::input.select></x-filament::input.wrapper>
                    </label>
                @elseif(in_array($mode, ['country', 'city'], true))
                    <label class="grid min-w-0 gap-1 text-sm">Ülke ara
                        <x-filament::input.wrapper><x-filament::input wire:model.live.debounce.300ms="countrySearch" maxlength="80" placeholder="Ülke adı veya ISO kodu" /></x-filament::input.wrapper>
                    </label>
                    <label class="grid min-w-0 gap-1 text-sm">Ülke — ilk 40 eşleşme
                        <x-filament::input.wrapper><x-filament::input.select wire:model.live="countryCode">
                            <option value="">Ülke seçin</option>
                            @foreach($countries as $country)<option value="{{ $country->code }}">{{ $country->name_tr }} ({{ $country->code }})</option>@endforeach
                        </x-filament::input.select></x-filament::input.wrapper>
                    </label>
                    @if($mode === 'city')
                        <label class="grid min-w-0 gap-1 text-sm">Şehir ara
                            <x-filament::input.wrapper><x-filament::input wire:model.live.debounce.300ms="citySearch" maxlength="80" /></x-filament::input.wrapper>
                        </label>
                        <label class="grid min-w-0 gap-1 text-sm">Şehir — ilk 40 eşleşme
                            <x-filament::input.wrapper><x-filament::input.select wire:model="cityId">
                                <option value="">Şehir seçin</option>
                                @foreach($cities as $city)<option value="{{ $city->id }}">{{ $city->name }}</option>@endforeach
                            </x-filament::input.select></x-filament::input.wrapper>
                        </label>
                    @endif
                @endif
                @if($errors->any())<p role="alert" class="text-sm text-red-600 dark:text-red-400 sm:col-span-2">{{ $errors->first() }}</p>@endif
                <div class="flex flex-wrap gap-2 sm:col-span-2">
                    <x-filament::button type="submit" wire:loading.attr="disabled">Uygula</x-filament::button>
                    <x-filament::button color="gray" wire:click="resetToGlobal" wire:loading.attr="disabled">Tüm dünya</x-filament::button>
                </div>
            </form>
        </x-filament::modal>
    </div>
</div>
