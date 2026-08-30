<x-layouts.app>
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('football.city', \Illuminate\Support\Str::slug($currentCity)) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    ← {{ $currentCity }} Futbol Ana Sayfası
                </a>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl dark:text-stone-100">
                    {{ $currentCity }} Maç Takvimi & Skorlar
                </h1>
                <p class="text-sm text-stone-600 dark:text-stone-400">
                    Planlanan halı saha randevuları ve tamamlanmış doğrulanmış maç sonuçları.
                </p>
            </div>
            <a href="{{ route('football.matches.create') }}"
               class="inline-flex items-center gap-2 rounded-2xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                + Yeni Maç Planla
            </a>
        </div>

        {{-- Sekmeler --}}
        <div class="mt-6 flex border-b border-stone-200 dark:border-stone-800">
            <a href="{{ route('football.matches.index', ['city' => \Illuminate\Support\Str::slug($currentCity), 'tab' => 'upcoming']) }}"
               class="border-b-2 px-5 py-3 text-sm font-bold transition {{ $currentTab === 'upcoming' ? 'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-400' : 'border-transparent text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200' }}">
                📅 Gelecek & Planlanan Maçlar
            </a>
            <a href="{{ route('football.matches.index', ['city' => \Illuminate\Support\Str::slug($currentCity), 'tab' => 'played']) }}"
               class="border-b-2 px-5 py-3 text-sm font-bold transition {{ $currentTab === 'played' ? 'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-400' : 'border-transparent text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200' }}">
                🏆 Oynanmış & Doğrulanmış Maçlar
            </a>
        </div>

        @if ($matches->isEmpty())
            <div class="mt-8 rounded-3xl border border-dashed border-stone-200 p-12 text-center text-stone-500 dark:border-stone-800 dark:text-stone-400">
                <p class="text-base font-semibold">Bu kategoride henüz maç bulunmuyor.</p>
                <a href="{{ route('football.matches.create') }}" class="mt-3 inline-block font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    İlk maçı sen planla!
                </a>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($matches as $match)
                    <div class="flex flex-col justify-between rounded-3xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-3xs font-bold text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                    {{ $match->status->getLabel() }}
                                </span>
                                <span class="text-2xs text-stone-500 font-medium">
                                    {{ $match->match_date->translatedFormat('d M H:i') }}
                                </span>
                            </div>

                            {{-- Karşılaşma Blok --}}
                            <div class="mt-4 flex items-center justify-between gap-2">
                                <div class="flex-1 text-left min-w-0">
                                    <p class="font-bold text-sm text-stone-900 truncate dark:text-stone-100">
                                        {{ $match->homeTeam?->name }}
                                    </p>
                                </div>
                                <div class="px-3 py-1 rounded-xl bg-stone-900 text-white font-bold text-sm dark:bg-stone-700">
                                    @if ($match->home_score !== null && $match->away_score !== null)
                                        {{ $match->home_score }} - {{ $match->away_score }}
                                    @else
                                        vs
                                    @endif
                                </div>
                                <div class="flex-1 text-right min-w-0">
                                    <p class="font-bold text-sm text-stone-900 truncate dark:text-stone-100">
                                        {{ $match->awayTeam?->name ?: 'Rakip Aranıyor' }}
                                    </p>
                                </div>
                            </div>

                            <p class="mt-4 text-xs text-stone-500 dark:text-stone-400">
                                📍 {{ $match->venueDisplay() }}
                            </p>
                        </div>

                        <div class="mt-5 border-t border-stone-100 pt-3 dark:border-stone-800">
                            <a href="{{ route('football.matches.show', ['city' => \Illuminate\Support\Str::slug($match->city), 'match' => $match->id]) }}"
                               class="flex w-full items-center justify-center rounded-xl bg-stone-50 py-2 text-xs font-bold text-stone-800 transition hover:bg-emerald-50 hover:text-emerald-800 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300">
                                Maç Detayı / Haber →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $matches->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
