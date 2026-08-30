{{-- Nisoya Spor & Halı Saha Bölümü (Ana Sayfa) --}}
@if (\App\Support\HomeSections::visible('spor') && isset($spor) && $spor !== null)
    <section class="mx-auto max-w-6xl px-4 py-8" x-data x-reveal>
        <div class="rounded-3xl border border-stone-200 bg-gradient-to-br from-emerald-950 via-stone-900 to-stone-950 p-6 text-white shadow-xl sm:p-8 dark:border-stone-800">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-400">
                        <span>⚽</span> Nisoya Spor
                    </span>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                        {{ $spor['sehir'] }} Halı Saha Topluluğu
                    </h2>
                    <p class="mt-1 text-sm text-stone-300">
                        Şehrindeki Türklerle takımını kur, maçını yap, skorunu paylaş.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('football.city', \Illuminate\Support\Str::slug($spor['sehir'])) }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-emerald-500">
                        <span>⚽</span> Şehrin Futbol Hub'ı
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="{{ route('football.teams.create') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                        + Takımını Kur
                    </a>
                </div>
            </div>

            {{-- Canlı Şehir Sayaçları --}}
            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl bg-white/5 p-3.5 backdrop-blur ring-1 ring-white/10">
                    <p class="text-2xs font-semibold uppercase text-stone-500">Aktif Takım</p>
                    <p class="mt-1 text-xl font-bold text-white">{{ $spor['istatistikler']['takim'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/5 p-3.5 backdrop-blur ring-1 ring-white/10">
                    <p class="text-2xs font-semibold uppercase text-stone-500">Doğrulanmış Maç</p>
                    <p class="mt-1 text-xl font-bold text-emerald-400">{{ $spor['istatistikler']['mac'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/5 p-3.5 backdrop-blur ring-1 ring-white/10">
                    <p class="text-2xs font-semibold uppercase text-stone-500">Kayıtlı Saha</p>
                    <p class="mt-1 text-xl font-bold text-white">{{ $spor['istatistikler']['saha'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/5 p-3.5 backdrop-blur ring-1 ring-white/10">
                    <p class="text-2xs font-semibold uppercase text-stone-500">Oyuncu Havuzu</p>
                    <p class="mt-1 text-xl font-bold text-amber-400">{{ $spor['istatistikler']['oyuncu'] }}</p>
                </div>
            </div>

            {{-- Son Maçlar / Haftanın Maçı Skor Kartları --}}
            @if ($spor['maclar']->isNotEmpty())
                <div class="mt-6 border-t border-white/10 pt-6">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-emerald-400">
                            Son Doğrulanan Maç Skorları
                        </h3>
                        <a href="{{ route('football.league', \Illuminate\Support\Str::slug($spor['sehir'])) }}" class="text-xs font-medium text-stone-500 hover:text-white">
                            Şehir Puan Tablosu →
                        </a>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-2">
                        @foreach ($spor['maclar'] as $match)
                            <a href="{{ route('football.matches.show', ['city' => \Illuminate\Support\Str::slug($match->city), 'match' => $match->id]) }}"
                               class="group flex items-center justify-between rounded-2xl bg-stone-900/90 p-4 ring-1 ring-white/10 transition hover:bg-stone-800 hover:ring-emerald-500/50">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate font-semibold text-white group-hover:text-emerald-400">
                                            {{ $match->homeTeam?->name ?: 'Ev Sahibi' }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="truncate font-semibold text-stone-300">
                                            {{ $match->awayTeam?->name ?: 'Deplasman' }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-2xs text-stone-500">
                                        📍 {{ $match->venueDisplay() }} · {{ $match->match_date->translatedFormat('d M H:i') }}
                                    </p>
                                </div>
                                <div class="flex flex-col items-center justify-center rounded-xl bg-stone-950 px-4 py-2 ring-1 ring-white/10">
                                    <span class="text-lg font-bold text-emerald-400">
                                        {{ $match->home_score }} - {{ $match->away_score }}
                                    </span>
                                    <span class="text-3xs font-semibold uppercase text-stone-500">Bitti</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif
