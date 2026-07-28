<x-layouts.app title="Yetenek Havuzu — Nisoya">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Yetenek Havuzu</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">İşe alım mı yapıyorsun? Nisoya üyelerinin yetenek ve portfolyolarını keşfet.</p>
            </div>
            @auth
                @unless (auth()->user()->company)
                    <a href="{{ route('panel.company.edit') }}" class="rounded-lg border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-500 dark:text-emerald-400 dark:hover:bg-emerald-950/30">İşveren misin? Şirket profili oluştur</a>
                @endunless
            @endauth
        </div>

        {{-- Filtreler --}}
        <form method="GET" class="mt-5 grid grid-cols-1 gap-3 rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900 sm:grid-cols-2 lg:grid-cols-5">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="İsim, yetenek, bio..."
                   class="rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 lg:col-span-2">
            <select name="kategori" class="rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                <option value="">Tüm kategoriler</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->slug }}" @selected(($filters['kategori'] ?? '') === $c->slug)>{{ $c->name }}</option>
                @endforeach
            </select>
            <select name="ulke" class="rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                <option value="">Tüm ülkeler</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->code }}" @selected(($filters['ulke'] ?? '') === $country->code)>{{ $country->emoji }} {{ $country->name_tr }}</option>
                @endforeach
            </select>
            <input type="text" name="sehir" value="{{ $filters['sehir'] ?? '' }}" placeholder="Şehir"
                   class="rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900 lg:col-span-1">Filtrele</button>
        </form>

        {{-- Sonuçlar --}}
        @if ($candidates->isNotEmpty())
            <p class="mt-6 text-sm text-stone-500 dark:text-stone-400">{{ $candidates->total() }} üye bulundu</p>
            <div class="mt-3 grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($candidates as $candidate)
                    @include('partials.candidate-card', ['candidate' => $candidate])
                @endforeach
            </div>
            <div class="mt-8">{{ $candidates->links() }}</div>
        @else
            <x-empty-state
                illustration="search"
                title="Kimse bulunamadı"
                description="Filtreleri değiştirmeyi dene ya da daha sonra tekrar bak."
            />
        @endif
    </div>
</x-layouts.app>
