<x-layouts.app title="İş İlanları — Nisoya">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">İş İlanları</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Yurt dışındaki Türk işverenlerin açık pozisyonları.</p>
            </div>
            @auth
                <a href="{{ route('panel.company.edit') }}" class="rounded-lg border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-500 dark:text-emerald-400 dark:hover:bg-emerald-950/30">İşveren misin? İlan ver</a>
            @endauth
        </div>

        {{-- Filtreler --}}
        <form method="GET" class="mt-5 grid grid-cols-1 gap-3 rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900 sm:grid-cols-2 lg:grid-cols-5">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Pozisyon, şirket..."
                   class="rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 lg:col-span-2">
            <select name="kategori" class="rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                <option value="">Tüm kategoriler</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->slug }}" @selected(($filters['kategori'] ?? '') === $c->slug)>{{ $c->name }}</option>
                @endforeach
            </select>
            <select name="tip" class="rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                <option value="">Tüm çalışma tipleri</option>
                @foreach ($employmentTypes as $t)
                    <option value="{{ $t->value }}" @selected(($filters['tip'] ?? '') === $t->value)>{{ $t->getLabel() }}</option>
                @endforeach
            </select>
            <select name="ulke" class="rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                <option value="">Tüm ülkeler</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->code }}" @selected(($filters['ulke'] ?? '') === $country->code)>{{ $country->emoji }} {{ $country->name_tr }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm text-stone-600 dark:text-stone-300">
                <input type="checkbox" name="uzaktan" value="1" @checked(! empty($filters['uzaktan'])) class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
                Sadece uzaktan
            </label>
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900 lg:col-span-1">Filtrele</button>
        </form>

        {{-- Alan: liste üstü (reklam/duyuru) --}}
        <div class="mt-4">
            <x-zone zone-key="is_ilanlari_liste_ust" />
        </div>

        {{-- Sonuçlar --}}
        @if ($jobs->isNotEmpty())
            <p class="mt-6 text-sm text-stone-500 dark:text-stone-400">{{ $jobs->total() }} ilan bulundu</p>
            <div class="mt-3 grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($jobs as $job)
                    @include('partials.job-card', ['job' => $job])
                @endforeach
            </div>
            <div class="mt-8">{{ $jobs->links() }}</div>
        @else
            <div class="mt-10 rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center dark:border-stone-700 dark:bg-stone-900">
                <div class="text-4xl">💼</div>
                <h2 class="mt-3 text-lg font-semibold text-stone-800 dark:text-stone-100">İlan bulunamadı</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Filtreleri değiştirmeyi dene ya da daha sonra tekrar bak.</p>
            </div>
        @endif
    </div>
</x-layouts.app>
