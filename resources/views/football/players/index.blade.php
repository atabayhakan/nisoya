<x-layouts.app>
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('football.city', \Illuminate\Support\Str::slug($currentCity)) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    ← {{ $currentCity }} Futbol Ana Sayfası
                </a>
                <h1 class="mt-1 text-2xl font-black tracking-tight text-stone-900 sm:text-3xl dark:text-stone-100">
                    {{ $currentCity }} Futbolcu Havuzu
                </h1>
                <p class="text-sm text-stone-600 dark:text-stone-400">
                    Şehirdeki serbest futbolcuları, mevkilerini, seviyelerini keşfet ve takımına davet et.
                </p>
            </div>
            <a href="{{ route('football.player.edit') }}"
               class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                ⚡ Futbol Profilimi Düzenle
            </a>
        </div>

        {{-- Filtreler --}}
        <form method="GET" class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <div>
                <select name="position" class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="">Tüm Mevkiler</option>
                    <option value="kaleci" @selected(request('position') === 'kaleci')>🧤 Kaleci</option>
                    <option value="defans" @selected(request('position') === 'defans')>🛡️ Defans</option>
                    <option value="orta_saha" @selected(request('position') === 'orta_saha')>⚙️ Orta Saha</option>
                    <option value="kanat" @selected(request('position') === 'kanat')>⚡ Kanat</option>
                    <option value="forvet" @selected(request('position') === 'forvet')>🎯 Forvet</option>
                </select>
            </div>
            <div>
                <select name="level" class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="">Tüm Seviyeler</option>
                    <option value="baslangic" @selected(request('level') === 'baslangic')>Başlangıç</option>
                    <option value="orta" @selected(request('level') === 'orta')>Orta</option>
                    <option value="iyi" @selected(request('level') === 'iyi')>İyi</option>
                    <option value="ileri" @selected(request('level') === 'ileri')>İleri</option>
                </select>
            </div>
            <label class="flex items-center gap-2 text-xs font-semibold text-stone-700 dark:text-stone-300 cursor-pointer">
                <input type="checkbox" name="looking_for_team" value="1" @checked(request('looking_for_team')) class="rounded text-emerald-600 focus:ring-emerald-500">
                <span>Takım Arayanlar</span>
            </label>
            <button type="submit" class="rounded-xl bg-stone-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-stone-800 dark:bg-stone-700 dark:hover:bg-stone-600">
                Filtrele
            </button>
        </form>

        @if ($players->isEmpty())
            <div class="mt-8 rounded-3xl border border-dashed border-stone-200 p-12 text-center text-stone-500 dark:border-stone-800 dark:text-stone-400">
                <p class="text-base font-semibold">Bu kriterlere uygun oyuncu bulunamadı.</p>
                <a href="{{ route('football.player.edit') }}" class="mt-3 inline-block font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    Kendi futbol profilini hemen oluştur!
                </a>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($players as $player)
                    <div class="flex flex-col justify-between rounded-3xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900">
                        <div>
                            <div class="flex items-start justify-between">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-lg font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    {{ mb_substr($player->user->name, 0, 2) }}
                                </div>
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-3xs font-bold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    {{ $player->level->badgeEmoji() }} {{ $player->level->getLabel() }}
                                </span>
                            </div>

                            <h2 class="mt-3 font-bold text-sm text-stone-900 dark:text-stone-100 truncate">
                                <a href="{{ route('profiles.show', $player->user->username) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">
                                    {{ $player->user->name }}
                                </a>
                            </h2>

                            <p class="mt-1 text-2xs text-stone-500 dark:text-stone-400">
                                📍 {{ $player->city ?: $currentCity }}
                                @if ($player->preferred_foot)
                                    · {{ $player->preferred_foot === 'sol' ? 'Sol Ayak' : ($player->preferred_foot === 'sag' ? 'Sağ Ayak' : 'Çift Ayak') }}
                                @endif
                            </p>

                            {{-- Mevkiler --}}
                            <div class="mt-3 flex flex-wrap gap-1">
                                @foreach ($player->positionEnums() as $pos)
                                    <span class="rounded-md bg-stone-100 px-1.5 py-0.5 text-3xs font-semibold text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                        {{ $pos->labelWithEmoji() }}
                                    </span>
                                @endforeach
                            </div>

                            @if ($player->bio)
                                <p class="mt-3 text-xs text-stone-600 line-clamp-2 dark:text-stone-400">
                                    {{ $player->bio }}
                                </p>
                            @endif

                            @if ($player->is_looking_for_team)
                                <div class="mt-3">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-3xs font-bold text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                        ✨ Takım Arıyor
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 border-t border-stone-100 pt-3 dark:border-stone-800">
                            <a href="{{ route('profiles.show', $player->user->username) }}"
                               class="flex w-full items-center justify-center rounded-xl bg-stone-50 py-1.5 text-xs font-bold text-stone-700 transition hover:bg-emerald-50 hover:text-emerald-800 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300">
                                Oyuncu Profili →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $players->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
