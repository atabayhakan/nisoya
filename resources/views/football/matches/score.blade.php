<x-layouts.app>
    <div class="mx-auto max-w-2xl px-4 py-8">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
            <div class="border-b border-stone-100 pb-4 dark:border-stone-800">
                <a href="{{ route('football.matches.show', ['city' => \Illuminate\Support\Str::slug($match->city), 'match' => $match->id]) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    ← Maç Detayına Dön
                </a>
                <h1 class="mt-1 text-2xl font-bold text-stone-900 dark:text-stone-100">
                    Maç Skorunu ve İstatistiklerini Gir
                </h1>
                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">
                    Skoru girdiğinizde rakip kaptana onay bildirimi gönderilir ve otomatik editoryal spor haberi oluşturulur.
                </p>
            </div>

            <form method="POST" action="{{ route('football.matches.score-submit', $match) }}" class="mt-6 space-y-6">
                @csrf

                @if ($errors->any())
                    <div class="rounded-2xl bg-rose-50 p-4 text-xs text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Skor Giriş Kutuları --}}
                <div class="rounded-2xl bg-stone-50 p-6 dark:bg-stone-800/60">
                    <div class="grid grid-cols-3 items-center gap-4 text-center">
                        <div>
                            <p class="font-bold text-sm text-stone-900 dark:text-stone-100 truncate">{{ $match->homeTeam?->name }}</p>
                            <span class="text-3xs uppercase text-stone-500">Ev Sahibi</span>
                            <input type="number" name="home_score" value="{{ old('home_score', $match->home_score ?? 0) }}" min="0" max="50" required
                                   class="mt-2 w-20 mx-auto text-center font-bold text-2xl rounded-2xl border border-stone-200 bg-white p-3 focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-100">
                        </div>

                        <div class="font-bold text-xl text-stone-500">
                            -
                        </div>

                        <div>
                            <p class="font-bold text-sm text-stone-900 dark:text-stone-100 truncate">{{ $match->awayTeam?->name ?: 'Deplasman' }}</p>
                            <span class="text-3xs uppercase text-stone-500">Deplasman</span>
                            <input type="number" name="away_score" value="{{ old('away_score', $match->away_score ?? 0) }}" min="0" max="50" required
                                   class="mt-2 w-20 mx-auto text-center font-bold text-2xl rounded-2xl border border-stone-200 bg-white p-3 focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-100">
                        </div>
                    </div>
                </div>

                {{-- Maçın Adamı (MVP) --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">⭐ Maçın Adamı (MVP - Opsiyonel)</label>
                    <select name="mvp_player_id" class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        <option value="">Seçilmedi</option>
                        <optgroup label="{{ $match->homeTeam?->name }}">
                            @foreach ($match->homeTeam?->activeMembers ?? [] as $m)
                                <option value="{{ $m->user_id }}" @selected(old('mvp_player_id', $match->mvp_player_id) == $m->user_id)>
                                    {{ $m->user->name }}
                                </option>
                            @endforeach
                        </optgroup>
                        @if ($match->awayTeam)
                            <optgroup label="{{ $match->awayTeam->name }}">
                                @foreach ($match->awayTeam->activeMembers ?? [] as $m)
                                    <option value="{{ $m->user_id }}" @selected(old('mvp_player_id', $match->mvp_player_id) == $m->user_id)>
                                        {{ $m->user->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-2xl bg-emerald-700 py-3.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                        ⚽ Skoru Kaydet & Rakip Kaptana Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
