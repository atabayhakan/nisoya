@php
    $typeVal = old('type', $event?->type?->value);
    $themeVal = old('theme', $event?->theme);
@endphp

<div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

<div>
    <label for="type" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Etkinlik türü</label>
    <select id="type" name="type" required
            class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
        <option value="">Seç...</option>
        @foreach ($types as $type)
            <option value="{{ $type->value }}" @selected($typeVal === $type->value)>{{ $type->emoji() }} {{ $type->getLabel() }}</option>
        @endforeach
    </select>
    @error('type') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
</div>

<div>
    <label for="title" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Başlık</label>
    <input id="title" name="title" type="text" required maxlength="120" value="{{ old('title', $event?->title) }}"
           placeholder="ör. Ayşe & Mehmet Düğünü"
           class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
    @error('title') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
</div>

<div>
    <label for="starts_at" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Tarih ve saat</label>
    <input id="starts_at" name="starts_at" type="datetime-local" required
           value="{{ old('starts_at', $event?->starts_at?->format('Y-m-d\TH:i')) }}"
           class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:max-w-xs dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
    @error('starts_at') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="venue_name" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Mekan adı <span class="text-stone-600 dark:text-stone-400">(ops.)</span></label>
        <input id="venue_name" name="venue_name" type="text" maxlength="120" value="{{ old('venue_name', $event?->venue_name) }}"
               placeholder="ör. Grand Salon"
               class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
        @error('venue_name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="venue_address" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Adres <span class="text-stone-600 dark:text-stone-400">(ops.)</span></label>
        <input id="venue_address" name="venue_address" type="text" maxlength="255" value="{{ old('venue_address', $event?->venue_address) }}"
               placeholder="ör. Hauptstraße 12, 10827 Berlin"
               class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
        @error('venue_address') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label for="description" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Davet metni <span class="text-stone-600 dark:text-stone-400">(ops.)</span></label>
    <textarea id="description" name="description" rows="4" maxlength="2000"
              placeholder="Misafirlerine iletmek istediğin mesaj: 'Bu mutlu günümüzde sizleri de aramızda görmekten onur duyarız...'"
              class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('description', $event?->description) }}</textarea>
    @error('description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
</div>

<div>
    <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">Tema <span class="text-stone-600 dark:text-stone-400">(boş bırakırsan türe göre seçilir)</span></span>
    <div class="mt-2 grid gap-2 sm:grid-cols-2">
        @foreach ($themes as $key => $theme)
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition {{ $themeVal === $key ? 'border-emerald-500 ring-1 ring-emerald-300 dark:ring-emerald-700' : 'border-stone-200 hover:border-stone-300 dark:border-stone-700' }}">
                <input type="radio" name="theme" value="{{ $key }}" @checked($themeVal === $key)
                       class="border-stone-300 text-emerald-700 focus:ring-emerald-500 dark:border-stone-600">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-lg {{ $theme['page'] }} border {{ explode(' ', $theme['card'])[0] === 'bg-stone-900' ? 'border-stone-700' : 'border-stone-200 dark:border-stone-700' }}">{{ $theme['ornament'] }}</span>
                <span class="text-sm text-stone-700 dark:text-stone-300">{{ $theme['label'] }}</span>
            </label>
        @endforeach
    </div>
    @error('theme') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
</div>
