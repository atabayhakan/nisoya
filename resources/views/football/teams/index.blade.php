<x-layouts.app>
    <div class="mx-auto max-w-6xl px-4 py-8">
        {{-- Başlık --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('football.city', \Illuminate\Support\Str::slug($currentCity)) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    ← {{ $currentCity }} Futbol Ana Sayfası
                </a>
                <h1 class="mt-1 text-2xl font-black tracking-tight text-stone-900 sm:text-3xl dark:text-stone-100">
                    {{ $currentCity }} Halı Saha Takımları
                </h1>
                <p class="text-sm text-stone-600 dark:text-stone-400">
                    Şehirdeki kayıtlı takımları keşfet, maç teklif et veya kadrolarına katıl.
                </p>
            </div>
            <a href="{{ route('football.teams.create') }}"
               class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                + Takımını Kur
            </a>
        </div>

        {{-- Filtreler & Arama --}}
        <form method="GET" class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <div class="min-w-[200px] flex-1">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Takım adı veya açıklama ara..."
                       class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
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
            <button type="submit" class="rounded-xl bg-stone-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-stone-800 dark:bg-stone-700 dark:hover:bg-stone-600">
                Filtrele
            </button>
        </form>

        {{-- Takım Kartları Listesi --}}
        @if ($teams->isEmpty())
            <div class="mt-8 rounded-3xl border border-dashed border-stone-200 p-12 text-center text-stone-500 dark:border-stone-800 dark:text-stone-400">
                <p class="text-base font-semibold">Aradığınız kriterlere uygun takım bulunamadı.</p>
                <a href="{{ route('football.teams.create') }}" class="mt-3 inline-block font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    Kendi takımını hemen kur!
                </a>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($teams as $team)
                    <div class="flex flex-col justify-between rounded-3xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-stone-100 text-2xl font-bold text-stone-700 dark:bg-stone-800 dark:text-stone-200">
                                    @if ($team->logo_path)
                                        <img src="{{ Storage::url($team->logo_path) }}" alt="{{ $team->name }}" class="h-full w-full rounded-2xl object-cover">
                                    @else
                                        ⚽
                                    @endif
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                        {{ $team->level->badgeEmoji() }} {{ $team->level->getLabel() }}
                                    </span>
                                    <span class="mt-1 text-sm font-black text-emerald-700 dark:text-emerald-400">
                                        {{ $team->points }} Puan
                                    </span>
                                </div>
                            </div>

                            <h2 class="mt-3 text-lg font-bold text-stone-900 dark:text-stone-100">
                                <a href="{{ route('football.teams.show', ['city' => \Illuminate\Support\Str::slug($team->city), 'team' => $team->slug]) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">
                                    {{ $team->name }}
                                </a>
                            </h2>

                            @if ($team->description)
                                <p class="mt-1 text-xs text-stone-600 line-clamp-2 dark:text-stone-400">
                                    {{ $team->description }}
                                </p>
                            @endif

                            <div class="mt-4 flex items-center gap-3 text-xs text-stone-500 dark:text-stone-400">
                                <span>👤 Kaptan: <strong>{{ $team->captain?->name }}</strong></span>
                                <span>👥 {{ $team->active_members_count }} Oyuncu</span>
                            </div>

                            <div class="mt-3 flex items-center gap-2 text-2xs text-stone-400">
                                <span>{{ $team->matches_count }} Maç</span> ·
                                <span class="text-emerald-600">{{ $team->wins_count }}G</span> ·
                                <span class="text-amber-600">{{ $team->draws_count }}B</span> ·
                                <span class="text-rose-600">{{ $team->losses_count }}M</span>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-stone-100 pt-3 dark:border-stone-800">
                            <a href="{{ route('football.teams.show', ['city' => \Illuminate\Support\Str::slug($team->city), 'team' => $team->slug]) }}"
                               class="flex w-full items-center justify-center rounded-xl bg-stone-50 py-2 text-xs font-bold text-stone-800 transition hover:bg-emerald-50 hover:text-emerald-800 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300">
                                Takım Profili ve Kadro →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $teams->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
