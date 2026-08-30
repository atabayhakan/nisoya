<x-layouts.app>
    <div class="mx-auto max-w-lg px-4 py-12 text-center">
        <div class="rounded-3xl border border-stone-200 bg-white p-8 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <span class="text-4xl">👑</span>
            <h1 class="mt-4 text-xl font-bold text-stone-900 dark:text-stone-100">
                Maç Planlamak İçin Kaptan Olmalısınız
            </h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
                Nisoya'da maç randevuları ve teklifleri takım kaptanları tarafından yönetilir. Henüz kaptanı olduğunuz aktif bir takım bulunmuyor.
            </p>
            <div class="mt-6 flex flex-col gap-2">
                <a href="{{ route('football.teams.create') }}"
                   class="rounded-2xl bg-emerald-700 py-3 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                    🏆 Yeni Takımını Kur & Kaptan Ol
                </a>
                <a href="{{ route('football.index') }}"
                   class="rounded-2xl bg-stone-100 py-3 text-sm font-semibold text-stone-700 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300">
                    Futbol Ana Sayfasına Dön
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
