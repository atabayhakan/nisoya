<x-layouts.app title="Başvurularım — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <x-panel.back-link />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Başvurularım</h1>

        @if ($applications->isNotEmpty())
            <div class="mt-6 space-y-3">
                @foreach ($applications as $app)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
                        <div class="min-w-0">
                            @if ($app->jobListing)
                                <a href="{{ route('jobs.show', [$app->jobListing, $app->jobListing->slug]) }}" class="font-semibold text-stone-800 hover:text-emerald-700 dark:text-stone-100 dark:hover:text-emerald-400">{{ $app->jobListing->title }}</a>
                                <div class="text-xs text-stone-400 dark:text-stone-500">{{ $app->jobListing->company?->name }} · {{ $app->created_at->diffForHumans() }}</div>
                            @else
                                <span class="text-stone-500 dark:text-stone-400">İlan kaldırılmış</span>
                            @endif
                        </div>
                        <span @class([
                            'rounded-full px-2.5 py-1 text-xs font-medium',
                            'bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300' => $app->status->value === 'gonderildi',
                            'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300' => $app->status->value === 'incelendi',
                            'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $app->status->value === 'gorusme',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => $app->status->value === 'kabul',
                            'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' => $app->status->value === 'red',
                        ])>{{ $app->status->getLabel() }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $applications->links() }}</div>
        @else
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center dark:border-stone-700 dark:bg-stone-900">
                <div class="text-4xl">🔍</div>
                <h2 class="mt-3 text-lg font-semibold text-stone-800 dark:text-stone-100">Henüz başvurmadın</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Sana uygun işleri keşfet ve başvur.</p>
                <a href="{{ route('jobs.index') }}" class="mt-5 inline-block rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">İş ilanlarına göz at</a>
            </div>
        @endif
    </div>
</x-layouts.app>
