<x-layouts.app title="İlanı Düzenle — Nisoya">
    <div class="mx-auto max-w-2xl px-4 py-8">
        <x-panel.back-link :href="route('panel.jobs.index')" label="İş ilanlarım" />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">İlanı Düzenle</h1>

        <form method="POST" action="{{ route('panel.jobs.update', $job) }}" class="mt-6 space-y-5 rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
            @csrf
            @method('PUT')
            @include('panel.jobs.partials.form-fields', ['job' => $job])
            <div class="pt-2">
                <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Güncelle</button>
            </div>
        </form>
    </div>
</x-layouts.app>
