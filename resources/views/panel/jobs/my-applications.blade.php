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
            <x-empty-state
                illustration="inbox"
                title="Henüz başvurmadın"
                description="Sana uygun işleri keşfet ve başvur."
                cta-text="İş ilanlarına göz at"
                :cta-href="route('jobs.index')"
            />
        @endif
    </div>
</x-layouts.app>
