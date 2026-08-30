<x-layouts.app>
    <div class="mx-auto max-w-2xl px-4 py-8">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
            <div class="border-b border-stone-100 pb-4 dark:border-stone-800">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">📍 Halı Saha Rehberi</span>
                <h1 class="mt-1 text-2xl font-bold text-stone-900 dark:text-stone-100">
                    Yeni Halı Saha Ekle
                </h1>
                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">
                    Bildiğin halı saha veya futbol tesisini ekle, diğer oyuncular keşfetsin ve maç planlasın.
                </p>
            </div>

            <form method="POST" action="{{ route('football.venues.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                @csrf
                @include('partials.honeypot')

                @if ($errors->any())
                    <div class="rounded-2xl bg-rose-50 p-4 text-xs text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Saha Adı --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Saha / Tesis Adı *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Örn: Soccerworld Berlin, Arena Sport Köln"
                           class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>

                {{-- Şehir & Ülke --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Şehir *</label>
                        <input type="text" name="city" value="{{ old('city', $defaultCity) }}" required placeholder="Örn: Berlin, Köln, Frankfurt"
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Ülke *</label>
                        <select name="country_code" required class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            @foreach ($countries as $country)
                                <option value="{{ $country->code }}" @selected(old('country_code', $defaultCountry) === $country->code)>
                                    {{ $country->emoji }} {{ $country->name_tr }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Adres --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Açık Adres *</label>
                    <input type="text" name="address" value="{{ old('address') }}" required placeholder="Örn: Friedrichstraße 123, 10117 Berlin"
                           class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>

                {{-- Saha & Zemin Tipi --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Saha Tipi *</label>
                        <select name="pitch_type" required class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            @foreach ($pitchTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('pitch_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Zemin Türü *</label>
                        <select name="surface_type" required class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            @foreach ($surfaceTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('surface_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- İletişim & Fiyat --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Telefon / Rezervasyon</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Örn: +49 30 1234567"
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Fiyat Bilgisi</label>
                        <input type="text" name="price_info" value="{{ old('price_info') }}" placeholder="Örn: Saatlik 80€ - 100€"
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                </div>

                {{-- Özellikler Checkbox --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Tesis Özellikleri</label>
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($features as $key => $label)
                            <label class="flex items-center gap-2 rounded-xl border border-stone-100 bg-stone-50 p-2.5 text-xs text-stone-800 dark:border-stone-800 dark:bg-stone-800/60 dark:text-stone-200 cursor-pointer">
                                <input type="checkbox" name="features[]" value="{{ $key }}" class="rounded text-emerald-700 focus:ring-emerald-500">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Görsel --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Saha Fotoğrafı (Opsiyonel)</label>
                    <input type="file" name="cover_image" accept="image/*"
                           class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-xs focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-2xl bg-emerald-700 py-3.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                        📍 Halı Sahayı Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
