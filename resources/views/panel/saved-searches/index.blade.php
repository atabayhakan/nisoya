<x-layouts.app title="Aramalarım — Nisoya">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <x-panel.back-link />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Kayıtlı Aramalarım</h1>
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Bu aramalara uyan yeni ilanlar çıkınca sana e-posta ile haber veririz.</p>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        @if ($searches->isNotEmpty())
            <div class="mt-6 space-y-3">
                @foreach ($searches as $s)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 truncate font-medium text-stone-800 dark:text-stone-100"><x-heroicon-o-bookmark class="h-4 w-4 shrink-0 text-emerald-700 dark:text-emerald-400" /> {{ $s->label }}</div>
                            <div class="text-xs text-stone-600 dark:text-stone-400">{{ $s->created_at->diffForHumans() }} kaydedildi</div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <a href="{{ route('listings.index', $s->toQueryParams()) }}" class="rounded-lg border border-stone-300 px-3 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800">Ara</a>
                            <form method="POST" action="{{ route('saved-searches.destroy', $s) }}" onsubmit="return confirm('Bu kayıtlı aramayı silmek istediğine emin misin?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg px-2 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30">Sil</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <x-empty-state
                illustration="search"
                title="Kayıtlı araman yok"
                description='İlanlarda filtre uygula, sonra "Aramayı kaydet" ile buraya ekle.'
                cta-text="İlanlara git"
                :cta-href="route('listings.index')"
            />
        @endif
    </div>
</x-layouts.app>
