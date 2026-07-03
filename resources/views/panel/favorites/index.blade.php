<x-layouts.app title="Favorilerim — Nisoya">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <x-panel.back-link />
        <h1 class="text-2xl font-bold text-stone-900">Favorilerim</h1>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @if ($listings->isNotEmpty())
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($listings as $listing)
                    @include('partials.listing-card', ['listing' => $listing])
                @endforeach
            </div>
            <div class="mt-8">{{ $listings->links() }}</div>
        @else
            <div class="mt-10 rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center">
                <div class="text-4xl">🤍</div>
                <h2 class="mt-3 text-lg font-semibold text-stone-800">Henüz favorin yok</h2>
                <p class="mt-1 text-sm text-stone-500">Beğendiğin ilanları kalbe tıklayarak buraya kaydedebilirsin.</p>
                <a href="{{ route('listings.index') }}" class="mt-5 inline-block rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">İlanlara göz at</a>
            </div>
        @endif
    </div>
</x-layouts.app>
