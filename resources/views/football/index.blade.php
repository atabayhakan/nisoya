<x-layouts.app>
    <div class="mx-auto max-w-6xl px-4 py-8">
        {{-- Üst Başlık & Şehir Seçici --}}
        <div class="rounded-3xl border border-stone-200 bg-gradient-to-r from-emerald-900 via-stone-900 to-emerald-950 p-6 text-white shadow-lg sm:p-10 dark:border-stone-800">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-300">
                            <span>⚽</span> Halı Saha Topluluğu
                        </span>
                        <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs text-stone-300">
                            {{ $currentCity }}
                        </span>
                    </div>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                        {{ $currentCity }}'de Futbol Zamanı
                    </h1>
                    <p class="mt-2 text-base text-stone-300">
                        Şehrindeki Türklerle takımını kur, eksik oyuncu bul, halı sahaları keşfet ve ligde yerini al.
                    </p>
                </div>

                {{-- Hızlı Eylem Butonları --}}
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('football.teams.create') }}"
                       class="inline-flex items-center gap-2 rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                        <span>+</span> Takımını Kur
                    </a>
                    <a href="{{ route('football.venues.index', \Illuminate\Support\Str::slug($currentCity)) }}"
                       class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-amber-400 to-amber-500 px-4 py-3 text-sm font-black text-stone-950 shadow-md transition hover:from-amber-300 hover:to-amber-400">
                        <span>📍</span> En Yakın Halı Sahalar
                    </a>
                    <a href="{{ route('football.requests.create') }}"
                       class="inline-flex items-center gap-2 rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                        <span>👥</span> Oyuncu / Maç Ara
                    </a>
                </div>
            </div>

            {{-- 4 Temel Metrik Kartı --}}
            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                    <p class="text-xs font-semibold text-stone-300">Aktif Takım</p>
                    <p class="mt-1 text-2xl font-bold text-white">{{ $metrics['teams_count'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                    <p class="text-xs font-semibold text-stone-300">Kayıtlı Oyuncu</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-400">{{ $metrics['players_count'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                    <p class="text-xs font-semibold text-stone-300">Halı Saha</p>
                    <p class="mt-1 text-2xl font-bold text-white">{{ $metrics['venues_count'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                    <p class="text-xs font-semibold text-stone-300">Bu Hafta Maç</p>
                    <p class="mt-1 text-2xl font-bold text-amber-400">{{ $metrics['weekly_matches_count'] }}</p>
                </div>
            </div>
        </div>

        {{-- Şehir Değiştirici Haplar --}}
        @if ($cities->isNotEmpty())
            <div class="mt-6 flex items-center gap-2 overflow-x-auto pb-2">
                <span class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Şehirler:</span>
                @foreach ($cities as $c)
                    @php($slug = \Illuminate\Support\Str::slug($c->name))
                    <a href="{{ route('football.city', $slug) }}"
                       class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-medium transition {{ mb_strtolower($currentCity) === mb_strtolower($c->name) ? 'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' : 'bg-stone-100 text-stone-700 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700' }}">
                        {{ $c->name }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- EA Sports FC / Ultimate Team TOTW Showcase --}}
        @if (! empty($totw))
            <div class="mt-8 overflow-hidden rounded-3xl border border-amber-500/30 bg-gradient-to-br from-stone-950 via-stone-900 to-amber-950/40 p-6 text-white shadow-xl sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-amber-500/20 pb-4">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-amber-500/20 px-3 py-1 text-2xs font-extrabold uppercase tracking-widest text-amber-300">
                            <span>⚡ EA FC 26</span>
                            <span>•</span>
                            <span>TEAM OF THE WEEK</span>
                        </div>
                        <h2 class="mt-2 text-2xl font-black tracking-tight text-white sm:text-3xl">
                            {{ $currentCity }} Haftanın Halı Saha 7'si (TOTW)
                        </h2>
                        <p class="mt-1 text-xs text-stone-300">
                            Lig maçlarında gösterdikleri üstün performans ve MVP oylarıyla haftanın altın kartlarına seçilen oyuncular.
                        </p>
                    </div>
                    <span class="rounded-xl border border-amber-400/40 bg-stone-900/80 px-3 py-1.5 text-center text-xs font-black text-amber-300">
                        HAFTA #8
                    </span>
                </div>

                {{-- Futbolcu Kartları Izgarası --}}
                <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($totw as $card)
                        <div class="group relative overflow-hidden rounded-2xl border-2 border-amber-400/80 bg-gradient-to-b from-amber-200/20 via-stone-900 to-stone-950 p-4 text-center shadow-lg transition-all hover:-translate-y-1 hover:border-amber-300 hover:shadow-amber-500/20">
                            {{-- Kart Üstü Reyting & Mevki --}}
                            <div class="flex items-start justify-between">
                                <div class="text-left">
                                    <div class="text-2xl font-black text-amber-300 leading-none">{{ $card['ovr'] }}</div>
                                    <div class="text-[11px] font-black uppercase text-amber-100">{{ $card['position'] }}</div>
                                </div>
                                <span class="rounded-full bg-amber-500/30 px-2 py-0.5 text-[10px] font-bold text-amber-200">
                                    ★ GOLD
                                </span>
                            </div>

                            {{-- Avatar / Silüet --}}
                            <div class="my-2 flex items-center justify-center">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full border-2 border-amber-400/60 bg-stone-800 text-2xl font-black text-amber-300 shadow-inner">
                                    {{ mb_substr($card['name'], 0, 1) }}
                                </div>
                            </div>

                            {{-- Oyuncu & Takım Adı --}}
                            <h3 class="text-sm font-black text-white truncate">{{ $card['name'] }}</h3>
                            <p class="text-2xs font-semibold text-stone-400 truncate">{{ $card['team'] }}</p>

                            {{-- EA FC İstatistik Rozeti --}}
                            <div class="mt-3 grid grid-cols-3 gap-1 border-t border-amber-400/20 pt-2 text-[10px] font-bold">
                                <div><span class="text-stone-400">PAC</span> <span class="text-white">{{ $card['pac'] }}</span></div>
                                <div><span class="text-stone-400">SHO</span> <span class="text-white">{{ $card['sho'] }}</span></div>
                                <div><span class="text-stone-400">PAS</span> <span class="text-white">{{ $card['pas'] }}</span></div>
                                <div><span class="text-stone-400">DRI</span> <span class="text-white">{{ $card['dri'] }}</span></div>
                                <div><span class="text-stone-400">DEF</span> <span class="text-white">{{ $card['def'] }}</span></div>
                                <div><span class="text-stone-400">PHY</span> <span class="text-white">{{ $card['phy'] }}</span></div>
                            </div>

                            {{-- Haftanın Unvanı --}}
                            <div class="mt-2.5 rounded-lg bg-amber-500/20 py-1 text-3xs font-black uppercase text-amber-300">
                                {{ $card['badge'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Kings League / Baller League Formatı: Viral Halı Saha Vitrini --}}
        @if (! empty($viralFeed))
            <div class="mt-8 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-stone-100 pb-4 dark:border-stone-800">
                    <div>
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-2xs font-bold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                            <span>🔥</span>
                            <span>KINGS LEAGUE & VIRAL VİTRİN</span>
                        </div>
                        <h2 class="mt-1 text-xl font-extrabold text-stone-900 dark:text-stone-100 sm:text-2xl">
                            Efsane Halı Saha Takımları & Haftanın Golleri
                        </h2>
                        <p class="text-xs text-stone-500 dark:text-stone-400">
                            Sahalarda çekilen en iyi jeneriklik hareketler, son dakika geri dönüşleri ve popüler halı saha kulüpleri.
                        </p>
                    </div>
                    <a href="{{ route('football.teams.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-stone-900 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-stone-800 dark:bg-stone-700 dark:hover:bg-stone-600">
                        <span>🏆 Kendi Takımını Tanıt</span>
                    </a>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    @foreach ($viralFeed as $item)
                        <div class="flex flex-col justify-between rounded-2xl border border-stone-100 bg-stone-50 p-4 transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-800/60">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="rounded-lg bg-white px-2 py-0.5 text-2xs font-extrabold shadow-sm dark:bg-stone-700 dark:text-stone-200">
                                        {{ $item['tag'] }}
                                    </span>
                                    <span class="text-2xs font-semibold text-stone-500 dark:text-stone-400">
                                        👁️ {{ $item['views'] }}
                                    </span>
                                </div>
                                <h3 class="mt-2.5 font-bold text-sm text-stone-900 line-clamp-2 dark:text-stone-100">
                                    {{ $item['title'] }}
                                </h3>
                                <p class="mt-1 text-xs text-stone-600 line-clamp-2 dark:text-stone-300">
                                    {{ $item['summary'] }}
                                </p>
                            </div>
                            <div class="mt-4 flex items-center justify-between border-t border-stone-200/60 pt-2.5 text-2xs dark:border-stone-700">
                                <span class="font-bold text-emerald-700 dark:text-emerald-400 truncate">{{ $item['team'] }}</span>
                                <span class="text-stone-500 dark:text-stone-400">❤️ {{ $item['likes'] }} Beğeni</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Sekme Navigasyonu --}}
        <div class="mt-8 grid gap-8 lg:grid-cols-3">
            {{-- Sol / Ana Kolon (2 Kolon) --}}
            <div class="space-y-8 lg:col-span-2">
                {{-- Eksik Oyuncu / Maç İlanları Canlı Vitrini --}}
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">📢</span>
                            <h2 class="text-lg font-bold text-stone-900 dark:text-stone-100">Acil Oyuncu & Maç İlanları</h2>
                        </div>
                        <a href="{{ route('football.requests.index', \Illuminate\Support\Str::slug($currentCity)) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                            Tüm İlanlar ({{ $metrics['open_requests_count'] }}) →
                        </a>
                    </div>

                    @if ($requests->isEmpty())
                        <div class="mt-4 rounded-2xl border border-dashed border-stone-200 p-6 text-center text-sm text-stone-500 dark:border-stone-800 dark:text-stone-400">
                            Şu an açık bir oyuncu ilanı yok.
                            <a href="{{ route('football.requests.create') }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">İlk ilanı sen aç!</a>
                        </div>
                    @else
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ($requests as $req)
                                <div class="rounded-2xl border border-stone-100 bg-stone-50 p-4 transition hover:border-emerald-200 dark:border-stone-800 dark:bg-stone-800/60 dark:hover:border-emerald-800">
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-bold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            {{ $req->type->emoji() }} {{ $req->type->getLabel() }}
                                        </span>
                                        @if ($req->needed_count > 0)
                                            <span class="text-2xs font-semibold text-amber-700 dark:text-amber-400">{{ $req->needed_count }} kişi aranıyor</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm font-semibold text-stone-900 line-clamp-2 dark:text-stone-100">
                                        {{ $req->description }}
                                    </p>
                                    <div class="mt-3 flex items-center justify-between text-2xs text-stone-500 dark:text-stone-400">
                                        <span>📍 {{ $req->venue_name ?: $req->city }}</span>
                                        @if ($req->match_time)
                                            <span>📅 {{ $req->match_time->translatedFormat('d M H:i') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Şehirdeki Takımlar --}}
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🏆</span>
                            <h2 class="text-lg font-bold text-stone-900 dark:text-stone-100">{{ $currentCity }} Takımları</h2>
                        </div>
                        <a href="{{ route('football.teams.index', \Illuminate\Support\Str::slug($currentCity)) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                            Tüm Takımlar ({{ $metrics['teams_count'] }}) →
                        </a>
                    </div>

                    @if ($teams->isEmpty())
                        <div class="mt-4 rounded-2xl border border-dashed border-stone-200 p-6 text-center text-sm text-stone-500 dark:border-stone-800 dark:text-stone-400">
                            {{ $currentCity }} şehrinde henüz kurulmuş bir takım yok.
                            <a href="{{ route('football.teams.create') }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">İlk takımı kur!</a>
                        </div>
                    @else
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ($teams as $team)
                                <a href="{{ route('football.teams.show', ['city' => \Illuminate\Support\Str::slug($team->city), 'team' => $team->slug]) }}"
                                   class="group flex items-center gap-3 rounded-2xl border border-stone-100 bg-stone-50 p-3.5 transition hover:border-emerald-300 hover:bg-white dark:border-stone-800 dark:bg-stone-800/60 dark:hover:bg-stone-800">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-stone-200 text-lg font-bold text-stone-700 dark:bg-stone-700 dark:text-stone-200">
                                        @if ($team->logo_path)
                                            <img src="{{ Storage::url($team->logo_path) }}" alt="{{ $team->name }}" class="h-full w-full rounded-2xl object-cover">
                                        @else
                                            ⚽
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <h3 class="truncate font-bold text-stone-900 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-400">
                                                {{ $team->name }}
                                            </h3>
                                            @if ($team->is_verified)
                                                <span title="Doğrulanmış Takım" class="text-emerald-700">✓</span>
                                            @endif
                                        </div>
                                        <p class="text-2xs text-stone-500 dark:text-stone-400">
                                            Kaptan: {{ $team->captain?->name }} · {{ $team->active_members_count }} Oyuncu
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ $team->points }}p</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Popüler Halı Sahalar --}}
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">📍</span>
                            <h2 class="text-lg font-bold text-stone-900 dark:text-stone-100">{{ $currentCity }} Halı Sahaları</h2>
                        </div>
                        <a href="{{ route('football.venues.index', \Illuminate\Support\Str::slug($currentCity)) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                            Tüm Sahalar →
                        </a>
                    </div>

                    @if ($venues->isEmpty())
                        <div class="mt-4 rounded-2xl border border-dashed border-stone-200 p-6 text-center text-sm text-stone-500 dark:border-stone-800 dark:text-stone-400">
                            Bu şehirde henüz halı saha eklenmedi.
                            <a href="{{ route('football.venues.create') }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Yeni saha ekle!</a>
                        </div>
                    @else
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ($venues as $venue)
                                <a href="{{ route('football.venues.show', ['city' => \Illuminate\Support\Str::slug($venue->city), 'venue' => $venue->slug]) }}"
                                   class="group rounded-2xl border border-stone-100 bg-stone-50 p-4 transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-800/60">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-bold text-stone-900 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-400 truncate">
                                            {{ $venue->name }}
                                        </h3>
                                        <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400">
                                            ⭐ {{ number_format($venue->rating, 1) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-stone-500 truncate dark:text-stone-400">
                                        {{ $venue->address }}
                                    </p>
                                    @if ($venue->price_info)
                                        <p class="mt-2 text-2xs font-semibold text-emerald-700 dark:text-emerald-400">
                                            💰 {{ $venue->price_info }}
                                        </p>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sağ Kolon: Şehir Puan Durumu & Hızlı Menü --}}
            <div class="space-y-6">
                {{-- Şehir Halı Saha Ligi Özeti --}}
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <div class="flex items-center justify-between border-b border-stone-100 pb-3 dark:border-stone-800">
                        <h2 class="text-base font-bold text-stone-900 dark:text-stone-100">
                            🏆 {{ $currentCity }} Halı Saha Ligi
                        </h2>
                        <a href="{{ route('football.league', \Illuminate\Support\Str::slug($currentCity)) }}" class="text-2xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                            Detaylı Tablo →
                        </a>
                    </div>

                    @if ($standings->isEmpty())
                        <p class="mt-4 text-center text-xs text-stone-500 dark:text-stone-400">
                            Henüz oynanmış maç bulunmuyor.
                        </p>
                    @else
                        <div class="mt-4 overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="text-stone-500">
                                        <th class="py-1">#</th>
                                        <th class="py-1">Takım</th>
                                        <th class="py-1 text-center">O</th>
                                        <th class="py-1 text-center">Av</th>
                                        <th class="py-1 text-right">P</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                                    @foreach ($standings as $row)
                                        <tr class="transition hover:bg-stone-50 dark:hover:bg-stone-800/40">
                                            <td class="py-2.5 font-bold {{ $row['rank'] <= 3 ? 'text-amber-600' : 'text-stone-500' }}">
                                                {{ $row['rank'] }}
                                            </td>
                                            <td class="py-2.5 font-semibold text-stone-900 dark:text-stone-100 truncate max-w-[120px]">
                                                <a href="{{ route('football.teams.show', ['city' => \Illuminate\Support\Str::slug($row['team']->city), 'team' => $row['team']->slug]) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">
                                                    {{ $row['team']->name }}
                                                </a>
                                            </td>
                                            <td class="py-2.5 text-center text-stone-600 dark:text-stone-400">{{ $row['played'] }}</td>
                                            <td class="py-2.5 text-center text-stone-600 dark:text-stone-400">{{ $row['goal_diff'] > 0 ? '+'.$row['goal_diff'] : $row['goal_diff'] }}</td>
                                            <td class="py-2.5 text-right font-bold text-emerald-700 dark:text-emerald-400">{{ $row['points'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Hızlı Gezinme Kartı --}}
                <div class="rounded-3xl border border-stone-200 bg-stone-50 p-6 dark:border-stone-800 dark:bg-stone-900/60">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Futbol Menüsü</h3>
                    <ul class="mt-4 space-y-2 text-sm font-medium">
                        <li>
                            <a href="{{ route('football.teams.index', \Illuminate\Support\Str::slug($currentCity)) }}" class="flex items-center justify-between rounded-xl bg-white p-3 text-stone-800 shadow-sm transition hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:text-emerald-400">
                                <span>🏆 Takımlar</span>
                                <span>→</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('football.matches.index', \Illuminate\Support\Str::slug($currentCity)) }}" class="flex items-center justify-between rounded-xl bg-white p-3 text-stone-800 shadow-sm transition hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:text-emerald-400">
                                <span>📅 Maç Takvimi & Skorlar</span>
                                <span>→</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('football.venues.index', \Illuminate\Support\Str::slug($currentCity)) }}" class="flex items-center justify-between rounded-xl bg-white p-3 text-stone-800 shadow-sm transition hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:text-emerald-400">
                                <span>📍 Halı Sahalar</span>
                                <span>→</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('football.players.index', \Illuminate\Support\Str::slug($currentCity)) }}" class="flex items-center justify-between rounded-xl bg-white p-3 text-stone-800 shadow-sm transition hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:text-emerald-400">
                                <span>👤 Oyuncu Havuzu</span>
                                <span>→</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('football.player.edit') }}" class="flex items-center justify-between rounded-xl bg-emerald-50 p-3 text-emerald-800 shadow-sm transition hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <span>⚡ Futbol Profilimi Düzenle</span>
                                <span>→</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
