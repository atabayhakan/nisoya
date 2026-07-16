<a href="{{ route('profiles.show', $candidate->username) }}"
   class="group block rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
    <div class="flex items-start gap-4">
        <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-full bg-emerald-100 font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
            @if ($candidate->avatar_path)
                <img src="{{ Storage::url($candidate->avatar_path) }}" alt="" class="h-full w-full object-cover">
            @else
                {{ mb_strtoupper(mb_substr($candidate->name, 0, 1)) }}
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <h3 class="flex items-center gap-1.5 truncate font-semibold text-stone-800 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-400">
                {{ $candidate->name }}
                @if ($candidate->is_verified)<x-verified-badge size="xs" />@endif
            </h3>

            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-stone-400 dark:text-stone-500">
                @if ($candidate->jobCategory)
                    <span class="rounded-full bg-stone-100 px-2 py-0.5 text-stone-500 dark:bg-stone-800 dark:text-stone-400">{{ $candidate->jobCategory->name }}</span>
                @endif
                @if ($candidate->city || $candidate->country_code)
                    <span>@if ($candidate->city){{ $candidate->city }} · @endif{{ $candidate->country_code }}</span>
                @endif
            </div>

            @if ($candidate->bio)
                <p class="mt-2 line-clamp-2 text-sm text-stone-500 dark:text-stone-400">{{ $candidate->bio }}</p>
            @endif

            @if ($candidate->skills)
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    @foreach (array_slice($candidate->skills, 0, 4) as $skill)
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $skill }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</a>
