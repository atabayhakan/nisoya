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
           placeholder="{{ match ($type ?? 'hizmet') { 'urun' => 'ör. Ev yapımı fıstıklı baklava (1 kg)', 'emlak' => 'ör. Kreuzberg\'de eşyalı 2+1 kiralık daire', 'vasita' => 'ör. 2019 VW Golf 1.6 TDI — kesin dönüş nedeniyle', default => 'ör. Online İngilizce konuşma dersi veriyorum' } }}"
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
              placeholder="{{ match ($type ?? 'hizmet') { 'urun' => 'Ürününü anlat: malzemeler, boyut/ağırlık, teslimat & kargo, hazırlık süresi...', 'emlak' => 'Evi anlat: konum/ulaşım, ısıtma, bina yaşı, çevre olanakları, kiralama koşulları...', 'vasita' => 'Aracı anlat: bakım geçmişi, hasar durumu, ekstralar, satış/kiralama koşulları...', default => 'Sunduğun hizmeti detaylıca anlat: deneyimin, neler yaptığın, nasıl çalıştığın...' } }}"
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

    {{-- Faz M5: opsiyonel boyutlar → ilan sayfasında "Boyut karşılaştır" görseli
         (170cm insan silüetiyle ölçekli kıyas). Özellikle mobilya/beyaz eşya
         gibi büyük ürünlerde alıcı ölçek hissi kazanır. --}}
    <div>
        <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">Boyut <span class="text-stone-400 dark:text-stone-500">(ops., cm — girersen ilanda ölçek karşılaştırması gösterilir)</span></span>
        <div class="mt-1 grid gap-4 sm:grid-cols-2 sm:max-w-md">
            <div>
                <label for="width_cm" class="block text-xs text-stone-500 dark:text-stone-400">Genişlik (cm)</label>
                <input id="width_cm" name="width_cm" type="number" min="1" max="2000" value="{{ old('width_cm', $listing?->width_cm) }}"
                       placeholder="ör. 120" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('width_cm') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="height_cm" class="block text-xs text-stone-500 dark:text-stone-400">Yükseklik (cm)</label>
                <input id="height_cm" name="height_cm" type="number" min="1" max="2000" value="{{ old('height_cm', $listing?->height_cm) }}"
                       placeholder="ör. 75" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('height_cm') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>
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
@elseif (($type ?? 'hizmet') === 'vasita')
    @php
        $vehicle = $listing?->vehicleDetail;
        $vBadgesVal = (array) old('badges', $vehicle?->badges ?? []);
    @endphp

    <fieldset class="rounded-xl border border-stone-200 p-4 dark:border-stone-800">
        <legend class="px-1 text-sm font-semibold text-stone-700 dark:text-stone-300">🚗 Araç detayları</legend>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="brand" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Marka</label>
                <input id="brand" name="brand" type="text" maxlength="60" value="{{ old('brand', $vehicle?->brand) }}"
                       placeholder="ör. Volkswagen" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('brand') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="model" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Model</label>
                <input id="model" name="model" type="text" maxlength="60" value="{{ old('model', $vehicle?->model) }}"
                       placeholder="ör. Golf 1.6 TDI" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('model') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="year" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Model yılı</label>
                <input id="year" name="year" type="number" min="1950" max="{{ now()->year + 1 }}" value="{{ old('year', $vehicle?->year) }}"
                       placeholder="ör. 2019" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('year') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
                <label for="mileage_km" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Kilometre</label>
                <input id="mileage_km" name="mileage_km" type="number" min="0" max="2000000" value="{{ old('mileage_km', $vehicle?->mileage_km) }}"
                       placeholder="ör. 85000" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('mileage_km') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="fuel" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Yakıt</label>
                <select id="fuel" name="fuel" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="">Seç...</option>
                    @foreach (\App\Models\ListingVehicleDetail::FUELS as $key => $label)
                        <option value="{{ $key }}" @selected(old('fuel', $vehicle?->fuel) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('fuel') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="transmission" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Vites</label>
                <select id="transmission" name="transmission" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="">Seç...</option>
                    @foreach (\App\Models\ListingVehicleDetail::TRANSMISSIONS as $key => $label)
                        <option value="{{ $key }}" @selected(old('transmission', $vehicle?->transmission) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('transmission') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="body_type" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Kasa tipi <span class="text-stone-400 dark:text-stone-500">(ops.)</span></label>
                <select id="body_type" name="body_type" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="">Seç...</option>
                    @foreach (\App\Models\ListingVehicleDetail::BODY_TYPES as $key => $label)
                        <option value="{{ $key }}" @selected(old('body_type', $vehicle?->body_type) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('body_type') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="color" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Renk <span class="text-stone-400 dark:text-stone-500">(ops.)</span></label>
                <input id="color" name="color" type="text" maxlength="30" value="{{ old('color', $vehicle?->color) }}"
                       placeholder="ör. Gri" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('color') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
                <label for="min_rental_days" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Min. kiralama (gün) <span class="text-stone-400 dark:text-stone-500">(kiralık, ops.)</span></label>
                <input id="min_rental_days" name="min_rental_days" type="number" min="1" max="365" value="{{ old('min_rental_days', $vehicle?->min_rental_days) }}"
                       placeholder="ör. 3" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('min_rental_days') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="deposit" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Depozito <span class="text-stone-400 dark:text-stone-500">(kiralık, ops.)</span></label>
                <input id="deposit" name="deposit" type="number" step="0.01" min="0" value="{{ old('deposit', $vehicle?->deposit) }}"
                       placeholder="ör. 300" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('deposit') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="km_limit_per_day" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Günlük km sınırı <span class="text-stone-400 dark:text-stone-500">(kiralık, ops.)</span></label>
                <input id="km_limit_per_day" name="km_limit_per_day" type="number" min="0" max="10000" value="{{ old('km_limit_per_day', $vehicle?->km_limit_per_day) }}"
                       placeholder="Boş = sınırsız" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                @error('km_limit_per_day') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4">
            <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">Öne çıkan özellikler <span class="text-stone-400 dark:text-stone-500">(işaretlediklerin ilanında rozet olur)</span></span>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @foreach (\App\Models\ListingVehicleDetail::BADGES as $key => $label)
                    <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                        <input type="checkbox" name="badges[]" value="{{ $key }}" @checked(in_array($key, $vBadgesVal, true))
                               class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('badges') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>
    </fieldset>
@endif
