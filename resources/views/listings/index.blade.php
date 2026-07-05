<x-layouts.app :title="($activeCategory?->name ?? 'Tüm İlanlar').' — Nisoya'">
    <div class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">{{ $activeCategory?->name ?? 'Tüm İlanlar' }}</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $listings->total() }} ilan bulundu</p>
            </div>
            <div class="flex items-center gap-2">
                @auth
                    <form method="POST" action="{{ route('saved-searches.store') }}">
                        @csrf
                        <input type="hidden" name="q" value="{{ $filters['q'] }}">
                        <input type="hidden" name="kategori" value="{{ $filters['kategori'] }}">
                        <input type="hidden" name="ulke" value="{{ $filters['ulke'] }}">
                        <input type="hidden" name="sehir" value="{{ $filters['sehir'] }}">
                        <input type="hidden" name="tip" value="{{ $filters['tip'] }}">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/40">
                            <x-heroicon-o-bell class="h-4 w-4" /> Aramayı kaydet
                        </button>
                    </form>
                @endauth
                <a href="{{ route('listings.map', $filters['tip'] ? ['tip' => $filters['tip']] : []) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800">
                    <x-heroicon-o-map class="h-4 w-4" /> Haritada gör
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-8 lg:grid-cols-4">
            {{-- Filtreler --}}
            <aside class="lg:col-span-1">
                <form method="GET" action="{{ route('listings.index') }}" class="space-y-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <div>
                        <label for="q" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Ara</label>
                        <input id="q" name="q" type="text" value="{{ $filters['q'] }}" placeholder="Anahtar kelime"
                               class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                    </div>

                    <div>
                        <label for="tip" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Tür</label>
                        <select id="tip" name="tip" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <option value="">Tümü</option>
                            <option value="hizmet" @selected($filters['tip'] === 'hizmet')>🧰 Hizmetler</option>
                            <option value="urun" @selected($filters['tip'] === 'urun')>📦 Ürünler</option>
                        </select>
                    </div>

                    <div>
                        <label for="kategori" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Kategori</label>
                        <select id="kategori" name="kategori" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <option value="">Tüm kategoriler</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->slug }}" @selected($filters['kategori'] === $cat->slug)>{{ $cat->icon }} {{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="ulke" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Ülke</label>
                        <select id="ulke" name="ulke" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <option value="">Tüm ülkeler</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->code }}" @selected($filters['ulke'] === $country->code)>{{ $country->emoji }} {{ $country->name_tr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="sehir" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Şehir</label>
                        <input id="sehir" name="sehir" type="text" value="{{ $filters['sehir'] }}"
                               class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>

                    <div>
                        <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">Fiyat aralığı</span>
                        <div class="mt-1 flex items-center gap-2">
                            <input name="min" type="number" min="0" value="{{ $filters['min'] }}" placeholder="En az" class="w-full rounded-lg border-stone-300 px-2 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                            <span class="text-stone-400 dark:text-stone-500">—</span>
                            <input name="max" type="number" min="0" value="{{ $filters['max'] }}" placeholder="En çok" class="w-full rounded-lg border-stone-300 px-2 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                        <input type="checkbox" name="uzaktan" value="1" @checked($filters['uzaktan']) class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
                        Sadece uzaktan / online
                    </label>

                    <div>
                        <label for="sirala" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Sırala</label>
                        <select id="sirala" name="sirala" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <option value="" @selected($filters['sirala'] === '')>En yeni</option>
                            <option value="fiyat_artan" @selected($filters['sirala'] === 'fiyat_artan')>Fiyat (artan)</option>
                            <option value="fiyat_azalan" @selected($filters['sirala'] === 'fiyat_azalan')>Fiyat (azalan)</option>
                            <option value="populer" @selected($filters['sirala'] === 'populer')>En popüler</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <button type="submit" class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Filtrele</button>
                        <a href="{{ route('listings.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-stone-500 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">Temizle</a>
                    </div>
                </form>
            </aside>

            {{-- Sonuçlar --}}
            <main class="lg:col-span-3">
                {{-- Alan: liste üstü (reklam/duyuru) --}}
                <div class="mb-4">
                    <x-zone zone-key="ilan_listesi_ust" />
                </div>

                @if ($listings->isNotEmpty())
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($listings as $listing)
                            @include('partials.listing-card', ['listing' => $listing])
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $listings->links() }}</div>
                @else
                    <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center dark:border-stone-700 dark:bg-stone-900">
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-stone-100 text-stone-400 dark:bg-stone-800 dark:text-stone-500">
                            <x-heroicon-o-magnifying-glass class="h-6 w-6" />
                        </span>
                        <h2 class="mt-3 text-lg font-semibold text-stone-800 dark:text-stone-100">Sonuç bulunamadı</h2>
                        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Filtreleri değiştirmeyi veya temizlemeyi dene.</p>
                        <a href="{{ route('listings.index') }}" class="mt-5 inline-block rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-brand dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Tüm ilanları gör</a>
                    </div>
                @endif
            </main>
        </div>
    </div>
</x-layouts.app>
