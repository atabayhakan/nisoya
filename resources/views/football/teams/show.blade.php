<x-layouts.app>
    <div class="mx-auto max-w-6xl px-4 py-8">
        {{-- Durum Bildirimleri --}}
        @if (session('status'))
            <div class="mb-6 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Takım Profil Başlığı --}}
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-5">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-3xl bg-stone-100 text-3xl font-black text-stone-700 ring-1 ring-stone-200 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-700">
                        @if ($team->logo_path)
                            <img src="{{ Storage::url($team->logo_path) }}" alt="{{ $team->name }}" class="h-full w-full rounded-3xl object-cover">
                        @else
                            ⚽
                        @endif
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-extrabold text-stone-900 sm:text-3xl dark:text-stone-100">
                                {{ $team->name }}
                            </h1>
                            @if ($team->is_verified)
                                <span title="Doğrulanmış Takım" class="text-emerald-600 text-lg">✓</span>
                            @endif
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                {{ $team->level->badgeEmoji() }} {{ $team->level->getLabel() }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                            📍 {{ $team->city }}, {{ $team->country?->name_tr ?? $team->country_code }} · Kaptan: <strong>{{ $team->captain?->name }}</strong>
                        </p>
                        @if ($team->primary_kit_color || $team->secondary_kit_color)
                            <p class="mt-1 text-2xs text-stone-400">
                                Forma: {{ $team->primary_kit_color }} {{ $team->secondary_kit_color ? '/ '.$team->secondary_kit_color : '' }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Eylem Düğmeleri --}}
                <div class="flex flex-wrap items-center gap-2">
                    @auth
                        @if ($team->isCaptain(auth()->user()))
                            <a href="{{ route('football.teams.edit', $team) }}"
                               class="rounded-xl border border-stone-300 bg-white px-3.5 py-2 text-xs font-bold text-stone-700 shadow-sm transition hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200">
                                ⚙️ Takımı Düzenle
                            </a>
                        @elseif ($userMembership && $userMembership->status === \App\Enums\FootballMemberStatus::Aktif)
                            <form method="POST" action="{{ route('football.teams.leave', $team) }}" onsubmit="return confirm('Takımdan ayrılmak istediğinize emin misiniz?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300">
                                    Takımdan Ayrıl
                                </button>
                            </form>
                        @elseif ($userMembership && $userMembership->status === \App\Enums\FootballMemberStatus::DavetEdildi)
                            <form method="POST" action="{{ route('football.teams.respond-invite', $userMembership) }}" class="flex gap-2">
                                @csrf
                                <button type="submit" name="action" value="accept" class="rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-500">
                                    Daveti Kabul Et
                                </button>
                                <button type="submit" name="action" value="reject" class="rounded-xl bg-stone-200 px-3.5 py-2 text-xs font-bold text-stone-700 transition hover:bg-stone-300">
                                    Reddet
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('football.teams.join', $team) }}">
                                @csrf
                                <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-500">
                                    + Takıma Katılma İsteği Gönder
                                </button>
                            </form>
                        @endif

                        @if (! $team->isCaptain(auth()->user()) && auth()->user()->captainTeams()->exists())
                            <a href="{{ route('football.matches.create', ['away_team_id' => $team->id]) }}"
                               class="rounded-xl bg-stone-900 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-stone-800 dark:bg-stone-700">
                                ⚽ Maç Teklif Et
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white">
                            Giriş Yap & Katıl
                        </a>
                    @endauth
                </div>
            </div>

            @if ($team->description)
                <p class="mt-6 text-sm text-stone-700 leading-relaxed border-t border-stone-100 pt-4 dark:border-stone-800 dark:text-stone-300">
                    {{ $team->description }}
                </p>
            @endif

            {{-- Takım İstatistik Çubuğu --}}
            <div class="mt-6 grid grid-cols-2 gap-2 border-t border-stone-100 pt-6 sm:grid-cols-7 dark:border-stone-800">
                <div class="rounded-2xl bg-stone-50 p-3 text-center dark:bg-stone-800/60">
                    <p class="text-3xs uppercase font-bold text-stone-400">Puan</p>
                    <p class="mt-1 text-lg font-black text-emerald-700 dark:text-emerald-400">{{ $team->points }}</p>
                </div>
                <div class="rounded-2xl bg-stone-50 p-3 text-center dark:bg-stone-800/60">
                    <p class="text-3xs uppercase font-bold text-stone-400">Maç</p>
                    <p class="mt-1 text-lg font-bold text-stone-800 dark:text-stone-200">{{ $team->matches_count }}</p>
                </div>
                <div class="rounded-2xl bg-stone-50 p-3 text-center dark:bg-stone-800/60">
                    <p class="text-3xs uppercase font-bold text-stone-400">Galibiyet</p>
                    <p class="mt-1 text-lg font-bold text-emerald-600">{{ $team->wins_count }}</p>
                </div>
                <div class="rounded-2xl bg-stone-50 p-3 text-center dark:bg-stone-800/60">
                    <p class="text-3xs uppercase font-bold text-stone-400">Beraberlik</p>
                    <p class="mt-1 text-lg font-bold text-amber-600">{{ $team->draws_count }}</p>
                </div>
                <div class="rounded-2xl bg-stone-50 p-3 text-center dark:bg-stone-800/60">
                    <p class="text-3xs uppercase font-bold text-stone-400">Mağlubiyet</p>
                    <p class="mt-1 text-lg font-bold text-rose-600">{{ $team->losses_count }}</p>
                </div>
                <div class="rounded-2xl bg-stone-50 p-3 text-center dark:bg-stone-800/60">
                    <p class="text-3xs uppercase font-bold text-stone-400">Atılan/Yenen</p>
                    <p class="mt-1 text-sm font-bold text-stone-700 dark:text-stone-300">{{ $team->goals_for }}:{{ $team->goals_against }}</p>
                </div>
                <div class="rounded-2xl bg-stone-50 p-3 text-center dark:bg-stone-800/60">
                    <p class="text-3xs uppercase font-bold text-stone-400">Averaj</p>
                    <p class="mt-1 text-lg font-bold {{ $team->goalDifference() >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $team->goalDifference() > 0 ? '+'.$team->goalDifference() : $team->goalDifference() }}
                    </p>
                </div>
            </div>
        </div>

        {{-- 2 Kolon: Kadro & Maçlar --}}
        <div class="mt-8 grid gap-8 lg:grid-cols-3">
            {{-- Kadro (2 Kolon) --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <div class="flex items-center justify-between border-b border-stone-100 pb-4 dark:border-stone-800">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">👥</span>
                            <h2 class="text-lg font-bold text-stone-900 dark:text-stone-100">
                                Takım Kadrosu ({{ $team->activeMembers->count() }})
                            </h2>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($team->activeMembers as $member)
                            <div class="flex items-center justify-between rounded-2xl border border-stone-100 bg-stone-50 p-3.5 dark:border-stone-800 dark:bg-stone-800/60">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        {{ $member->jersey_number ?: ($member->isCaptain() ? 'C' : '⚽') }}
                                    </div>
                                    <div>
                                        <a href="{{ route('profiles.show', $member->user->username) }}" class="font-bold text-stone-900 hover:text-emerald-700 dark:text-stone-100 dark:hover:text-emerald-400 text-sm">
                                            {{ $member->user->name }}
                                        </a>
                                        <p class="text-2xs text-stone-500 dark:text-stone-400">
                                            {{ $member->isCaptain() ? '👑 Kaptan' : ($member->primary_position?->labelWithEmoji() ?: 'Oyuncu') }}
                                        </p>
                                    </div>
                                </div>
                                @if ($member->user->footballProfile)
                                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">
                                        ⭐ {{ number_format($member->user->footballProfile->rating, 1) }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Kaptan için Oyuncu Davet Formu --}}
                    @auth
                        @if ($team->isCaptain(auth()->user()))
                            <div class="mt-6 border-t border-stone-100 pt-6 dark:border-stone-800">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    + Kadroya Oyuncu Davet Et
                                </h3>
                                <form method="POST" action="{{ route('football.teams.invite', $team) }}" class="mt-3 flex flex-wrap gap-2">
                                    @csrf
                                    <input type="text" name="username" placeholder="Kullanıcı adı (@ahmet)" required
                                           class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs focus:border-emerald-500 focus:bg-white dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                    <select name="primary_position" class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                        <option value="">Pozisyon Seç</option>
                                        <option value="kaleci">🧤 Kaleci</option>
                                        <option value="defans">🛡️ Defans</option>
                                        <option value="orta_saha">⚙️ Orta Saha</option>
                                        <option value="kanat">⚡ Kanat</option>
                                        <option value="forvet">🎯 Forvet</option>
                                    </select>
                                    <input type="number" name="jersey_number" placeholder="No (Örn: 10)" min="1" max="99"
                                           class="w-20 rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                    <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-600">
                                        Davet Gönder
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endauth
                </div>
            </div>

            {{-- Sağ Kolon: Maçlar --}}
            <div class="space-y-6">
                {{-- Son Maçlar --}}
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-stone-900 dark:text-stone-100 border-b border-stone-100 pb-3 dark:border-stone-800">
                        Son Oynanan Maçlar
                    </h2>

                    @if ($recentMatches->isEmpty())
                        <p class="mt-4 text-center text-xs text-stone-500 dark:text-stone-400">
                            Henüz tamamlanmış maç yok.
                        </p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($recentMatches as $match)
                                <a href="{{ route('football.matches.show', ['city' => \Illuminate\Support\Str::slug($match->city), 'match' => $match->id]) }}"
                                   class="block rounded-2xl border border-stone-100 bg-stone-50 p-3 text-xs transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-800/60">
                                    <div class="flex items-center justify-between font-bold">
                                        <span class="truncate max-w-[100px]">{{ $match->homeTeam?->name }}</span>
                                        <span class="rounded bg-stone-900 px-2 py-0.5 text-white dark:bg-stone-700">
                                            {{ $match->home_score }} - {{ $match->away_score }}
                                        </span>
                                        <span class="truncate max-w-[100px]">{{ $match->awayTeam?->name }}</span>
                                    </div>
                                    <p class="mt-2 text-3xs text-stone-400 text-center">
                                        {{ $match->match_date->translatedFormat('d M Y') }} · {{ $match->venueDisplay() }}
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Gelecek Maçlar --}}
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-stone-900 dark:text-stone-100 border-b border-stone-100 pb-3 dark:border-stone-800">
                        Planlanan Maçlar
                    </h2>

                    @if ($upcomingMatches->isEmpty())
                        <p class="mt-4 text-center text-xs text-stone-500 dark:text-stone-400">
                            Yaklaşan maç planı yok.
                        </p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($upcomingMatches as $match)
                                <div class="rounded-2xl border border-stone-100 bg-stone-50 p-3 text-xs dark:border-stone-800 dark:bg-stone-800/60">
                                    <div class="flex items-center justify-between font-bold">
                                        <span class="truncate">{{ $match->homeTeam?->name }}</span>
                                        <span class="text-stone-400">vs</span>
                                        <span class="truncate">{{ $match->awayTeam?->name ?: 'Rakip Bekleniyor' }}</span>
                                    </div>
                                    <p class="mt-2 text-3xs text-emerald-700 dark:text-emerald-400 text-center font-semibold">
                                        📅 {{ $match->match_date->translatedFormat('d M H:i') }} · 📍 {{ $match->venueDisplay() }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
