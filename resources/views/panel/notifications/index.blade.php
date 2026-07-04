<x-layouts.app title="Bildirimler — Nisoya">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <x-panel.back-link />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Bildirimler</h1>

        @if ($notifications->isNotEmpty())
            <div class="mt-6 divide-y divide-stone-100 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:divide-stone-800 dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
                @foreach ($notifications as $n)
                    @php($d = $n->data)
                    <a href="{{ $d['url'] ?? '#' }}" class="flex items-start gap-3 px-4 py-3 transition hover:bg-stone-50 dark:hover:bg-stone-800 {{ is_null($n->read_at) ? 'bg-emerald-50/50 dark:bg-emerald-900/20' : '' }}">
                        <span class="text-xl">{{ $d['icon'] ?? '🔔' }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-stone-800 dark:text-stone-100">{{ $d['title'] ?? 'Bildirim' }}</div>
                            @if (! empty($d['body']))
                                <div class="truncate text-sm text-stone-500 dark:text-stone-400">{{ $d['body'] }}</div>
                            @endif
                            <div class="mt-0.5 text-xs text-stone-400 dark:text-stone-500">{{ $n->created_at->diffForHumans() }}</div>
                        </div>
                        @if (is_null($n->read_at))<span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>@endif
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $notifications->links() }}</div>
        @else
            <div class="mt-10 rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center dark:border-stone-700 dark:bg-stone-900">
                <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                    <x-heroicon-o-bell class="h-6 w-6" />
                </span>
                <h2 class="mt-3 text-lg font-semibold text-stone-800 dark:text-stone-100">Bildirim yok</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Yeni mesaj, değerlendirme ve ilan güncellemeleri burada görünecek.</p>
            </div>
        @endif
    </div>
</x-layouts.app>
