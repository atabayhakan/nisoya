<x-layouts.app title="Yeni İş İlanı — Nisoya">
    <div class="mx-auto max-w-2xl px-4 py-8">
        <x-panel.back-link :href="route('panel.jobs.index')" label="İş ilanlarım" />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Yeni İş İlanı</h1>

        <form method="POST" action="{{ route('panel.jobs.store') }}" class="mt-6 space-y-5 rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
            @csrf
            <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
            @include('panel.jobs.partials.form-fields', ['job' => null])
            <div class="pt-2">
                <button type="submit" class="rounded-lg bg-emerald-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">İlanı yayınla</button>
            </div>
        </form>
    </div>
</x-layouts.app>
