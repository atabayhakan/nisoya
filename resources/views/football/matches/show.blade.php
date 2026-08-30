<x-layouts.app>
    <div class="mx-auto max-w-4xl px-4 py-8">
        {{-- Bildirimler --}}
        @if (session('status'))
            <div class="mb-6 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-800">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Skor Tablosu & Maç Başlığı --}}
        <div class="rounded-3xl border border-stone-200 bg-gradient-to-br from-stone-900 via-emerald-950 to-stone-950 p-6 text-white shadow-xl sm:p-8 dark:border-stone-800">
            <div class="flex items-center justify-between">
                <a href="{{ route('football.matches.index', \Illuminate\Support\Str::slug($match->city)) }}" class="text-xs font-bold text-emerald-400 hover:underline">
                    ← {{ $match->city }} Maçlarına Dön
                </a>
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider {{ $match->result_status === \App\Enums\FootballResultStatus::Dogrulandi ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-300' }}">
                    {{ $match->result_status->getLabel() }}
                </span>
            </div>

            {{-- Skor Tahtası --}}
            <div class="mt-8 grid grid-cols-3 items-center gap-4 text-center">
                {{-- Ev Sahibi --}}
                <div class="flex flex-col items-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white/10 text-2xl font-black backdrop-blur ring-1 ring-white/20">
                        @if ($match->homeTeam?->logo_path)
                            <img src="{{ Storage::url($match->homeTeam->logo_path) }}" alt="{{ $match->homeTeam->name }}" class="h-full w-full rounded-3xl object-cover">
                        @else
                            ⚽
                        @endif
                    </div>
                    <h2 class="mt-2 font-bold text-base sm:text-lg">
                        <a href="{{ route('football.teams.show', ['city' => \Illuminate\Support\Str::slug($match->homeTeam->city), 'team' => $match->homeTeam->slug]) }}" class="hover:text-emerald-400">
                            {{ $match->homeTeam?->name }}
                        </a>
                    </h2>
                    <p class="text-2xs text-stone-400">Kaptan: {{ $match->homeTeam?->captain?->name }}</p>
                </div>

                {{-- Skor --}}
                <div class="flex flex-col items-center justify-center">
                    @if ($match->home_score !== null && $match->away_score !== null)
                        <span class="text-4xl sm:text-5xl font-black text-emerald-400 tracking-tight">
                            {{ $match->home_score }} - {{ $match->away_score }}
                        </span>
                        <span class="mt-1 text-3xs uppercase font-bold text-stone-400">Karşılaşma Skoru</span>
                    @else
                        <span class="text-3xl font-black text-stone-400">VS</span>
                        <span class="mt-1 text-2xs font-semibold text-emerald-400">Maç Bekleniyor</span>
                    @endif
                </div>

                {{-- Deplasman --}}
                <div class="flex flex-col items-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white/10 text-2xl font-black backdrop-blur ring-1 ring-white/20">
                        @if ($match->awayTeam?->logo_path)
                            <img src="{{ Storage::url($match->awayTeam->logo_path) }}" alt="{{ $match->awayTeam->name }}" class="h-full w-full rounded-3xl object-cover">
                        @else
                            ⚽
                        @endif
                    </div>
                    <h2 class="mt-2 font-bold text-base sm:text-lg">
                        @if ($match->awayTeam)
                            <a href="{{ route('football.teams.show', ['city' => \Illuminate\Support\Str::slug($match->awayTeam->city), 'team' => $match->awayTeam->slug]) }}" class="hover:text-emerald-400">
                                {{ $match->awayTeam->name }}
                            </a>
                        @else
                            Rakip Aranıyor
                        @endif
                    </h2>
                    @if ($match->awayTeam)
                        <p class="text-2xs text-stone-400">Kaptan: {{ $match->awayTeam->captain?->name }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-8 flex flex-wrap items-center justify-between border-t border-white/10 pt-4 text-xs text-stone-300">
                <span>📍 {{ $match->venueDisplay() }}</span>
                <span>📅 {{ $match->match_date->translatedFormat('d F Y, H:i') }}</span>
                @if ($match->mvpPlayer)
                    <span class="font-bold text-amber-400">⭐ Maçın Adamı: {{ $match->mvpPlayer->name }}</span>
                @endif
            </div>
        </div>

        {{-- Çift Taraflı Skor Onay Paneli --}}
        @auth
            @php
                $user = auth()->user();
                $isHomeCaptain = (int) $user->id === (int) $match->homeTeam?->user_id;
                $isAwayCaptain = (int) $user->id === (int) $match->awayTeam?->user_id;
                $isCaptain = $isHomeCaptain || $isAwayCaptain;
                $submitterId = (int) $match->result_submitted_by_id;
                $isSubmitter = (int) $user->id === $submitterId;
            @endphp

            @if ($isCaptain || ($user->role?->canAccessAdminPanel() ?? false))
                <div class="mt-6 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-stone-900 dark:text-stone-100">
                        ⚡ Kaptan Yönetim Paneli
                    </h3>

                    {{-- 1. Skor Henüz Girilmemişse --}}
                    @if ($match->result_status === \App\Enums\FootballResultStatus::Beklemede)
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-xs text-stone-600 dark:text-stone-400">
                                Maç tamamlandıysa skoru girin. Rakip kaptan onayladığında lig puan tablosu güncellenecektir.
                            </p>
                            <a href="{{ route('football.matches.score-form', $match) }}"
                               class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-500">
                                ⚽ Maç Skorunu Gir
                            </a>
                        </div>

                    {{-- 2. Skor Girilmiş ama Karşı Kaptan Onayı Bekleniyorsa --}}
                    @elseif ($match->result_status === \App\Enums\FootballResultStatus::Girildi)
                        @if ($isSubmitter)
                            <div class="mt-3 rounded-2xl bg-amber-50 p-4 text-xs text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                ⏳ Skoru siz bildirdiniz ({{ $match->home_score }} - {{ $match->away_score }}). Rakip kaptanın onayı bekleniyor.
                            </div>
                        @else
                            <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl bg-amber-50 p-4 dark:bg-amber-950/40">
                                <div>
                                    <p class="text-xs font-bold text-amber-900 dark:text-amber-200">
                                        Rakip kaptan skoru bildirdi: {{ $match->home_score }} - {{ $match->away_score }}
                                    </p>
                                    <p class="text-2xs text-amber-700 dark:text-amber-400">
                                        Skoru teyit ediyor musunuz? Onayladığınızda maç sonucu lig tablosuna işlenir.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('football.matches.score-verify', $match) }}">
                                        @csrf
                                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-500">
                                            ✅ Skoru Onayla
                                        </button>
                                    </form>

                                    <button type="button" @click="$dispatch('open-dispute-modal')"
                                            class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-rose-500">
                                        ❌ İtiraz Et
                                    </button>
                                </div>
                            </div>

                            {{-- İtiraz Modal / Formu --}}
                            <div x-data="{ open: false }" @open-dispute-modal.window="open = true" x-show="open" class="mt-4 border-t border-stone-100 pt-4 dark:border-stone-800">
                                <form method="POST" action="{{ route('football.matches.score-dispute', $match) }}" class="space-y-3">
                                    @csrf
                                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">İtiraz Sebebiniz *</label>
                                    <textarea name="dispute_reason" rows="2" required placeholder="Gerçek skor neydi? Hangi takım kaç gol attı?"
                                              class="w-full rounded-xl border border-stone-200 bg-stone-50 p-3 text-xs focus:border-rose-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"></textarea>
                                    <button type="submit" class="rounded-xl bg-rose-700 px-4 py-2 text-xs font-bold text-white">
                                        İtirazı Kaydet & Yöneticilere Bildir
                                    </button>
                                </form>
                            </div>
                        @endif

                    {{-- 3. Doğrulanmış Maç --}}
                    @elseif ($match->result_status === \App\Enums\FootballResultStatus::Dogrulandi)
                        <div class="mt-3 rounded-2xl bg-emerald-50 p-4 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                            ✓ Bu maç her iki kaptan tarafından doğrulanmış ve lig tablosuna işlenmiştir.
                        </div>

                    {{-- 4. İtiraz Edilmiş Maç --}}
                    @elseif ($match->result_status === \App\Enums\FootballResultStatus::Itiraz)
                        <div class="mt-3 rounded-2xl bg-rose-50 p-4 text-xs text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                            ⚠️ Bu maça itiraz edilmiştir: "{{ $match->dispute_reason }}". Yönetici moderasyonu beklenmektedir.
                        </div>
                    @endif
                </div>
            @endif
        @endauth

        {{-- Editoryal Spor Haberi & WhatsApp Paylaşım Kartı --}}
        @if ($match->news_title)
            <div class="mt-8 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
                <div class="flex items-center justify-between border-b border-stone-100 pb-4 dark:border-stone-800">
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                        <span>📰</span> Nisoya Spor Gazetesi
                    </span>
                    <a href="https://api.whatsapp.com/send?text={{ urlencode($whatsAppShareText) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-500">
                        <span>📲</span> WhatsApp'ta Paylaş
                    </a>
                </div>

                <article class="mt-6">
                    <h2 class="text-xl font-extrabold text-stone-900 sm:text-2xl dark:text-stone-100">
                        {{ $match->news_title }}
                    </h2>
                    <p class="mt-2 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                        {{ $match->news_summary }}
                    </p>
                    <div class="mt-4 prose prose-stone text-sm text-stone-700 leading-relaxed dark:text-stone-300">
                        {!! nl2br(e($match->news_body)) !!}
                    </div>
                </article>
            </div>
        @endif
    </div>
</x-layouts.app>
