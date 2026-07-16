<x-layouts.app title="Favorilerim — Nisoya">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <x-panel.back-link />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Favorilerim</h1>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        @if ($listings->isNotEmpty())
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($listings as $listing)
                    @include('partials.listing-card', ['listing' => $listing])
                @endforeach
            </div>
            <div class="mt-8">{{ $listings->links() }}</div>
        @else
            <x-empty-state
                illustration="heart"
                title="Henüz favorin yok"
                description="Beğendiğin ilanları kalbe tıklayarak buraya kaydedebilirsin."
                cta-text="İlanlara göz at"
                :cta-href="route('listings.index')"
            />
        @endif
    </div>
</x-layouts.app>
