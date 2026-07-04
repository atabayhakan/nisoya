<x-layouts.app :title="$company->name.' — Nisoya'">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <x-panel.back-link :href="route('jobs.index')" label="İş ilanları" />

        {{-- Şirket başlığı --}}
        <div class="mt-3 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            <div class="flex items-start gap-4">
                <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-2xl bg-stone-100 text-xl font-bold text-stone-500 dark:bg-stone-800 dark:text-stone-400">
                    @if ($company->logoUrl())
                        <img src="{{ $company->logoUrl() }}" alt="{{ $company->name }}" class="h-full w-full object-cover">
                    @else
                        {{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold text-stone-900 dark:text-stone-50">{{ $company->name }}</h1>
                        @if ($company->is_verified)
                            <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700 dark:bg-sky-900/40 dark:text-sky-300"><x-heroicon-s-check-badge class="h-3.5 w-3.5" /> Doğrulandı</span>
                        @endif
                    </div>
                    @if ($company->tagline)<p class="mt-0.5 text-sm text-stone-500 dark:text-stone-400">{{ $company->tagline }}</p>@endif
                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-stone-400 dark:text-stone-500">
                        @if ($company->sector)<span>🏢 {{ $company->sector }}</span>@endif
                        @if ($company->company_size)<span>👥 {{ $company->company_size }} çalışan</span>@endif
                        @if ($company->founded_year)<span>📅 {{ $company->founded_year }}</span>@endif
                        @if ($company->country)<span>{{ $company->country->emoji }} {{ $company->city ?: $company->country->name_tr }}</span>@endif
                    </div>
                    <div class="mt-2 flex flex-wrap gap-3 text-xs">
                        @if ($company->website)<a href="{{ $company->website }}" target="_blank" rel="noopener nofollow" class="text-emerald-700 hover:underline dark:text-emerald-400">🌐 Web sitesi</a>@endif
                        @if ($company->social_linkedin)<a href="{{ $company->social_linkedin }}" target="_blank" rel="noopener nofollow" class="text-emerald-700 hover:underline dark:text-emerald-400">LinkedIn</a>@endif
                    </div>
                </div>
            </div>

            @if ($company->about)
                <div class="mt-5 whitespace-pre-line border-t border-stone-100 pt-4 text-sm text-stone-600 dark:border-stone-800 dark:text-stone-300">{{ $company->about }}</div>
            @endif
        </div>

        {{-- Açık pozisyonlar --}}
        <h2 class="mt-6 text-lg font-bold text-stone-900 dark:text-stone-50">Açık pozisyonlar ({{ $jobs->total() }})</h2>
        @if ($jobs->isNotEmpty())
            <div class="mt-3 space-y-4">
                @foreach ($jobs as $job)
                    @include('partials.job-card', ['job' => $job])
                @endforeach
            </div>
            <div class="mt-6">{{ $jobs->links() }}</div>
        @else
            <p class="mt-3 rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center text-sm text-stone-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-400">Şu an açık pozisyon yok.</p>
        @endif
    </div>
</x-layouts.app>
