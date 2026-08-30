<x-layouts.app>
    <div class="mx-auto max-w-2xl px-4 py-8">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
            <div class="border-b border-stone-100 pb-4 dark:border-stone-800">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">📢 Futbol İlanı</span>
                <h1 class="mt-1 text-2xl font-extrabold text-stone-900 dark:text-stone-100">
                    Oyuncu veya Maç İlanı Aç
                </h1>
                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">
                    Maçınızda eksik oyuncu mu var, yoksa tek başınıza maç yapacak bir grup mu arıyorsunuz?
                </p>
            </div>

            <form method="POST" action="{{ route('football.requests.store') }}" class="mt-6 space-y-5">
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

                {{-- İlan Türü --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">İlan Türü *</label>
                    <select name="type" required class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        <option value="oyuncu_araniyor" @selected(old('type') === 'oyuncu_araniyor')>👥 Takımıma Eksik Oyuncu Arıyorum</option>
                        <option value="mac_ariyorum" @selected(old('type') === 'mac_ariyorum')>⚽ Bireysel Olarak Oynayacak Maç Arıyorum</option>
                    </select>
                </div>

                {{-- Takımım (Varsa) --}}
                @if ($myTeams->isNotEmpty())
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Takımınız (Varsa)</label>
                        <select name="team_id" class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <option value="">Takım Belirtmeden</option>
                            @foreach ($myTeams as $t)
                                <option value="{{ $t->id }}" @selected(old('team_id') == $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

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

                {{-- Maç Zamanı & Saha --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Maç Tarihi & Saati</label>
                        <input type="datetime-local" name="match_time" value="{{ old('match_time') }}"
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Saha / Konum Adı</label>
                        <input type="text" name="venue_name" value="{{ old('venue_name') }}" placeholder="Örn: Soccerworld Kreuzberg"
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                </div>

                {{-- Eksik Sayısı & Seviye --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Eksik Oyuncu Sayısı *</label>
                        <input type="number" name="needed_count" value="{{ old('needed_count', 1) }}" min="1" max="15" required
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Aranan Seviye</label>
                        <select name="level" class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <option value="">Fark Etmez / Tüm Seviyeler</option>
                            <option value="baslangic">🟢 Başlangıç</option>
                            <option value="orta">🔵 Orta</option>
                            <option value="iyi">🟣 İyi</option>
                            <option value="ileri">🔴 İleri</option>
                        </select>
                    </div>
                </div>

                {{-- İlan Açıklaması --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">İlan Notu & Detaylar *</label>
                    <textarea name="description" rows="3" required placeholder="Örn: Bu akşam saat 21:00'de Kreuzberg'de 7v7 maçımız var. Kaleci veya defans arıyoruz. Gelmek isteyenler yazsın..."
                              class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('description') }}</textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-2xl bg-emerald-600 py-3.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                        📢 İlanı Yayınla
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
