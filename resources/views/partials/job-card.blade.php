<a href="{{ route('jobs.show', [$job, $job->slug]) }}"
   class="group block rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-stone-900 dark:shadow-none {{ $job->isCurrentlyFeatured() ? 'border-amber-300 ring-1 ring-amber-200 dark:border-amber-700 dark:ring-amber-900/40' : 'border-stone-200 dark:border-stone-800' }}">
    <div class="flex items-start gap-4">
        {{-- Şirket logosu --}}
        <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-stone-100 font-bold text-stone-600 dark:bg-stone-800 dark:text-stone-400">
            @if ($job->company?->logoUrl())
                <img src="{{ $job->company->logoUrl() }}" alt="{{ $job->company->name }}" class="h-full w-full object-cover">
            @else
                {{ mb_strtoupper(mb_substr($job->company?->name ?? '?', 0, 1)) }}
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                @if ($job->isCurrentlyFeatured())
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                        <x-heroicon-s-star class="h-3 w-3" /> Öne çıkan
                    </span>
                @endif
                @if ($job->category)
                    <span class="rounded-full bg-stone-100 px-2 py-0.5 text-stone-600 dark:bg-stone-800 dark:text-stone-400">{{ $job->category->name }}</span>
                @endif
                <span class="rounded-full bg-sky-100 px-2 py-0.5 font-medium text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">{{ $job->employment_type->getLabel() }}</span>
                @if ($job->is_remote)
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Uzaktan</span>
                @endif
            </div>

            <h3 class="mt-2 line-clamp-2 font-semibold text-stone-800 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-400">{{ $job->title }}</h3>

            <div class="mt-1 truncate text-sm text-stone-500 dark:text-stone-400">{{ $job->company?->name }}</div>

            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-stone-600 dark:text-stone-400">
                @if ($job->country)
                    <span>{{ $job->country->emoji }} @if ($job->city){{ $job->city }}@else{{ $job->country->name_tr }}@endif</span>
                @endif
                @if ($job->salaryLabel())
                    <span class="font-medium text-stone-600 dark:text-stone-300">{{ $job->salaryLabel() }}</span>
                @endif
                <span>{{ $job->created_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>
</a>
