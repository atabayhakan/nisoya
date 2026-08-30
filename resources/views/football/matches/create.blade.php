<x-layouts.app>
    <div class="mx-auto max-w-2xl px-4 py-8">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
            <div class="border-b border-stone-100 pb-4 dark:border-stone-800">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">⚽ Halı Saha Maçı</span>
                <h1 class="mt-1 text-2xl font-extrabold text-stone-900 dark:text-stone-100">
                    Yeni Maç Planla / Teklif Et
                </h1>
                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">
                    Kaptanı olduğun takım adına bir rakip seç veya açık maç ilanı bırak.
                </p>
            </div>

            <form method="POST" action="{{ route('football.matches.store') }}" class="mt-6 space-y-5">
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

                {{-- Takımım (Ev Sahibi) --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Takımınız (Ev Sahibi) *</label>
                    <select name="home_team_id" required class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        @foreach ($myTeams as $t)
                            <option value="{{ $t->id }}" @selected(old('home_team_id') == $t->id)>{{ $t->name }} ({{ $t->city }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Rakip Takım --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Rakip Takım (Opsiyonel)</label>
                    <select name="away_team_id" class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        <option value="">Açık Maç (Rakip Sonradan Katılsın)</option>
                        @foreach ($opponentTeams as $opp)
                            <option value="{{ $opp->id }}" @selected(old('away_team_id', request('away_team_id')) == $opp->id)>
                                {{ $opp->name }} (Kaptan: {{ $opp->captain?->name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Halı Saha --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Kayıtlı Halı Saha</label>
                        <select name="venue_id" class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <option value="">Saha Seçin veya Aşağıya Yazın</option>
                            @foreach ($venues as $v)
                                <option value="{{ $v->id }}" @selected(old('venue_id') == $v->id)>{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Özel Saha / Konum Adı</label>
                        <input type="text" name="venue_custom_name" value="{{ old('venue_custom_name') }}" placeholder="Örn: Kreuzberg Belediye Sahası"
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                </div>

                {{-- Maç Günü ve Saati --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Maç Tarihi ve Saati *</label>
                    <input type="datetime-local" name="match_date" value="{{ old('match_date') }}" required
                           class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>

                {{-- Notlar --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Maç Açıklaması / Notlar</label>
                    <textarea name="description" rows="2" placeholder="Örn: 7'ye 7 maç, yelekler bizden, ücret yarı yarıya..."
                              class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('description') }}</textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-2xl bg-emerald-600 py-3.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                        ⚽ Maçı Planla & Teklif Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
