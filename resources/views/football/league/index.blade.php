<x-layouts.app>
    <div class="mx-auto max-w-6xl px-4 py-8">
        {{-- Başlık --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('football.city', \Illuminate\Support\Str::slug($currentCity)) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    ← {{ $currentCity }} Futbol Ana Sayfası
                </a>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl dark:text-stone-100">
                    🏆 {{ $currentCity }} Halı Saha Ligi & Puan Durumu
                </h1>
                <p class="text-sm text-stone-600 dark:text-stone-400">
                    Yalnızca karşılıklı iki kaptan tarafından doğrulanmış maç sonuçlarına göre hesaplanan resmî lig sıralaması.
                </p>
            </div>
            <a href="{{ route('football.matches.create') }}"
               class="inline-flex items-center gap-2 rounded-2xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                + Yeni Maç Yap
            </a>
        </div>

        {{-- Puan Tablosu --}}
        <div class="mt-8 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-stone-800 dark:bg-stone-900">
            @if ($standings->isEmpty())
                <div class="p-12 text-center text-stone-500 dark:text-stone-400">
                    <span class="text-4xl">⚽</span>
                    <p class="mt-3 text-base font-semibold">{{ $currentCity }} şehrinde henüz doğrulanmış bir maç bulunmuyor.</p>
                    <a href="{{ route('football.matches.create') }}" class="mt-2 inline-block font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                        İlk maçı planlayın ve ligi başlatın!
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-stone-50 text-xs uppercase font-bold text-stone-500 dark:bg-stone-800/60 dark:text-stone-400">
                            <tr>
                                <th class="px-5 py-3.5 text-center w-12">#</th>
                                <th class="px-5 py-3.5">Takım</th>
                                <th class="px-4 py-3.5 text-center">O</th>
                                <th class="px-4 py-3.5 text-center">G</th>
                                <th class="px-4 py-3.5 text-center">B</th>
                                <th class="px-4 py-3.5 text-center">M</th>
                                <th class="px-4 py-3.5 text-center">AG</th>
                                <th class="px-4 py-3.5 text-center">YG</th>
                                <th class="px-4 py-3.5 text-center">AV</th>
                                <th class="px-4 py-3.5 text-center">Form</th>
                                <th class="px-6 py-3.5 text-right font-bold">Puan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                            @foreach ($standings as $row)
                                <tr class="transition hover:bg-stone-50/80 dark:hover:bg-stone-800/40 {{ $row['rank'] === 1 ? 'bg-amber-50/30 dark:bg-amber-950/10' : '' }}">
                                    <td class="px-5 py-4 text-center font-bold {{ $row['rank'] <= 3 ? 'text-amber-600' : 'text-stone-500' }}">
                                        {{ $row['rank'] }}
                                    </td>
                                    <td class="px-5 py-4 font-bold text-stone-900 dark:text-stone-100">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-xs font-bold text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                                @if ($row['team']->logo_path)
                                                    <img src="{{ Storage::url($row['team']->logo_path) }}" alt="{{ $row['team']->name }}" class="h-full w-full rounded-xl object-cover">
                                                @else
                                                    ⚽
                                                @endif
                                            </div>
                                            <a href="{{ route('football.teams.show', ['city' => \Illuminate\Support\Str::slug($row['team']->city), 'team' => $row['team']->slug]) }}"
                                               class="hover:text-emerald-700 dark:hover:text-emerald-400">
                                                {{ $row['team']->name }}
                                            </a>
                                            @if ($row['team']->is_verified)
                                                <span title="Doğrulanmış Takım" class="text-emerald-700 text-xs">✓</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center text-stone-600 dark:text-stone-300">{{ $row['played'] }}</td>
                                    <td class="px-4 py-4 text-center font-semibold text-emerald-700">{{ $row['won'] }}</td>
                                    <td class="px-4 py-4 text-center font-semibold text-amber-600">{{ $row['drawn'] }}</td>
                                    <td class="px-4 py-4 text-center font-semibold text-rose-600">{{ $row['lost'] }}</td>
                                    <td class="px-4 py-4 text-center text-stone-600 dark:text-stone-400">{{ $row['goals_for'] }}</td>
                                    <td class="px-4 py-4 text-center text-stone-600 dark:text-stone-400">{{ $row['goals_against'] }}</td>
                                    <td class="px-4 py-4 text-center font-bold {{ $row['goal_diff'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                        {{ $row['goal_diff'] > 0 ? '+'.$row['goal_diff'] : $row['goal_diff'] }}
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            @foreach ($row['form'] as $f)
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-3xs font-bold text-white {{ $f === 'G' ? 'bg-emerald-700' : ($f === 'B' ? 'bg-amber-500' : 'bg-rose-600') }}">
                                                    {{ $f }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-base text-emerald-700 dark:text-emerald-400">
                                        {{ $row['points'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Son Doğrulanmış Karşılaşmalar Akışı --}}
        @if ($recentMatches->isNotEmpty())
            <div class="mt-10">
                <h2 class="text-lg font-bold text-stone-900 dark:text-stone-100">
                    Son Doğrulanmış Maç Sonuçları
                </h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($recentMatches as $m)
                        <a href="{{ route('football.matches.show', ['city' => \Illuminate\Support\Str::slug($m->city), 'match' => $m->id]) }}"
                           class="flex items-center justify-between rounded-2xl border border-stone-200 bg-white p-4 shadow-sm transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900">
                            <div>
                                <p class="font-bold text-xs text-stone-900 dark:text-stone-100 truncate">{{ $m->homeTeam?->name }}</p>
                                <p class="mt-1 font-bold text-xs text-stone-900 dark:text-stone-100 truncate">{{ $m->awayTeam?->name }}</p>
                                <p class="mt-2 text-3xs text-stone-500">{{ $m->match_date->translatedFormat('d M Y') }} · {{ $m->venueDisplay() }}</p>
                            </div>
                            <div class="rounded-xl bg-stone-900 px-3 py-1.5 text-center text-white font-bold text-sm dark:bg-stone-700">
                                {{ $m->home_score }} - {{ $m->away_score }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
