<x-layouts.app>
    {{-- HERO ARKA PLAN MEDYASI — KABLO BAĞLANDI (2026-08-08).

         2026-08-06'da Hero Yöneticisi'nin METİN anahtarları buraya bağlanmıştı
         (aşağıdaki not). Ama MEDYA anahtarları bağlanmadan kaldı: sahip panelden
         arka plan görseli yükleyip kırpabiliyor, karartma ayarlayabiliyor,
         9 noktalı odak seçebiliyordu — ve canlıda hiçbir şey değişmiyordu.
         `App\Support\Hero` 16 metot sunuyor, bu dosya 4'ünü kullanıyordu.

         Yani PR #119'un düzelttiği hatanın yarım kalmış hâliydi: ekranın bir
         yarısı bağlıydı, öbür yarısı hâlâ sessizce hiçbir şey yapmıyordu.

         SÖZLEŞME VİTRİN İLE BİREBİR AYNI (components/vitrin/hero.blade.php):
         aynı `Hero` metotları, aynı `<picture>` + `<video>` işaretlemesi, aynı
         karartma katmanı. İki temanın medya davranışı ayrışmasın — bu dosyalar
         zaten bağımsız yeniden yazım, bir de medya sözleşmesi ayrışırsa
         panelin ne yapacağı temaya göre değişir.

         TEK BİLİNÇLİ FARK — `duzen()` OKUNMUYOR: Vitrin metni medyanın üstüne
         yalnız "sahne" düzeninde koyar, o yüzden orada `koyu = sahne && medya`.
         Klasik hero'nun tek bir düzeni var ve metin her zaman ortada, yani
         medya varsa metin HER ZAMAN onun üstünde. Burada `koyu = medya`.

         GERİYE DÖNÜK UYUM: medya seçilmemişken (`arkaplan_tipi = yok`, varsayılan)
         çıktı eskisiyle BİREBİR aynı — aynı degrade, aynı üç bulanık daire. --}}
    @php
        $hero = \App\Support\Hero::class;
        $heroTip = $hero::arkaplanTipi();
        $heroGorsel = $heroTip === 'gorsel' ? $hero::arkaplanGorseli() : null;
        $heroGorselMobil = $heroTip === 'gorsel' ? $hero::arkaplanGorseli(true) : null;
        $heroVideo = $heroTip === 'video' ? $hero::videoUrl() : null;
        $heroMedya = $heroGorsel !== null || $heroVideo !== null;
        $heroOverlay = $hero::overlay();
    @endphp

    {{-- Hero (Konteyner İçinde / Taşmasız Çerçeveli Düzen) --}}
    <div class="mx-auto max-w-6xl px-4 pt-3 sm:pt-5">
        <section class="relative overflow-hidden rounded-3xl border border-stone-200/80 shadow-xl dark:border-stone-800 {{ $heroMedya ? 'bg-stone-900' : 'bg-gradient-to-b from-emerald-50/90 via-white to-stone-50 dark:from-stone-900 dark:via-stone-900/90 dark:to-stone-950' }}">
            @if ($heroMedya)
                {{-- Arka plan medyası (Hero Yöneticisi). Dekoratif: alt="" +
                     aria-hidden — ekran okuyucuya anlatacak bir şeyi yok. --}}
                <div class="absolute inset-0" aria-hidden="true">
                    @if ($heroGorsel)
                        <picture>
                            @if ($heroGorselMobil && $heroGorselMobil !== $heroGorsel)
                                <source media="(max-width: 639px)" srcset="{{ $heroGorselMobil }}">
                            @endif
                            {{-- fetchpriority=high: bu görsel sayfanın LCP adayı. --}}
                            <img src="{{ $heroGorsel }}" alt="" width="2400" height="1200" fetchpriority="high"
                                 class="h-full w-full object-cover" style="object-position: {{ $hero::odakCss() }}">
                        </picture>
                    @else
                        <video class="h-full w-full object-cover" autoplay muted loop playsinline preload="metadata">
                            <source src="{{ $heroVideo }}" type="video/mp4">
                        </video>
                    @endif
                    @if ($heroOverlay > 0)
                        {{-- Karartma metnin okunabilirliği için. --}}
                        <div class="absolute inset-0 bg-stone-950" style="opacity: {{ $heroOverlay / 100 }}"></div>
                    @endif

                    @if ($hero::metinPaneli())
                        <div class="absolute inset-0 bg-gradient-to-b from-stone-950/85 via-stone-950/60 to-transparent sm:bg-gradient-to-r sm:from-stone-950/80 sm:via-stone-950/45 sm:to-transparent"></div>
                    @endif
                </div>
            @else
                {{-- Soyut arka plan dekorasyonu (saf CSS, görsel dosyası yok) --}}
                <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                    <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-emerald-300/25 blur-3xl dark:bg-emerald-700/15"></div>
                    <div class="absolute -right-16 top-1/4 h-80 w-80 rounded-full bg-amber-200/25 blur-3xl dark:bg-amber-900/10"></div>
                    <div class="absolute -bottom-16 left-1/3 h-64 w-64 rounded-full bg-emerald-200/30 blur-3xl dark:bg-emerald-800/10"></div>
                </div>
            @endif

            <div class="relative z-10 mx-auto max-w-4xl px-4 py-12 sm:py-20 text-center">
                <div class="inline-flex flex-wrap items-center justify-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full px-3.5 py-1 text-xs font-semibold {{ $heroMedya ? 'bg-white/15 text-white backdrop-blur' : 'bg-emerald-100/90 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                        {{ \App\Support\Hero::rozet() }}
                    </span>
                    @if (\App\Support\Modules::enabled('hali_saha'))
                        <a href="{{ route('football.index') }}" class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-medium text-emerald-300 backdrop-blur transition hover:bg-emerald-500/30 dark:bg-emerald-500/20 dark:text-emerald-300">
                            <span>⚽ Şehrinde Halı Saha Ligi Başladı</span>
                        </a>
                    @endif
                </div>

                <h1 class="mt-4 text-3xl sm:text-5xl md:text-6xl tracking-tight {{ $heroMedya ? 'text-white' : 'text-stone-900 dark:text-stone-50' }} {{ \App\Support\Tema::tasarimModu() === 'yeni' ? 'font-serif italic font-normal' : 'font-bold' }}">
                    {{ \App\Support\Hero::baslik() }}<br>
                    <span class="{{ $heroMedya ? 'text-emerald-300' : 'text-emerald-700 dark:text-emerald-400' }}">{{ \App\Support\Hero::vurgu() }}</span> {{ setting('home.hero_satir2') }}
                </h1>
                <p class="mx-auto mt-4 max-w-2xl text-base sm:text-lg {{ $heroMedya ? 'text-white/80' : 'text-stone-600 dark:text-stone-300' }}">
                    {{ \App\Support\Hero::altBaslik() }}
                </p>

                {{-- Hibrit Akıllı Komuta Merkezi: AI Asistanı & İlan Keşfi --}}
                <div x-data="{ aramaModu: 'ai' }" class="mx-auto mt-6 max-w-2xl text-center">
                    {{-- Sekme Değiştirici --}}
                    <div class="mb-3.5 inline-flex rounded-2xl bg-black/40 p-1.5 ring-1 ring-white/15 backdrop-blur-md">
                        <button
                            type="button"
                            @click="aramaModu = 'ai'"
                            :class="aramaModu === 'ai'
                                ? 'bg-emerald-700 text-white shadow-brand dark:bg-emerald-500 dark:text-stone-950 font-bold'
                                : 'text-white/80 hover:text-white font-medium'"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs sm:text-sm transition"
                        >
                            <x-heroicon-s-sparkles class="h-4 w-4 shrink-0" />
                            <span>Nisoya AI Asistanı</span>
                            <span class="rounded-md bg-white/20 px-1.5 py-0.5 text-3xs font-extrabold uppercase tracking-wider text-white">Akıllı</span>
                        </button>
                        <button
                            type="button"
                            @click="aramaModu = 'ilan'"
                            :class="aramaModu === 'ilan'
                                ? 'bg-emerald-700 text-white shadow-brand dark:bg-emerald-500 dark:text-stone-950 font-bold'
                                : 'text-white/80 hover:text-white font-medium'"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs sm:text-sm transition"
                        >
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 shrink-0" />
                            <span>İlan & Hizmet Keşfi</span>
                        </button>
                    </div>

                    {{-- 1. Mod: Nisoya AI Asistanı --}}
                    <div x-show="aramaModu === 'ai'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-98" x-transition:enter-end="opacity-100 scale-100">
                        <x-nisoya-ai-arama :ziyaretci-ulke="$ziyaretciUlke" :show-prompts="true" />
                    </div>

                    {{-- 2. Mod: Klasik İlan ve Hizmet Arama --}}
                    <div x-show="aramaModu === 'ilan'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-98" x-transition:enter-end="opacity-100 scale-100">
                        <form action="{{ url('/ilanlar') }}" method="GET" class="flex flex-col gap-2 rounded-2xl bg-white/95 p-2 shadow-xl ring-1 ring-stone-200/90 backdrop-blur sm:flex-row dark:bg-stone-900/95 dark:ring-stone-700/80">
                            <input type="text" name="q" placeholder="{{ setting('home.arama_placeholder', 'Usta, tercüman, nakliye, ev veya iş ara...') }}"
                                   class="h-11 flex-1 rounded-xl border-0 bg-transparent px-4 text-sm sm:text-base font-medium text-stone-800 placeholder-stone-500 focus:outline-none focus:ring-0 dark:text-stone-100 dark:placeholder-stone-500">
                            <select name="ulke" class="h-11 rounded-xl border-0 bg-stone-100 px-3 text-sm font-medium text-stone-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-stone-800 dark:text-stone-200">
                                <option value="">Tüm ülkeler (60+)</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->code }}" @selected(($ziyaretciUlke?->code ?? '') === $country->code)>
                                        {{ $country->emoji }} {{ $country->name_tr }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="inline-flex h-11 items-center justify-center gap-1.5 rounded-xl bg-emerald-700 px-6 font-bold text-white shadow-brand transition hover:bg-emerald-800 active:scale-[0.98] dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                                <span>Ara</span>
                                <span aria-hidden="true">→</span>
                            </button>
                        </form>

                        {{-- Hızlı diaspora kısayolları --}}
                        <div class="mt-3 flex flex-wrap items-center justify-center gap-1.5 text-xs">
                            <span class="{{ $heroMedya ? 'text-white/80' : 'text-stone-500 dark:text-stone-400' }}">Popüler:</span>
                            <a href="{{ url('/ilanlar?q=nakliye') }}" class="rounded-full px-2.5 py-1 font-medium transition {{ $heroMedya ? 'bg-white/15 text-white hover:bg-white/25' : 'bg-stone-200/70 text-stone-700 hover:bg-stone-300/70 dark:bg-stone-800 dark:text-stone-300' }}">🚚 Nakliyeci</a>
                            <a href="{{ url('/ilanlar?q=tamir') }}" class="rounded-full px-2.5 py-1 font-medium transition {{ $heroMedya ? 'bg-white/15 text-white hover:bg-white/25' : 'bg-stone-200/70 text-stone-700 hover:bg-stone-300/70 dark:bg-stone-800 dark:text-stone-300' }}">🔧 Usta & Tamir</a>
                            <a href="{{ url('/ilanlar?q=tercuman') }}" class="rounded-full px-2.5 py-1 font-medium transition {{ $heroMedya ? 'bg-white/15 text-white hover:bg-white/25' : 'bg-stone-200/70 text-stone-700 hover:bg-stone-300/70 dark:bg-stone-800 dark:text-stone-300' }}">📑 Tercüman</a>
                            @if (\App\Support\Modules::enabled('hali_saha'))
                                <a href="{{ route('football.index') }}" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium transition {{ $heroMedya ? 'bg-emerald-500/25 text-emerald-200 hover:bg-emerald-500/35' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300' }}">⚽ Halı Saha Maçı</a>
                            @endif
                            @if (\App\Support\Modules::enabled('is_ilanlari'))
                                <a href="{{ route('jobs.index') }}" class="rounded-full px-2.5 py-1 font-medium transition {{ $heroMedya ? 'bg-white/15 text-white hover:bg-white/25' : 'bg-stone-200/70 text-stone-700 hover:bg-stone-300/70 dark:bg-stone-800 dark:text-stone-300' }}">💼 İş İlanları</a>
                            @endif
                        </div>
                    </div>

                    {{-- Güven ve Ağ Rozetleri --}}
                    <div class="mt-5 flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5 text-xs font-medium {{ $heroMedya ? 'text-white/80' : 'text-stone-600 dark:text-stone-300' }}">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="text-emerald-500 font-bold">✓</span> 60+ Ülke Diaspora Ağı
                        </span>
                        <span class="hidden sm:inline opacity-30">•</span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="text-emerald-500 font-bold">✓</span> %100 Türkçe Hizmet
                        </span>
                        <span class="hidden sm:inline opacity-30">•</span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="text-emerald-500 font-bold">✓</span> Yapay Zekâ Destekli Rehber
                        </span>
                        <span class="hidden sm:inline opacity-30">•</span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="text-emerald-500 font-bold">✓</span> Güvenli Topluluk
                        </span>
                    </div>
                </div>
            </div>
        </section>
    </div>


    {{-- Bölümler artık SIRALANABİLİR (Faz 2 · G5). Sıra panelden yönetilir
         (Anasayfa Bölümleri sayfası); her bölüm kendi @if görünürlük kapısını
         partial'ının içinde taşır, yani mantık yer değiştirmedi.
         Sıralanamayan bloklar bir SIRA İNDEKSİNE çapalanır (bkz. HomeSections
         ::CAPALAR) — varsayılan sırada sayfa bugünküyle birebir aynıdır. --}}
    @foreach (\App\Support\HomeSections::sirali('klasik') as $sira => $bolum)
        @include('partials.home.'.$bolum)

        @if (\App\Support\HomeSections::capa('klasik', 'nabiz') === $sira)
            @include('partials.home.capa-nabiz')
        @endif
        @if (\App\Support\HomeSections::capa('klasik', 'zone_orta') === $sira)
            @include('partials.home.capa-zone-orta')
        @endif
    @endforeach

    {{-- Alan: sayfa sonu (reklam/duyuru) --}}
    <div class="mx-auto max-w-6xl px-4 pb-6">
        <x-zone zone-key="anasayfa_alt" />
    </div>
</x-layouts.app>
