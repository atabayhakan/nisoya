<x-layouts.app :title="($activeCategory?->name ?? 'Emlak').' — Nisoya'"
               description="Yurt dışındaki Türklerden kiralık ve satılık konut, kısa dönem tatil evi, oda ve ev arkadaşı ilanları.">
    <div class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">🏡 {{ $activeCategory?->name ?? 'Emlak' }}</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $listings->total() }} ilan · kendi insanından ev bul, kirala, sat</p>
            </div>
            <a href="{{ route('panel.listings.create', ['tip' => 'emlak']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                <x-heroicon-o-plus class="h-4 w-4" /> Emlak İlanı Ver
            </a>
        </div>

        {{-- Kategori sekmeleri --}}
        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('properties.index') }}"
               class="rounded-full px-4 py-1.5 text-sm font-medium transition {{ ! $activeCategory ? 'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' : 'border border-stone-300 text-stone-600 hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800' }}">
                Tümü
            </a>
            @foreach ($categories as $cat)
                <a href="{{ route('properties.index', array_filter(array_merge($filters, ['kategori' => $cat->slug]))) }}"
                   class="rounded-full px-4 py-1.5 text-sm font-medium transition {{ $activeCategory?->slug === $cat->slug ? 'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' : 'border border-stone-300 text-stone-600 hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800' }}">
                    {{ $cat->icon }} {{ $cat->name }}
                </a>
            @endforeach
        </div>

        <div class="mt-6 grid gap-8 lg:grid-cols-4">
            {{-- Filtreler --}}
            <aside class="lg:col-span-1">
                <form method="GET" action="{{ route('properties.index') }}" class="space-y-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    @if ($filters['kategori'])
                        <input type="hidden" name="kategori" value="{{ $filters['kategori'] }}">
                    @endif

                    <div>
                        <label for="q" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Ara</label>
                        <input id="q" name="q" type="text" value="{{ $filters['q'] }}" placeholder="Anahtar kelime"
                               class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
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
                        <label for="oda" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Oda sayısı</label>
                        <select id="oda" name="oda" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <option value="">Farketmez</option>
                            @foreach ($roomOptions as $option)
                                <option value="{{ $option }}" @selected($filters['oda'] === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">Fiyat aralığı</span>
                        <div class="mt-1 flex items-center gap-2">
                            <input name="min" type="number" min="0" value="{{ $filters['min'] }}" placeholder="En az" class="w-full rounded-lg border-stone-300 px-2 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                            <span class="text-stone-600 dark:text-stone-400">—</span>
                            <input name="max" type="number" min="0" value="{{ $filters['max'] }}" placeholder="En çok" class="w-full rounded-lg border-stone-300 px-2 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                        </div>
                    </div>

                    <div>
                        <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">m² aralığı</span>
                        <div class="mt-1 flex items-center gap-2">
                            <input name="min_m2" type="number" min="0" value="{{ $filters['min_m2'] }}" placeholder="En az" class="w-full rounded-lg border-stone-300 px-2 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                            <span class="text-stone-600 dark:text-stone-400">—</span>
                            <input name="max_m2" type="number" min="0" value="{{ $filters['max_m2'] }}" placeholder="En çok" class="w-full rounded-lg border-stone-300 px-2 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                        <input type="checkbox" name="esyali" value="1" @checked($filters['esyali']) class="rounded border-stone-300 text-emerald-700 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
                        Sadece eşyalı
                    </label>

                    <div class="rounded-xl bg-stone-50 p-3 dark:bg-stone-800/60">
                        <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">🧳 Kısa dönem tarih</span>
                        <div class="mt-2 space-y-2">
                            <input name="giris" type="date" value="{{ $filters['giris'] }}" aria-label="Giriş tarihi"
                                   class="w-full rounded-lg border-stone-300 px-2 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <input name="cikis" type="date" value="{{ $filters['cikis'] }}" aria-label="Çıkış tarihi"
                                   class="w-full rounded-lg border-stone-300 px-2 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <input name="kisi" type="number" min="1" max="50" value="{{ $filters['kisi'] }}" placeholder="Kişi sayısı"
                                   class="w-full rounded-lg border-stone-300 px-2 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                        </div>
                        <p class="mt-2 text-xs text-stone-600 dark:text-stone-400">Seçilen tarihlerde müsait ilanlar gösterilir.</p>
                    </div>

                    <div>
                        <label for="sirala" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Sırala</label>
                        <select id="sirala" name="sirala" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <option value="" @selected($filters['sirala'] === '')>En yeni</option>
                            <option value="fiyat_artan" @selected($filters['sirala'] === 'fiyat_artan')>Fiyat (artan)</option>
                            <option value="fiyat_azalan" @selected($filters['sirala'] === 'fiyat_azalan')>Fiyat (azalan)</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <button type="submit" class="flex-1 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Filtrele</button>
                        <a href="{{ route('properties.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">Temizle</a>
                    </div>
                </form>
            </aside>

            {{-- Sonuçlar --}}
            <main class="lg:col-span-3">
                {{-- Alan: emlak liste üstü (reklam/duyuru) --}}
                <div class="mb-4">
                    <x-zone zone-key="emlak_liste_ust" />
                </div>

                @if ($listings->isNotEmpty())
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($listings as $listing)
                            @include('partials.listing-card', ['listing' => $listing])
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $listings->links() }}</div>
                @else
                    <x-empty-state
                        illustration="listing"
                        title="Henüz ilan yok"
                        description="Bu filtrelerle sonuç bulunamadı — ilk emlak ilanını sen ver, binlerce kişiye ulaşsın."
                        cta-text="Emlak İlanı Ver"
                        :cta-href="route('panel.listings.create', ['tip' => 'emlak'])"
                    />
                @endif
            </main>
        </div>
    </div>
</x-layouts.app>
