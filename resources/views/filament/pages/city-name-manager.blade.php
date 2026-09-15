<x-filament-panels::page class="gcc-surface">
    <p class="break-words text-sm text-gray-600 dark:text-gray-300">Tüm şehir kataloğu. Büyük/küçük harf ve Türkçe karakter farkları otomatik eşleşir. Farklı dildeki adları, örneğin London → Londra, burada bağlayın. Aynı ülkede başka şehre ait ad kabul edilmez.</p>
    <label class="grid min-w-0 gap-1 text-sm">Şehir veya ek ad ara
        <x-filament::input.wrapper><x-filament::input wire:model.live.debounce.300ms="search" maxlength="80" placeholder="Şehir adı" /></x-filament::input.wrapper>
    </label>
    <x-filament::section heading="Bir şehre ek ad tanımla">
        <form wire:submit="addAlias" class="grid min-w-0 gap-3 sm:grid-cols-2">
            <label class="grid min-w-0 gap-1 text-sm">Katalogdaki şehir — ilk 40 eşleşme
                <x-filament::input.wrapper><x-filament::input.select wire:model="selectedCityId"><option value="">Şehir seçin</option>@foreach($cities as $city)<option value="{{ $city->id }}">{{ $city->name }} · {{ $city->country?->name_tr ?? $city->country_code }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper>
            </label>
            <label class="grid min-w-0 gap-1 text-sm">Ek ad
                <x-filament::input.wrapper><x-filament::input wire:model="aliasName" maxlength="255" placeholder="Örneğin London" /></x-filament::input.wrapper>
            </label>
            @if($errors->any())<p role="alert" class="break-words text-sm text-red-600 sm:col-span-2">{{ $errors->first() }}</p>@endif
            <div><x-filament::button type="submit" wire:loading.attr="disabled">Eşleştir</x-filament::button></div>
        </form>
    </x-filament::section>
    <div class="grid min-w-0 gap-3 md:grid-cols-2 xl:grid-cols-3">
        @foreach($aliases as $alias)
            <article wire:key="alias-{{ $alias->id }}" class="min-w-0 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="break-words font-semibold">{{ $alias->name }}</h3>
                <p class="break-words text-sm">{{ $alias->city->name }} · {{ $alias->city->country?->name_tr ?? $alias->city->country_code }}</p>
                @if($alias->is_canonical)<p class="mt-2 text-xs text-gray-500">Katalog adı</p>
                @else<x-filament::button class="mt-2" size="sm" color="gray" wire:click="removeAlias({{ $alias->id }})" wire:confirm="Bu ek ad eşleştirmesini kaldırmak istiyor musunuz?" wire:loading.attr="disabled">Ek adı kaldır</x-filament::button>@endif
            </article>
        @endforeach
    </div>
    <div class="min-w-0">{{ $aliases->links() }}</div>
</x-filament-panels::page>
