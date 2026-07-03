<x-layouts.app title="Aramalarım — Nisoya">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <x-panel.back-link />
        <h1 class="text-2xl font-bold text-stone-900">Kayıtlı Aramalarım</h1>
        <p class="mt-1 text-sm text-stone-500">Bu aramalara uyan yeni ilanlar çıkınca sana e-posta ile haber veririz.</p>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @if ($searches->isNotEmpty())
            <div class="mt-6 space-y-3">
                @foreach ($searches as $s)
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                        <div class="min-w-0">
                            <div class="font-medium text-stone-800">🔔 {{ $s->label }}</div>
                            <div class="text-xs text-stone-400">{{ $s->created_at->diffForHumans() }} kaydedildi</div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <a href="{{ route('listings.index', $s->toQueryParams()) }}" class="rounded-lg border border-stone-300 px-3 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-50">Ara</a>
                            <form method="POST" action="{{ route('saved-searches.destroy', $s) }}" onsubmit="return confirm('Bu kayıtlı aramayı silmek istediğine emin misin?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg px-2 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50">Sil</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="mt-10 rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center">
                <div class="text-4xl">🔔</div>
                <h2 class="mt-3 text-lg font-semibold text-stone-800">Kayıtlı araman yok</h2>
                <p class="mt-1 text-sm text-stone-500">İlanlarda filtre uygula, sonra "Aramayı kaydet" ile buraya ekle.</p>
                <a href="{{ route('listings.index') }}" class="mt-5 inline-block rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">İlanlara git</a>
            </div>
        @endif
    </div>
</x-layouts.app>
