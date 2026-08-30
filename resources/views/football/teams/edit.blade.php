<x-layouts.app>
    <div class="mx-auto max-w-2xl px-4 py-8">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
            <div class="border-b border-stone-100 pb-4 dark:border-stone-800">
                <a href="{{ route('football.teams.show', ['city' => \Illuminate\Support\Str::slug($team->city), 'team' => $team->slug]) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    ← Takım Profiline Dön
                </a>
                <h1 class="mt-1 text-2xl font-extrabold text-stone-900 dark:text-stone-100">
                    Takım Bilgilerini Düzenle
                </h1>
            </div>

            <form method="POST" action="{{ route('football.teams.update', $team) }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

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
                    <input type="text" name="name" value="{{ old('name', $team->name) }}" required
                           class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>

                {{-- Şehir & Ülke --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Şehir *</label>
                        <input type="text" name="city" value="{{ old('city', $team->city) }}" required
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Ülke *</label>
                        <select name="country_code" required class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            @foreach ($countries as $country)
                                <option value="{{ $country->code }}" @selected(old('country_code', $team->country_code) === $country->code)>
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
                        <option value="baslangic" @selected(old('level', $team->level->value) === 'baslangic')>🟢 Başlangıç (Hobi / Keyif)</option>
                        <option value="orta" @selected(old('level', $team->level->value) === 'orta')>🔵 Orta (Düzenli oynayanlar)</option>
                        <option value="iyi" @selected(old('level', $team->level->value) === 'iyi')>🟣 İyi (Tempolu / Teknik)</option>
                        <option value="ileri" @selected(old('level', $team->level->value) === 'ileri')>🔴 İleri (Eski lisanslı / Turnuva takımı)</option>
                    </select>
                </div>

                {{-- Forma Renkleri --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Birincil Forma Rengi</label>
                        <input type="text" name="primary_kit_color" value="{{ old('primary_kit_color', $team->primary_kit_color) }}"
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">İkincil Forma Rengi</label>
                        <input type="text" name="secondary_kit_color" value="{{ old('secondary_kit_color', $team->secondary_kit_color) }}"
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                </div>

                {{-- Logo Güncelleme --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Yeni Logo Yükle</label>
                    <input type="file" name="logo" accept="image/*"
                           class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-xs focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>

                {{-- Açıklama --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Takım Açıklaması</label>
                    <textarea name="description" rows="3"
                              class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('description', $team->description) }}</textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-2xl bg-emerald-600 py-3.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                        Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
