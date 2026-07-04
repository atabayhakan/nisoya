<x-layouts.app :title="$job->title.' — Nisoya'">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <x-panel.back-link :href="route('jobs.index')" label="Tüm iş ilanları" />

        @if (session('status'))
            <div class="mt-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        {{-- Başlık kartı --}}
        <div class="mt-3 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            <div class="flex items-start gap-4">
                <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-stone-100 text-lg font-bold text-stone-500 dark:bg-stone-800 dark:text-stone-400">
                    @if ($job->company?->logoUrl())
                        <img src="{{ $job->company->logoUrl() }}" alt="{{ $job->company->name }}" class="h-full w-full object-cover">
                    @else
                        {{ mb_strtoupper(mb_substr($job->company?->name ?? '?', 0, 1)) }}
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-bold text-stone-900 dark:text-stone-50">{{ $job->title }}</h1>
                    @if ($job->company)
                        <a href="{{ route('companies.show', $job->company) }}" class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">{{ $job->company->name }}</a>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs">
                        <span class="rounded-full bg-sky-100 px-2 py-0.5 font-medium text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">{{ $job->employment_type->getLabel() }}</span>
                        @if ($job->category)<span class="rounded-full bg-stone-100 px-2 py-0.5 text-stone-500 dark:bg-stone-800 dark:text-stone-400">{{ $job->category->name }}</span>@endif
                        @if ($job->is_remote)<span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Uzaktan</span>@endif
                        @if ($job->experience_level)<span class="rounded-full bg-stone-100 px-2 py-0.5 text-stone-500 dark:bg-stone-800 dark:text-stone-400">{{ $job->experience_level->getLabel() }}</span>@endif
                    </div>
                </div>
            </div>

            {{-- Meta bilgiler --}}
            <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-stone-100 pt-4 text-sm dark:border-stone-800 sm:grid-cols-4">
                @if ($job->country)
                    <div><dt class="text-xs text-stone-400 dark:text-stone-500">Konum</dt><dd class="mt-0.5 font-medium text-stone-700 dark:text-stone-200">{{ $job->country->emoji }} {{ $job->city ?: $job->country->name_tr }}</dd></div>
                @endif
                @if ($job->salaryLabel())
                    <div><dt class="text-xs text-stone-400 dark:text-stone-500">Maaş</dt><dd class="mt-0.5 font-medium text-stone-700 dark:text-stone-200">{{ $job->salaryLabel() }}</dd></div>
                @endif
                <div><dt class="text-xs text-stone-400 dark:text-stone-500">Pozisyon</dt><dd class="mt-0.5 font-medium text-stone-700 dark:text-stone-200">{{ $job->positions }} kişi</dd></div>
                @if ($job->deadline)
                    <div><dt class="text-xs text-stone-400 dark:text-stone-500">Son başvuru</dt><dd class="mt-0.5 font-medium {{ $job->isExpired() ? 'text-red-600 dark:text-red-400' : 'text-stone-700 dark:text-stone-200' }}">{{ $job->deadline->translatedFormat('d F Y') }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Açıklama --}}
        <div class="mt-4 rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
            <h2 class="font-semibold text-stone-800 dark:text-stone-100">İş tanımı</h2>
            <div class="prose prose-sm mt-2 max-w-none whitespace-pre-line text-stone-600 dark:text-stone-300">{{ $job->description }}</div>
        </div>

        {{-- Başvuru bölümü --}}
        <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6 dark:border-emerald-900/40 dark:bg-emerald-950/20">
            @if ($isOwner)
                <p class="text-sm text-stone-600 dark:text-stone-300">Bu senin ilanın.</p>
                <a href="{{ route('panel.jobs.applicants', $job) }}" class="mt-2 inline-block rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Başvuruları gör</a>
            @elseif (! $job->isOpen())
                <p class="text-sm font-medium text-stone-600 dark:text-stone-300">Bu ilana artık başvuru alınmıyor.</p>
            @elseif ($hasApplied)
                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">✅ Bu ilana başvurdun. Durumu "Başvurularım" sayfasından takip edebilirsin.</p>
            @else
                @auth
                    <h2 class="font-semibold text-stone-800 dark:text-stone-100">Bu ilana başvur</h2>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Profilin (yetenekler + portfolyo) işverene otomatik olarak gösterilir. İstersen ön yazı ekle ve CV yükle.</p>
                    @if ($errors->any())
                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $errors->first() }}</div>
                    @endif
                    <form method="POST" action="{{ route('jobs.apply', $job) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                        @csrf
                        <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                        <textarea name="cover_letter" rows="4" maxlength="3000" placeholder="Ön yazı (opsiyonel) — neden bu iş için uygun olduğunu kısaca anlat."
                                  class="w-full rounded-xl border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('cover_letter') }}</textarea>
                        <div>
                            <label class="text-xs font-medium text-stone-600 dark:text-stone-300">CV (opsiyonel, PDF/Word, max 5MB)</label>
                            <input type="file" name="cv" accept=".pdf,.doc,.docx" class="mt-1 block w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-emerald-700 dark:text-stone-300 dark:file:bg-emerald-900/40 dark:file:text-emerald-300">
                        </div>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Başvuruyu gönder</button>
                    </form>
                @else
                    <p class="text-sm text-stone-600 dark:text-stone-300">Başvurmak için <a href="{{ route('login') }}" class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">giriş yap</a> ya da <a href="{{ route('register') }}" class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">kayıt ol</a>.</p>
                @endauth
            @endif
        </div>
    </div>
</x-layouts.app>
