{{-- Nisoya Spor & Halı Saha Bölümü (Ana Sayfa) --}}
@if (\App\Support\HomeSections::visible('spor') && isset($spor) && $spor !== null)
    @php
        $sehirSlug = \Illuminate\Support\Str::slug($spor['sehir']);
        $hasStats = ($spor['istatistikler']['takim'] > 0 || $spor['istatistikler']['oyuncu'] > 0);
    @endphp

    <section class="mx-auto max-w-6xl px-4 py-8 sm:py-12" x-data x-reveal>
        <div class="relative overflow-hidden rounded-3xl border border-stone-200/80 bg-gradient-to-br from-emerald-950 via-stone-900 to-stone-950 p-6 text-white shadow-xl sm:p-10 dark:border-stone-800">
            {{-- Arka plan futbol sahası ambiyansı --}}
            <div class="pointer-events-none absolute -right-16 -top-16 h-72 w-72 rounded-full bg-emerald-500/15 blur-3xl" aria-hidden="true"></div>

            <div class="relative z-10">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-400">
                            <span>⚽</span> Nisoya Halı Saha & Spor Topluluğu
                        </div>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl lg:text-4xl">
                            {{ $spor['sehir'] }} Türk Futbol Ağı
                        </h2>
                        <p class="mt-1 max-w-xl text-sm sm:text-base text-stone-300">
                            Şehrindeki Türklerle takımını kur, maçını organize et, oyuncu havuzuna katıl ve haftanın MVP'si ol!
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('football.city', $sehirSlug) }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white shadow-brand transition hover:bg-emerald-800 active:scale-95">
                            <span>⚽</span>
                            <span>{{ $spor['sehir'] }} Futbol Hub'ı</span>
                            <span aria-hidden="true">→</span>
                        </a>
                        <a href="{{ route('football.teams.create') }}"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                            <span>+</span>
                            <span>Takımını Kur</span>
                        </a>
                    </div>
                </div>

                {{-- İstatistikler / Canlı Hub Sayaçları --}}
                <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl bg-white/5 p-4 backdrop-blur ring-1 ring-white/10 transition hover:bg-white/10">
                        <p class="text-2xs font-semibold uppercase tracking-wider text-stone-300">Aktif Takımlar</p>
                        <p class="mt-1 text-2xl font-extrabold text-white">
                            {{ $spor['istatistikler']['takim'] > 0 ? $spor['istatistikler']['takim'] : '1. Ligi Başlat' }}
                        </p>
                        <p class="mt-0.5 text-3xs text-stone-300">Takımını kur, listeye gir</p>
                    </div>
                    <div class="rounded-2xl bg-white/5 p-4 backdrop-blur ring-1 ring-white/10 transition hover:bg-white/10">
                        <p class="text-2xs font-semibold uppercase tracking-wider text-stone-300">Doğrulanmış Maçlar</p>
                        <p class="mt-1 text-2xl font-extrabold text-emerald-400">
                            {{ $spor['istatistikler']['mac'] > 0 ? $spor['istatistikler']['mac'] : 'Haftalık Maçlar' }}
                        </p>
                        <p class="mt-0.5 text-3xs text-stone-300">Skorlar topluluk onaylı</p>
                    </div>
                    <div class="rounded-2xl bg-white/5 p-4 backdrop-blur ring-1 ring-white/10 transition hover:bg-white/10">
                        <p class="text-2xs font-semibold uppercase tracking-wider text-stone-300">Kayıtlı Sahalar</p>
                        <p class="mt-1 text-2xl font-extrabold text-white">
                            {{ $spor['istatistikler']['saha'] > 0 ? $spor['istatistikler']['saha'] : 'Avrupa Genelinde' }}
                        </p>
                        <p class="mt-0.5 text-3xs text-stone-300">Konuma göre saha bul</p>
                    </div>
                    <div class="rounded-2xl bg-white/5 p-4 backdrop-blur ring-1 ring-white/10 transition hover:bg-white/10">
                        <p class="text-2xs font-semibold uppercase tracking-wider text-stone-300">Oyuncu Havuzu</p>
                        <p class="mt-1 text-2xl font-extrabold text-amber-400">
                            {{ $spor['istatistikler']['oyuncu'] > 0 ? $spor['istatistikler']['oyuncu'] : 'Transfer Pazarı' }}
                        </p>
                        <p class="mt-0.5 text-3xs text-stone-300">Eksik oyuncu ara veya katıl</p>
                    </div>
                </div>

                {{-- Son Maçlar / Maç Vitrini --}}
                @if ($spor['maclar']->isNotEmpty())
                    <div class="mt-8 border-t border-white/10 pt-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-400">
                                ⚡ Son Doğrulanan Maç Skorları
                            </h3>
                            <a href="{{ route('football.league', $sehirSlug) }}" class="text-xs font-semibold text-stone-300 transition hover:text-white">
                                Şehir Puan Durumu →
                            </a>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($spor['maclar'] as $match)
                                <a href="{{ route('football.matches.show', ['city' => \Illuminate\Support\Str::slug($match->city), 'match' => $match->id]) }}"
                                   class="group flex items-center justify-between rounded-2xl bg-stone-900/90 p-4 ring-1 ring-white/10 transition hover:bg-stone-800 hover:ring-emerald-500/50">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="truncate font-bold text-white group-hover:text-emerald-400">
                                                {{ $match->homeTeam?->name ?: 'Ev Sahibi' }}
                                            </span>
                                        </div>
                                        <div class="mt-1 flex items-center gap-2">
                                            <span class="truncate font-medium text-stone-300">
                                                {{ $match->awayTeam?->name ?: 'Deplasman' }}
                                            </span>
                                        </div>
                                        <p class="mt-2 text-2xs text-stone-300">
                                            📍 {{ $match->venueDisplay() }} · {{ $match->match_date->translatedFormat('d M H:i') }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-center justify-center rounded-xl bg-stone-950 px-4 py-2 ring-1 ring-white/10">
                                        <span class="text-lg font-bold text-emerald-400">
                                            {{ $match->home_score }} - {{ $match->away_score }}
                                        </span>
                                        <span class="text-3xs font-semibold uppercase text-stone-300">Bitti</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    {{-- Boş Durum Yerine Teşvik Edici Topluluk Kartları --}}
                    <div class="mt-8 border-t border-white/10 pt-6">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-400">
                                🚀 Popüler Diaspora Futbol Şehirleri
                            </h3>
                            <a href="{{ route('football.index') }}" class="text-xs font-semibold text-stone-300 transition hover:text-white">
                                Tüm Şehirleri Gör →
                            </a>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @php
                                $populerSehirler = [
                                    ['ad' => 'Berlin', 'ulke' => '🇩🇪 Almanya'],
                                    ['ad' => 'Frankfurt', 'ulke' => '🇩🇪 Almanya'],
                                    ['ad' => 'Köln', 'ulke' => '🇩🇪 Almanya'],
                                    ['ad' => 'Amsterdam', 'ulke' => '🇳🇱 Hollanda'],
                                    ['ad' => 'Rotterdam', 'ulke' => '🇳🇱 Hollanda'],
                                    ['ad' => 'Londra', 'ulke' => '🇬🇧 İngiltere'],
                                    ['ad' => 'Viyana', 'ulke' => '🇦🇹 Avusturya'],
                                    ['ad' => 'Brüksel', 'ulke' => '🇧🇪 Belçika'],
                                ];
                            @endphp
                            @foreach ($populerSehirler as $sehirItem)
                                <a href="{{ route('football.city', \Illuminate\Support\Str::slug($sehirItem['ad'])) }}"
                                   class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3.5 py-2 text-xs font-semibold text-white backdrop-blur transition hover:bg-emerald-700/80 hover:ring-1 hover:ring-emerald-400">
                                    <span>⚽ {{ $sehirItem['ad'] }}</span>
                                    <span class="text-3xs text-stone-300 font-normal">({{ $sehirItem['ulke'] }})</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
