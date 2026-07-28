<x-layouts.app title="Yeni Davetiye — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <x-panel.back-link :href="route('panel.events.index')" label="Davetiyelerim" />
        <h1 class="mt-2 text-2xl font-bold text-stone-900 dark:text-stone-50">Yeni Davetiye</h1>
        <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">Bilgileri gir — sana özel bir davet linki oluşturalım. Misafirlerin üye olmadan LCV verebilir.</p>

        <form method="POST" action="{{ route('panel.events.store') }}" class="mt-6 space-y-5">
            @csrf
            @include('panel.events.partials.form-fields', ['event' => null])

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                    Davetiyeyi Oluştur
                </button>
                <a href="{{ route('panel.events.index') }}" class="text-sm font-medium text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200">Vazgeç</a>
            </div>
        </form>
    </div>
</x-layouts.app>
