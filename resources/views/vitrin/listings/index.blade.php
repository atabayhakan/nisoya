<x-layouts.app :title="($activeCategory?->name ?? 'Tüm İlanlar').' — Nisoya'" :noindex="$noindex ?? false">
    {{-- VİTRİN İLAN LİSTESİ (P2) — klasik listings/index'in aynı-ad override'ı.
         Korunan sözleşmeler: GET formu route('listings.index')'e gider ve alan
         adları birebir aynıdır (q, tip, kategori, ulke, sehir, min, max,
         uzaktan, sirala); kayıtlı-arama POST formu 5 gizli alanıyla, harita
         linki, zone anahtarı 'ilan_listesi_ust', x-empty-state propları,
         kartlardaki srcset/sizes ve ücretli öne-çıkarma rozeti aynen korunur.
         Kategori sayfası (listings.category) da bu view'ı kullanır.

         Yapı notu: sonuç sütununda POST formu (kayıtlı arama) bulunduğu için
         filtre GET formu iç içe geçemez — üstteki arama alanı ve sıralama
         seçici HTML5 `form="vitrin-filtreler"` özniteliğiyle asıl forma bağlanır. --}}
    @php
        $aktifFiltreler = collect([
            'q' => $filters['q'] ? '"'.$filters['q'].'"' : null,
            'tip' => $filters['tip'] ? match ($filters['tip']) {
                'hizmet' => 'Hizmetler', 'urun' => 'Ürünler', 'emlak' => 'Emlak', 'vasita' => 'Vasıta', default => null,
            } : null,
            'kategori' => $filters['kategori'] ? ($categories->firstWhere('slug', $filters['kategori'])?->name ?? $filters['kategori']) : null,
            'ulke' => $filters['ulke'] ? ($countries->firstWhere('code', $filters['ulke'])?->name_tr ?? $filters['ulke']) : null,
            'sehir' => $filters['sehir'] ?: null,
            // filled(): Laravel'in ConvertEmptyStringsToNull middleware'i boş
            // form alanlarını null'a çevirir — "!== ''" kontrolü bu yüzden
            // yetmez, boş fiyat alanları "aktif filtre" sayılırdı.
            'min' => filled($filters['min']) ? 'En az '.$filters['min'] : null,
            'max' => filled($filters['max']) ? 'En çok '.$filters['max'] : null,
            'uzaktan' => $filters['uzaktan'] ? 'Uzaktan / online' : null,
        ])->filter();

        // Bir filtreyi düşüren URL (diğerleri korunur) — paylaşılabilir link şartı.
        $filtresizUrl = function (string $dusen) use ($filters) {
            $query = array_filter([
                'q' => $filters['q'], 'tip' => $filters['tip'], 'kategori' => $filters['kategori'],
                'ulke' => $filters['ulke'], 'sehir' => $filters['sehir'], 'min' => $filters['min'],
                'max' => $filters['max'], 'uzaktan' => $filters['uzaktan'] ? '1' : '', 'sirala' => $filters['sirala'],
            ], fn ($v, $k) => filled($v) && $k !== $dusen, ARRAY_FILTER_USE_BOTH);

            return route('listings.index', $query);
        };

        // Kategori sayaçları (Faz P4): controller category_id başına adet verir;
        // kök kategoride alt kategorilerin adetleri de toplanır.
        $kategoriAdedi = function ($cat) use ($kategoriSayaclari) {
            if (empty($cat->id)) {
                return 0;
            }
            $toplam = $kategoriSayaclari[$cat->id] ?? 0;
            foreach ($cat->children as $alt) {
                $toplam += $kategoriSayaclari[$alt->id] ?? 0;
            }

            return $toplam;
        };

        $enYuksekKova = ! empty($fiyatDagilimi) ? max($fiyatDagilimi['kovalar']) : 0;
    @endphp

    <div x-data="{ filtreAcik: false }">
        {{-- Yapışkan arama bandı --}}
        <div class="sticky top-0 z-20 border-b border-stone-200/60 bg-white/95 backdrop-blur dark:border-stone-800 dark:bg-stone-900/95">
            <div class="mx-auto flex max-w-6xl items-center gap-2 px-4 py-3">
                <div class="flex h-[46px] min-w-0 flex-1 items-center gap-2 rounded-[13px] border border-stone-200 bg-stone-100 px-3.5 dark:border-stone-700 dark:bg-stone-800">
                    <x-heroicon-o-magnifying-glass class="h-4 w-4 shrink-0 text-stone-700 dark:text-stone-300" />
                    <input type="text" name="q" id="q" form="vitrin-filtreler" value="{{ $filters['q'] }}" placeholder="Anahtar kelime"
                           class="w-full min-w-0 border-0 bg-transparent p-0 text-sm font-semibold text-stone-800 placeholder-stone-400 focus:outline-none focus:ring-0 dark:text-stone-100 dark:placeholder-stone-500">
                    @if ($filters['q'])
                        <a href="{{ $filtresizUrl('q') }}" aria-label="Aramayı temizle"
                           class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-stone-200 text-2xs font-bold text-stone-500 transition hover:bg-stone-300 dark:bg-stone-700 dark:text-stone-300">×</a>
                    @endif
                </div>

                <button type="submit" form="vitrin-filtreler"
                        class="h-[46px] shrink-0 rounded-[13px] bg-emerald-700 px-4 text-sm font-bold text-white transition hover:brightness-95 dark:bg-emerald-500 dark:text-stone-900">
                    Ara
                </button>

                <button type="button" @click="filtreAcik = !filtreAcik" :aria-expanded="filtreAcik"
                        class="inline-flex h-[46px] shrink-0 items-center gap-2 rounded-[13px] border border-stone-300 bg-white px-3.5 text-sm font-bold text-stone-800 transition hover:bg-stone-100 lg:hidden dark:border-stone-700 dark:bg-stone-900 dark:text-stone-100 dark:hover:bg-stone-800">
                    <x-heroicon-o-funnel class="h-3.5 w-3.5" />
                    Filtreler
                    @if ($aktifFiltreler->isNotEmpty())
                        <span class="rounded-full bg-emerald-700 px-1.5 py-[3px] text-2xs font-bold text-white dark:bg-emerald-500 dark:text-stone-900">{{ $aktifFiltreler->count() }}</span>
                    @endif
                </button>
            </div>
        </div>

        <div class="mx-auto max-w-6xl px-4 pb-10 pt-5">
            @if (session('status'))
                <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ session('status') }}</div>
            @endif

            <div class="grid gap-5 lg:grid-cols-[284px_minmax(0,1fr)]">
                {{-- Filtre kolonu (mobilde çekmece — alanlar DOM'da kalır, gizlense de gönderilir) --}}
                <aside :class="filtreAcik ? 'grid' : 'hidden lg:grid'" class="hidden content-start gap-3.5 lg:grid">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-extrabold text-stone-800 dark:text-stone-100">Filtreler</h2>
                        <a href="{{ route('listings.index') }}" class="text-xs font-bold text-emerald-700 hover:underline dark:text-emerald-400">Temizle</a>
                    </div>

                    <form method="GET" action="{{ route('listings.index') }}" id="vitrin-filtreler" class="grid content-start gap-3.5">
                        {{-- Tür --}}
                        <div class="rounded-[18px] border border-stone-200/60 bg-white p-4 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                            <h3 class="text-sm font-bold text-stone-800 dark:text-stone-100">Tür</h3>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ([['', 'Tümü'], ['hizmet', '🧰 Hizmetler'], ['urun', '📦 Ürünler'], ['emlak', '🏡 Emlak'], ['vasita', '🚗 Vasıta']] as [$deger, $etiket])
                                    <label class="cursor-pointer">
                                        <input type="radio" name="tip" value="{{ $deger }}" @checked($filters['tip'] === $deger) class="peer sr-only">
                                        <span class="inline-flex rounded-full border border-stone-200 bg-stone-50 px-2.5 py-1.5 text-xs font-semibold text-stone-700 transition peer-checked:border-emerald-600 peer-checked:bg-emerald-700 peer-checked:text-white peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-emerald-600 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:peer-checked:text-stone-900">{{ $etiket }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Kategori --}}
                        <div class="rounded-[18px] border border-stone-200/60 bg-white p-4 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                            <h3 class="text-sm font-bold text-stone-800 dark:text-stone-100">Kategori</h3>
                            <div class="mt-3 grid max-h-64 gap-2.5 overflow-y-auto pr-1">
                                @foreach (collect([(object) ['slug' => '', 'name' => 'Tüm kategoriler', 'icon' => null]])->concat($categories) as $cat)
                                    <label class="flex cursor-pointer items-center gap-2.5 text-xs font-semibold text-stone-700 transition hover:text-stone-800 dark:text-stone-300 dark:hover:text-stone-100">
                                        <input type="radio" name="kategori" value="{{ $cat->slug }}" @checked($filters['kategori'] === $cat->slug) class="peer sr-only">
                                        <span class="grid h-4 w-4 shrink-0 place-items-center rounded-[5px] border-[1.6px] border-stone-300 text-white transition peer-checked:border-emerald-600 peer-checked:bg-emerald-700 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-emerald-600 dark:border-stone-600">
                                            <x-heroicon-s-check class="h-2.5 w-2.5" />
                                        </span>
                                        <span class="truncate">@if ($cat->icon){{ $cat->icon }} @endif{{ $cat->name }}</span>
                                        {{-- Sayaç (Faz P4): mevcut diğer filtrelerle bu kategoride kaç
                                             ilan olduğu. Kök kategori için alt kategoriler de toplanır. --}}
                                        @if ($cat->slug !== '' && ($catAdet = $kategoriAdedi($cat)) > 0)
                                            <span class="ml-auto shrink-0 text-xs font-semibold text-stone-600 dark:text-stone-400">{{ $catAdet }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Konum --}}
                        <div class="rounded-[18px] border border-stone-200/60 bg-white p-4 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                            <h3 class="text-sm font-bold text-stone-800 dark:text-stone-100">Konum</h3>
                            <label for="ulke" class="mt-3 block text-xs font-semibold text-stone-600">Ülke</label>
                            <select name="ulke" id="ulke" class="mt-1 w-full rounded-[9px] border border-stone-300 bg-white px-2.5 py-2 text-xs font-semibold text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                <option value="">Tüm ülkeler</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->code }}" @selected($filters['ulke'] === $country->code)>{{ $country->emoji }} {{ $country->name_tr }}</option>
                                @endforeach
                            </select>
                            <label for="sehir" class="mt-3 block text-xs font-semibold text-stone-600">Şehir</label>
                            <input type="text" name="sehir" id="sehir" value="{{ $filters['sehir'] }}"
                                   class="mt-1 w-full rounded-[9px] border border-stone-300 bg-white px-2.5 py-2 text-xs font-semibold text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        </div>

                        {{-- Fiyat --}}
                        <div class="rounded-[18px] border border-stone-200/60 bg-white p-4 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                            <div class="flex items-baseline justify-between gap-2">
                                <h3 class="text-sm font-bold text-stone-800 dark:text-stone-100">Fiyat aralığı</h3>
                                @if (! empty($fiyatDagilimi))
                                    <span class="text-xs font-semibold text-stone-500 dark:text-stone-400">
                                        {{ number_format($fiyatDagilimi['min'], 0) }}–{{ number_format($fiyatDagilimi['max'], 0) }}
                                    </span>
                                @endif
                            </div>

                            {{-- Histogram (Faz P4): fiyatlı ilanların 9 kovaya dağılımı.
                                 Anlamlı dağılım yoksa (fiyatlı ilan yok / hepsi aynı fiyat)
                                 controller boş döner ve blok HİÇ basılmaz. Seçili aralıktaki
                                 kovalar birincil renkte. --}}
                            @if (! empty($fiyatDagilimi) && $enYuksekKova > 0)
                                @php
                                    $hMin = $fiyatDagilimi['min'];
                                    $hAdim = ($fiyatDagilimi['max'] - $hMin) / 9;
                                    $secMin = filled($filters['min']) ? (float) $filters['min'] : null;
                                    $secMax = filled($filters['max']) ? (float) $filters['max'] : null;
                                @endphp
                                <div class="mt-3 flex h-[52px] items-end gap-[3px]" aria-hidden="true" data-fiyat-histogrami>
                                    @foreach ($fiyatDagilimi['kovalar'] as $i => $adet)
                                        @php
                                            $kovaAlt = $hMin + $i * $hAdim;
                                            $kovaUst = $kovaAlt + $hAdim;
                                            $secili = ($secMin === null || $kovaUst >= $secMin)
                                                && ($secMax === null || $kovaAlt <= $secMax);
                                            $yukseklik = max(6, (int) round($adet / $enYuksekKova * 100));
                                        @endphp
                                        <div class="vitrin-bar flex-1 rounded-[3px] {{ $secili ? 'bg-emerald-600 dark:bg-emerald-500' : 'bg-stone-200 dark:bg-stone-700' }}"
                                             style="height: {{ $yukseklik }}%; animation-delay: {{ $i * 40 }}ms"></div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-3.5 flex items-center gap-2">
                                <input type="number" name="min" min="0" value="{{ $filters['min'] }}" placeholder="En az" aria-label="En az fiyat"
                                       class="h-[34px] w-full rounded-[9px] border border-stone-300 bg-white px-2.5 text-xs font-semibold text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                <span class="text-stone-600">—</span>
                                <input type="number" name="max" min="0" value="{{ $filters['max'] }}" placeholder="En çok" aria-label="En çok fiyat"
                                       class="h-[34px] w-full rounded-[9px] border border-stone-300 bg-white px-2.5 text-xs font-semibold text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            </div>
                        </div>

                        {{-- Satıcı / sunum --}}
                        <div class="rounded-[18px] border border-stone-200/60 bg-white p-4 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                            <h3 class="text-sm font-bold text-stone-800 dark:text-stone-100">Sunum</h3>
                            <label class="mt-3 flex cursor-pointer items-center justify-between gap-3">
                                <span class="text-xs font-semibold leading-tight text-stone-700 dark:text-stone-300">Sadece uzaktan / online</span>
                                <input type="checkbox" name="uzaktan" value="1" @checked($filters['uzaktan']) class="peer sr-only">
                                <span class="flex h-5 w-[34px] shrink-0 items-center rounded-full bg-stone-300 p-[2px] transition peer-checked:bg-emerald-600 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-emerald-600 peer-checked:[&>span]:translate-x-[14px] dark:bg-stone-600">
                                    <span class="h-4 w-4 rounded-full bg-white transition"></span>
                                </span>
                            </label>
                        </div>

                        <button type="submit" class="h-[42px] rounded-[13px] bg-stone-800 text-sm font-bold text-white transition hover:brightness-110 dark:bg-stone-700">Filtrele</button>
                    </form>

                    {{-- Harita kısayolu --}}
                    <a href="{{ route('listings.map', $filters['tip'] ? ['tip' => $filters['tip']] : []) }}"
                       class="group relative flex h-[150px] items-end justify-center overflow-hidden rounded-[18px] border border-stone-200/60 bg-gradient-to-br from-emerald-50 via-stone-100 to-emerald-50/40 p-3 shadow-brand dark:border-stone-800 dark:from-emerald-950/40 dark:via-stone-900 dark:to-stone-950">
                        <span class="absolute left-[30%] top-[35%] h-[9px] w-[9px] rounded-full bg-emerald-600 shadow-[0_0_0_5px_rgba(62,99,240,.2)]" aria-hidden="true"></span>
                        <span class="vitrin-pulse absolute right-[32%] top-[52%] h-2 w-2 rounded-full bg-[#16a97f] shadow-[0_0_0_5px_rgba(22,169,127,.2)]" aria-hidden="true"></span>
                        <span class="inline-flex h-[38px] items-center gap-2 rounded-[11px] bg-stone-800 px-4 text-xs font-bold text-white transition group-hover:brightness-110 dark:bg-stone-700">
                            <x-heroicon-o-map class="h-4 w-4" /> Haritada gör
                        </span>
                    </a>
                </aside>

                {{-- Sonuç kolonu --}}
                <main>
                    <x-zone zone-key="ilan_listesi_ust" />

                    <div class="mt-1 flex flex-wrap items-end justify-between gap-4">
                        <div class="min-w-0">
                            <nav class="text-xs font-semibold text-stone-500 dark:text-stone-400">
                                <a href="{{ url('/ilanlar') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İlanlar</a>
                                @if ($activeCategory)
                                    <span class="mx-1 text-stone-300 dark:text-stone-400">/</span>
                                    <span class="text-stone-800 dark:text-stone-200">{{ $activeCategory->name }}</span>
                                @endif
                            </nav>
                            <h1 class="mt-2 text-2xl font-extrabold leading-[1.15] tracking-[-0.025em] text-stone-800 dark:text-stone-50">{{ $activeCategory?->name ?? 'Tüm İlanlar' }}</h1>
                            <p class="mt-1.5 text-sm font-medium text-stone-600 dark:text-stone-400">
                                <b class="text-stone-800 dark:text-stone-100">{{ $listings->total() }}</b> ilan bulundu
                                @if ($aktifFiltreler->isNotEmpty())
                                    · <b class="text-stone-800 dark:text-stone-100">{{ $aktifFiltreler->count() }}</b> filtre aktif
                                @endif
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @auth
                                <form method="POST" action="{{ route('saved-searches.store') }}">
                                    @csrf
                                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                                    <input type="hidden" name="kategori" value="{{ $filters['kategori'] }}">
                                    <input type="hidden" name="ulke" value="{{ $filters['ulke'] }}">
                                    <input type="hidden" name="sehir" value="{{ $filters['sehir'] }}">
                                    <input type="hidden" name="tip" value="{{ $filters['tip'] }}">
                                    <button type="submit" class="inline-flex h-[38px] items-center gap-1.5 rounded-[11px] border border-stone-200 bg-white px-3 text-xs font-semibold text-stone-800 transition hover:bg-stone-100 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-100 dark:hover:bg-stone-800">
                                        <x-heroicon-o-bell class="h-4 w-4" /> Aramayı kaydet
                                    </button>
                                </form>
                            @endauth

                            <select name="sirala" form="vitrin-filtreler" onchange="this.form.requestSubmit()" aria-label="Sırala"
                                    class="h-[38px] rounded-[11px] border border-stone-200 bg-white px-3 text-xs font-semibold text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-100">
                                <option value="" @selected($filters['sirala'] === '')>En yeni</option>
                                <option value="fiyat_artan" @selected($filters['sirala'] === 'fiyat_artan')>Fiyat (artan)</option>
                                <option value="fiyat_azalan" @selected($filters['sirala'] === 'fiyat_azalan')>Fiyat (azalan)</option>
                                <option value="populer" @selected($filters['sirala'] === 'populer')>En popüler</option>
                            </select>
                        </div>
                    </div>

                    {{-- Aktif filtre çipleri --}}
                    @if ($aktifFiltreler->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($aktifFiltreler as $anahtar => $etiket)
                                <a href="{{ $filtresizUrl($anahtar) }}"
                                   class="inline-flex items-center gap-2 rounded-full border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-800 transition hover:bg-stone-100 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-100 dark:hover:bg-stone-800">
                                    {{ $etiket }}
                                    <span class="text-stone-600" aria-hidden="true">×</span>
                                    <span class="sr-only">filtresini kaldır</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if ($listings->isNotEmpty())
                        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($listings as $listing)
                                <x-vitrin.listing-card :listing="$listing" />
                            @endforeach
                        </div>
                        <div class="mt-8">
                            {{ $listings->links() }}
                        </div>
                    @else
                        {{-- İKİ AYRI DURUM, İKİ AYRI CEVAP (2026-08-05).

                             Eskiden ikisine de aynı şey deniyordu ve "Tüm
                             ilanları gör" düğmesi filtre yokken KENDİ SAYFASINA
                             dönüyordu — hiçbir ilerleme sağlamayan bir düğme.

                             Pazaryeri gerçekten boşken sorun filtre DEĞİL;
                             kullanıcıya olmayan bir filtreyi değiştirmesini
                             söylemek onu çıkmaza sokar. --}}
                        {{-- `filled()` KULLANILMAZ: BrowseController
                             'uzaktan' => $request->boolean(...) gönderiyor ve
                             filtre yokken bu FALSE olur. Laravel'de
                             filled(false) === true olduğu için filtre hep
                             "dolu" görünüyordu ve boş pazaryerinde bile
                             "filtreleri temizle" yazıyordu. --}}
                        @php($filtreliMi = collect($filters ?? [])->filter(fn ($v) => $v !== null && $v !== '' && $v !== false)->isNotEmpty())
                        <div class="mt-4">
                            @if ($filtreliMi)
                                <x-empty-state
                                    illustration="search"
                                    title="Bu filtrelerle sonuç yok"
                                    description="Aramanı genişletmeyi dene."
                                    cta-text="Filtreleri temizle"
                                    :cta-href="route('listings.index')"
                                />
                            @else
                                <x-empty-state
                                    illustration="listing"
                                    title="Henüz ilan yok"
                                    description="İlk ilanı sen ver — şehrindeki Türk topluluğuna ulaşsın."
                                    cta-text="İlan ver"
                                    :cta-href="route('panel.listings.create')"
                                />
                            @endif
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </div>
</x-layouts.app>
