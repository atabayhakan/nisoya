@php
    $catVal = (int) old('category_id', $listing?->category_id);
    $curVal = old('currency', $listing?->currency ?? auth()->user()?->preferred_currency ?? 'EUR');
    $defaultUnit = ($type ?? 'hizmet') === 'urun' ? 'adet' : 'gorusulur';
    $unitVal = old('price_unit', $listing?->price_unit?->value ?? $defaultUnit);
    $countryVal = old('country_code', $listing?->country_code ?? auth()->user()?->country_code);
@endphp

<input type="hidden" name="type" value="{{ $type ?? 'hizmet' }}">
<div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

<div>
    <label for="title" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Başlık</label>
    <input id="title" name="title" type="text" value="{{ old('title', $listing?->title) }}" required
           placeholder="{{ match ($type ?? 'hizmet') { 'urun' => 'ör. Ev yapımı fıstıklı baklava (1 kg)', 'emlak' => 'ör. Kreuzberg\'de eşyalı 2+1 kiralık daire', default => 'ör. Online İngilizce konuşma dersi veriyorum' } }}"
           class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
    @error('title') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
</div>

<div>
    <label for="category_id" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Kategori</label>
    <select id="category_id" name="category_id" required
            class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
        <option value="">Kategori seç...</option>
        @foreach ($categories as $parent)
            <optgroup label="{{ $parent->icon }} {{ $parent->name }}">
                @foreach ($parent->children as $child)
                    <option value="{{ $child->id }}" @selected($catVal === $child->id)>{{ $child->name }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
    @error('category_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
</div>

<div>
    <label for="description" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Açıklama</label>
    <textarea id="description" name="description" rows="6" required
              placeholder="{{ match ($type ?? 'hizmet') { 'urun' => 'Ürününü anlat: malzemeler, boyut/ağırlık, teslimat & kargo, hazırlık süresi...', 'emlak' => 'Evi anlat: konum/ulaşım, ısıtma, bina yaşı, çevre olanakları, kiralama koşulları...', default => 'Sunduğun hizmeti detaylıca anlat: deneyimin, neler yaptığın, nasıl çalıştığın...' } }}"
              class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('description', $listing?->description) }}</textarea>
    @error('description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
</div>

<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label for="price" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Fiyat <span class="text-stone-400 dark:text-stone-500">(ops.)</span></label>
        <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $listing?->price) }}"
               placeholder="Boş = görüşülür"
               class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
        @error('price') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="currency" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Para birimi</label>
        <select id="currency" name="currency" required
                class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            @foreach ($currencies as $currency)
                <option value="{{ $currency->code }}" @selected($curVal === $currency->code)>{{ $currency->symbol }} {{ $currency->code }}</option>
            @endforeach
        </select>
        @error('currency') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="price_unit" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Birim</label>
        <select id="price_unit" name="price_unit" required
                class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            @foreach ($priceUnits as $unit)
                <option value="{{ $unit->value }}" @selected($unitVal === $unit->value)>{{ $unit->getLabel() }}</option>
            @endforeach
        </select>
        @error('price_unit') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="country_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Ülke</label>
        <select id="country_code" name="country_code" required
                class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            <option value="">Seç...</option>
            @foreach ($countries as $country)
                <option value="{{ $country->code }}" @selected($countryVal === $country->code)>{{ $country->emoji }} {{ $country->name_tr }}</option>
            @endforeach
        </select>
        @error('country_code') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="city" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Şehir <span class="text-stone-400 dark:text-stone-500">(ops.)</span></label>
        <input id="city" name="city" type="text" value="{{ old('city', $listing?->city) }}" list="city-options" autocomplete="off"
               class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
        @error('city') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        @include('partials.city-datalist')
    </div>
</div>

@if (($type ?? 'hizmet') === 'hizmet')
    <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
        <input type="checkbox" name="is_remote" value="1" @checked(old('is_remote', $listing?->is_remote))
               class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
        Bu hizmeti uzaktan / online verebiliyorum
    </label>
