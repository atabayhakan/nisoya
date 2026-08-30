<x-layouts.app>
    <div class="mx-auto max-w-2xl px-4 py-8">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
            <div class="border-b border-stone-100 pb-4 dark:border-stone-800">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">⚽ Yeni Takım</span>
                <h1 class="mt-1 text-2xl font-bold text-stone-900 dark:text-stone-100">
                    Halı Saha Takımını Kur
                </h1>
                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">
                    Takımını oluşturduktan sonra sen otomatik olarak Kaptan olursun ve arkadaşlarını kadroya davet edebilirsin.
                </p>
            </div>

            <form method="POST" action="{{ route('football.teams.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                @csrf
                <x-honeypot />

                @if ($errors->any())
                    <div class="rounded-2xl bg-rose-50 p-4 text-xs text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Takım Adı --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Takım Adı *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Örn: Berlin Hilalspor, Kreuzberg United"
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
                        <select name="country_code" required class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            @foreach ($countries as $country)
                                <option value="{{ $country->code }}" @selected(old('country_code', $defaultCountry) === $country->code)>
                                    {{ $country->emoji }} {{ $country->name_tr }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Takım Seviyesi --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Takım Seviyesi *</label>
                    <select name="level" required class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        <option value="baslangic" @selected(old('level') === 'baslangic')>🟢 Başlangıç (Hobi / Keyif)</option>
                        <option value="orta" @selected(old('level', 'orta') === 'orta')>🔵 Orta (Düzenli oynayanlar)</option>
                        <option value="iyi" @selected(old('level') === 'iyi')>🟣 İyi (Tempolu / Teknik)</option>
                        <option value="ileri" @selected(old('level') === 'ileri')>🔴 İleri (Eski lisanslı / Turnuva takımı)</option>
                    </select>
                </div>

                {{-- Forma Renkleri --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Birincil Forma Rengi</label>
                        <input type="text" name="primary_kit_color" value="{{ old('primary_kit_color') }}" placeholder="Örn: Kırmızı, Beyaz"
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">İkincil Forma Rengi</label>
                        <input type="text" name="secondary_kit_color" value="{{ old('secondary_kit_color') }}" placeholder="Örn: Siyah, Mavi"
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                </div>

                {{-- Logo Yükleme --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Takım Logosu (Opsiyonel)</label>
                    <input type="file" name="logo" accept="image/*"
                           class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-xs focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>

                {{-- Açıklama --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Takım Açıklaması / Tanıtım</label>
                    <textarea name="description" rows="3" placeholder="Takımınız hakkında kısa bilgi, kuruluş hikayesi veya maç günleri..."
                              class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('description') }}</textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-2xl bg-emerald-700 py-3.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                        🏆 Takımı Kur & Kaptan Ol
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
