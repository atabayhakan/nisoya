<x-layouts.app title="İş Yer İmlerim — Nisoya">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <x-panel.back-link />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">İş Yer İmlerim</h1>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        @if ($jobs->isNotEmpty())
            <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($jobs as $job)
                    @include('partials.job-card', ['job' => $job])
                @endforeach
            </div>
            <div class="mt-8">{{ $jobs->links() }}</div>
        @else
            <x-empty-state
                illustration="heart"
                title="Henüz yer imin yok"
                description="Beğendiğin iş ilanlarını yer imi butonuna tıklayarak buraya kaydedebilirsin."
                cta-text="İş ilanlarına göz at"
                :cta-href="route('jobs.index')"
            />
        @endif
    </div>
</x-layouts.app>