@elseif (($type ?? 'hizmet') === 'urun')
    <div>
        <label for="stock" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Stok adedi <span class="text-stone-400 dark:text-stone-500">(ops.)</span></label>
        <input id="stock" name="stock" type="number" min="0" value="{{ old('stock', $listing?->stock) }}"
               placeholder="ör. 10" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:max-w-xs dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
        @error('stock') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Teslimat/kargo bilgilerini açıklamaya ekleyebilirsin.</p>
    </div>
@elseif (($type ?? 'hizmet') === 'emlak')
    @php
        $detail = $listing?->propertyDetail;
        $roomsVal = old('rooms', $detail?->rooms);
        $badgesVal = (array) old('badges', $detail?->badges ?? []);
    @endphp

    <fieldset class="rounded-xl border border-stone-200 p-4 dark:border-stone-800">
        <legend class="px-1 text-sm font-semibold text-stone-700 dark:text-stone-300">🏡 Emlak detayları</legend>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="rooms" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Oda sayısı</label>
                <select id="rooms" name="rooms"
                        class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="">Seç...</option>
                    @foreach (\App\Models\ListingPropertyDetail::ROOM_OPTIONS as $option)
                        <option value="{{ $option }}" @selected($roomsVal === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                @error('rooms') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="area_m2" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Brüt m² <span class="text-stone-400 dark:text-stone-500">(ops.)</span></label>
                <input id="area_m2" name="area_m2" type="number" min="1" max="10000" value="{{ old('area_m2', $detail?->area_m2) }}"
                       placeholder="ör. 85" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('area_m2') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="floor" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Bulunduğu kat <span class="text-stone-400 dark:text-stone-500">(ops.)</span></label>
                <input id="floor" name="floor" type="number" min="-5" max="90" value="{{ old('floor', $detail?->floor) }}"
                       placeholder="ör. 3" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('floor') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="deposit" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Depozito <span class="text-stone-400 dark:text-stone-500">(ops., ilan para biriminde)</span></label>
                <input id="deposit" name="deposit" type="number" step="0.01" min="0" value="{{ old('deposit', $detail?->deposit) }}"
                       placeholder="ör. 1500" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('deposit') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="available_from" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Müsait tarih <span class="text-stone-400 dark:text-stone-500">(ops.)</span></label>
                <input id="available_from" name="available_from" type="date" value="{{ old('available_from', $detail?->available_from?->toDateString()) }}"
                       class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('available_from') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <label class="mt-4 flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
            <input type="checkbox" name="furnished" value="1" @checked(old('furnished', $detail?->furnished))
                   class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
            Eşyalı
        </label>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="max_guests" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Konuk kapasitesi <span class="text-stone-400 dark:text-stone-500">(kısa dönem, ops.)</span></label>
                <input id="max_guests" name="max_guests" type="number" min="1" max="50" value="{{ old('max_guests', $detail?->max_guests) }}"
                       placeholder="ör. 4" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('max_guests') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="min_stay_nights" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Min. konaklama (gece) <span class="text-stone-400 dark:text-stone-500">(kısa dönem, ops.)</span></label>
                <input id="min_stay_nights" name="min_stay_nights" type="number" min="1" max="365" value="{{ old('min_stay_nights', $detail?->min_stay_nights) }}"
                       placeholder="ör. 2" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('min_stay_nights') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4">
            <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">Yeni gelenlere uygun <span class="text-stone-400 dark:text-stone-500">(işaretlediklerin ilanında rozet olur)</span></span>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @foreach (\App\Models\ListingPropertyDetail::BADGES as $key => $label)
                    <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                        <input type="checkbox" name="badges[]" value="{{ $key }}" @checked(in_array($key, $badgesVal, true))
                               class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('badges') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>
    </fieldset>
@endif
