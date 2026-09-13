@props(['navLinks'])

@php
    // Statik girdiler (Faz H2): nav linkleri + sık kullanılan sayfalar.
    $staticEntries = $navLinks
        ->map(fn ($link) => ['category' => 'Menü', 'title' => $link->label, 'url' => $link->url])
        ->push(['category' => 'Sayfa', 'title' => 'İlan Ver', 'url' => route('panel.listings.create')]);

    if (auth()->check()) {
        $staticEntries->push(['category' => 'Sayfa', 'title' => 'Panelim', 'url' => route('dashboard')]);
        $staticEntries->push(['category' => 'Sayfa', 'title' => 'Bildirimler', 'url' => route('panel.notifications.index')]);
    }

    if (! \App\Support\Tema::koyuKilit()) {
        $staticEntries->push(['category' => 'Aksiyon', 'title' => 'Temayı Değiştir', 'action' => 'toggleTheme']);
    }

    $aiRozetAktif = \App\Support\Settings::get('gorunum.header_ai_rozet', '1') === '1' && config('ai.features.nisoya_ai_arama', true);

    $aiSorular = [
        ['etiket' => '🛂 Pasaport Yenileme', 'soru' => 'Pasaport yenileme randevusu ve gerekli evraklar'],
        ['etiket' => '🏛️ Konsolosluk Randevusu', 'soru' => 'Konsolosluk randevusu nasıl alınır ve vekaletname'],
        ['etiket' => '🏦 Hesap Açma', 'soru' => 'Yurt dışında ikametgah ve vergi olmadan banka hesabı'],
        ['etiket' => '⚖️ Türk Avukat', 'soru' => 'Türkçe bilen göçmenlik ve iş hukuku avukatı'],
        ['etiket' => '⚽ Halı Saha Ligi', 'soru' => 'Şehrimde halı saha maçı ve Türk futbol takımları'],
    ];

    $hizliAramalar = [
        ['etiket' => 'Kiralık Ev', 'ikon' => '🏠'],
        ['etiket' => 'İkinci El', 'ikon' => '🏷️'],
        ['etiket' => 'İş İlanları', 'ikon' => '💼'],
        ['etiket' => 'Konsolosluk', 'ikon' => '🏛️'],
        ['etiket' => 'Usta & Tamir', 'ikon' => '🔧'],
        ['etiket' => 'Tercüman', 'ikon' => '📄'],
        ['etiket' => 'Nakliye', 'ikon' => '🚚'],
    ];

    $hizliGezinme = [
        [
            'baslik' => 'İlanlar & Pazar Yeri',
            'aciklama' => 'Tüm kategorilerdeki güncel ilanlara göz at',
            'url' => url('/ilanlar'),
            'ikon' => 'heroicon-o-shopping-bag',
            'renk' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
        ],
        [
            'baslik' => 'İş & Kariyer Fırsatları',
            'aciklama' => 'Açık pozisyonlar ve yetenek havuzunu incele',
            'url' => url('/is-ilanlari'),
            'ikon' => 'heroicon-o-briefcase',
            'renk' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300',
        ],
        [
            'baslik' => 'Resmi Rehber & Konsolosluk',
            'aciklama' => 'Resmi işlemler, randevular ve ülke rehberleri',
            'url' => url('/rehber'),
            'ikon' => 'heroicon-o-book-open',
            'renk' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
        ],
        [
            'baslik' => 'Hizmet Haritası',
            'aciklama' => 'Yakınındaki Türkçe hizmet noktalarını keşfet',
            'url' => url('/harita'),
            'ikon' => 'heroicon-o-map-pin',
            'renk' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300',
        ],
        [
            'baslik' => 'Yeni İlan Yayınla',
            'aciklama' => 'Hemen ücretsiz ilan veya hizmet girişi yap',
            'url' => route('panel.listings.create'),
            'ikon' => 'heroicon-o-plus-circle',
            'renk' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300',
        ],
    ];
@endphp

<div
    x-data="commandPalette(@js($staticEntries->values()))"
    @keydown.cmd.k.window.prevent="openPalette()"
    @keydown.ctrl.k.window.prevent="openPalette()"
    @keydown.escape.window="open && closePalette()"
>
    <button
        type="button"
        @click="openPalette()"
        class="inline-flex h-9 w-9 md:w-full items-center justify-center md:justify-between md:gap-2 rounded-full border border-stone-200/90 bg-stone-50/80 p-0 md:px-3 text-xs font-medium text-stone-500 shadow-2xs transition hover:border-emerald-400 hover:bg-white hover:text-stone-800 shrink-0 dark:border-stone-800 dark:bg-stone-800/60 dark:text-stone-400 dark:hover:border-emerald-600 dark:hover:text-stone-200"
        aria-label="Ara veya AI'ya sor (Cmd/Ctrl+K)"
        title="Ara veya AI'ya sor (Cmd/Ctrl+K)"
    >
        <div class="flex items-center gap-2 min-w-0">
            <x-heroicon-o-magnifying-glass class="h-4 w-4 shrink-0 text-stone-500 dark:text-stone-400" />
            <span class="hidden md:inline truncate text-stone-500 dark:text-stone-400">İlan veya rehber ara...</span>
        </div>
        <div class="hidden items-center gap-1.5 md:flex shrink-0">
            @if ($aiRozetAktif)
                <span class="hidden lg:inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-300/30 dark:border-emerald-700/40">
                    <span class="animate-pulse">✨</span>
                    <span>AI</span>
                </span>
            @endif
            <kbd class="shrink-0 rounded-md border border-stone-200 bg-white px-1.5 py-0.5 text-[10px] font-bold text-stone-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-400">⌘K</kbd>
        </div>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-transition.opacity.duration.150ms
            class="fixed inset-0 z-50 flex items-start justify-center bg-stone-900/60 p-4 pt-[8vh] sm:pt-[12vh] backdrop-blur-sm"
            @click.self="closePalette()"
            role="dialog"
            aria-modal="true"
            aria-label="Hızlı arama ve AI asistan"
            x-cloak
        >
            <div
                x-ref="panel"
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                @keydown.arrow-down.prevent="move(1)"
                @keydown.arrow-up.prevent="move(-1)"
                @keydown.enter.prevent="choose()"
                @keydown.tab="trapFocus($event)"
                class="w-full max-w-2xl overflow-hidden rounded-3xl border border-stone-200/90 bg-white shadow-2xl ring-1 ring-black/5 dark:border-stone-800 dark:bg-stone-900 dark:shadow-stone-950/60"
            >
                {{-- Arama Başlığı & Giriş Kutusu --}}
                <div class="flex items-center gap-3 border-b border-stone-100 px-4 py-3.5 sm:px-5 dark:border-stone-800">
                    <template x-if="!loading && !aiLoading">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-400" />
                    </template>
                    <template x-if="loading || aiLoading">
                        <svg class="h-5 w-5 shrink-0 animate-spin text-emerald-700 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>

                    <input
                        x-ref="input"
                        x-model="query"
                        @input="onInput()"
                        type="text"
                        placeholder="İlan, rehber veya yetenek ara ya da AI'ya sor..."
                        class="w-full border-0 bg-transparent p-0 text-sm sm:text-base font-medium text-stone-900 placeholder-stone-400 focus:outline-none focus:ring-0 dark:text-stone-50 dark:placeholder-stone-500"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="true"
                        aria-controls="command-palette-listbox"
                    >

                    <div class="flex items-center gap-1.5 shrink-0">
                        <button
                            type="button"
                            x-show="query.trim().length >= 3"
                            @click="askAi()"
                            :disabled="aiLoading"
                            class="inline-flex items-center gap-1 rounded-xl bg-linear-to-r from-emerald-600 to-teal-600 px-2.5 py-1 text-2xs font-bold text-white shadow-2xs hover:from-emerald-700 hover:to-teal-700 transition active:scale-95 disabled:opacity-50"
                        >
                            <span x-show="!aiLoading">✨ AI'ya Sor</span>
                            <span x-show="aiLoading" class="flex items-center gap-1">
                                <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span>Düşünüyor...</span>
                            </span>
                        </button>
                        <button
                            type="button"
                            x-show="query.length > 0"
                            @click="clearQuery()"
                            class="grid h-6 w-6 place-items-center rounded-full text-stone-500 hover:bg-stone-100 hover:text-stone-700 dark:hover:bg-stone-800 dark:hover:text-stone-200"
                            title="Temizle"
                        >
                            <x-heroicon-o-x-mark class="h-4 w-4" />
                        </button>
                        <kbd class="hidden rounded-lg border border-stone-200 bg-stone-100/80 px-2 py-1 text-[10px] font-bold text-stone-500 sm:inline dark:border-stone-700 dark:bg-stone-800 dark:text-stone-400">Esc</kbd>
                    </div>
                </div>

                {{-- Arama Gövdesi --}}
                <div id="command-palette-listbox" role="listbox" class="max-h-[60vh] overflow-y-auto p-3 sm:p-4">
                    {{-- AI Yükleniyor Durumu --}}
                    <div x-show="aiLoading" class="p-3.5 border border-emerald-200/80 bg-emerald-50/50 dark:border-emerald-800/40 dark:bg-emerald-950/20 text-center rounded-2xl mb-3" x-cloak>
                        <div class="inline-flex items-center gap-2 text-xs font-bold text-emerald-800 dark:text-emerald-300">
                            <svg class="h-4 w-4 animate-spin text-emerald-700 dark:text-emerald-400" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span>✨ Nisoya AI (Claude) diaspora rehberi ve ilanları inceliyor...</span>
                        </div>
                    </div>

                    {{-- AI Yanıt Kutusu --}}
                    <div x-show="aiResult" class="p-3.5 border border-emerald-300 bg-emerald-50/80 rounded-2xl mb-3 shadow-2xs dark:border-emerald-800 dark:bg-emerald-950/30" x-cloak>
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-900 dark:text-emerald-200">
                                <span>✨ Nisoya AI (Claude) Asistan Yanıtı</span>
                            </span>
                            <button type="button" @click="clearAi()" class="text-2xs font-semibold text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200">Kapat ✕</button>
                        </div>

                        <template x-if="aiResult && aiResult.niyet === 'kapsam_disi'">
                            <p class="text-xs text-amber-800 dark:text-amber-300">Bu işlem T.C. konsolosluk yetki alanı dışındadır. İlgili ülkenin göç dairesine başvurmanız gerekir.</p>
                        </template>

                        <template x-if="aiResult && aiResult.sonuclar && aiResult.sonuclar.length">
                            <div class="space-y-1.5 mt-2">
                                <span class="text-[10px] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Doğrulanmış Rehber İşlemleri:</span>
                                <template x-for="item in aiResult.sonuclar" :key="item.url">
                                    <a :href="item.url" class="flex items-center justify-between rounded-xl bg-white p-2.5 text-xs font-medium text-stone-800 shadow-2xs hover:bg-emerald-50 transition dark:bg-stone-800 dark:text-stone-200">
                                        <div class="min-w-0 pr-2">
                                            <div class="truncate font-bold text-stone-900 dark:text-stone-100" x-text="item.baslik"></div>
                                            <div class="truncate text-[11px] text-stone-500 dark:text-stone-400" x-show="item.altbaslik" x-text="item.altbaslik"></div>
                                        </div>
                                        <span class="text-emerald-700 dark:text-emerald-400 font-bold shrink-0">Görüntüle →</span>
                                    </a>
                                </template>
                            </div>
                        </template>

                        <template x-if="aiResult && aiResult.ilanBaglantisi">
                            <div class="mt-2.5 pt-2 border-t border-emerald-200/60 dark:border-emerald-800/40">
                                <a :href="aiResult.ilanBaglantisi" class="text-xs font-bold text-emerald-700 hover:underline dark:text-emerald-400 inline-flex items-center gap-1">
                                    <span>Eşleşen ilanları listele</span>
                                    <span>→</span>
                                </a>
                            </div>
                        </template>
                    </div>

                    {{-- 1. DURUM: Henüz bir şey yazılmadı (Claude Prompt Çipleri, Popüler Aramalar ve Hızlı Gezinme) --}}
                    <div x-show="query.trim().length === 0" class="space-y-4">
                        {{-- Claude/AI Akıllı Soru Çipleri --}}
                        @if ($aiRozetAktif)
                            <div class="rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50/50 via-white to-teal-50/30 p-3 dark:border-emerald-800/50 dark:bg-emerald-950/20">
                                <div class="mb-2 flex items-center justify-between px-1">
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                                        <span>✨ Nisoya AI (Claude) Akıllı Soru Önerileri</span>
                                    </span>
                                    <span class="text-[10px] text-stone-500 dark:text-stone-400">Tıkla & Sor</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($aiSorular as $as)
                                        <button
                                            type="button"
                                            @click="setQuery('{{ addslashes($as['soru']) }}', true)"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200/80 bg-white/95 px-2.5 py-1.5 text-xs font-semibold text-stone-700 shadow-2xs transition hover:border-emerald-400 hover:bg-emerald-50 hover:text-emerald-800 active:scale-95 dark:border-emerald-800/60 dark:bg-stone-800/90 dark:text-stone-300 dark:hover:border-emerald-600 dark:hover:text-emerald-300"
                                        >
                                            <span>{{ $as['etiket'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        {{-- Popüler / Hızlı Aramalar --}}
                        <div>
                            <div class="mb-2.5 flex items-center justify-between px-1">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">🔥 Popüler Aramalar</span>
                                <span class="text-[11px] text-stone-500 dark:text-stone-400">Tıkla ve ara</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($hizliAramalar as $hizli)
                                    <button
                                        type="button"
                                        @click="setQuery('{{ $hizli['etiket'] }}')"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200/80 bg-stone-50/80 px-3 py-1.5 text-xs font-semibold text-stone-700 transition hover:border-emerald-300 hover:bg-white hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-800/60 dark:text-stone-300 dark:hover:border-emerald-700 dark:hover:text-emerald-300"
                                    >
                                        <span class="text-sm">{{ $hizli['ikon'] }}</span>
                                        <span>{{ $hizli['etiket'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Hızlı Gezinme Kısayolları --}}
                        <div>
                            <div class="mb-2 px-1">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">⚡ Hızlı Gezinme</span>
                            </div>
                            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                                @foreach ($hizliGezinme as $gezinme)
                                    <a
                                        href="{{ $gezinme['url'] }}"
                                        class="group flex items-center gap-3 rounded-2xl border border-stone-100 bg-stone-50/50 p-2.5 transition hover:border-emerald-200 hover:bg-emerald-50/40 dark:border-stone-800/80 dark:bg-stone-850 dark:hover:border-emerald-800 dark:hover:bg-emerald-950/20"
                                    >
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $gezinme['renk'] }} transition group-hover:scale-105">
                                            <x-dynamic-component :component="$gezinme['ikon']" class="h-5 w-5" />
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-xs font-bold text-stone-800 group-hover:text-emerald-800 dark:text-stone-200 dark:group-hover:text-emerald-300">
                                                {{ $gezinme['baslik'] }}
                                            </div>
                                            <div class="truncate text-[11px] text-stone-500 dark:text-stone-400">
                                                {{ $gezinme['aciklama'] }}
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- 2. DURUM: Arama yapıldı ama sonuç yok --}}
                    <div x-show="query.trim().length > 0 && results.length === 0 && !loading" class="py-8 text-center" x-cloak>
                        <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-400">
                            <x-heroicon-o-magnifying-glass class="h-6 w-6" />
                        </div>
                        <h3 class="text-sm font-bold text-stone-800 dark:text-stone-200">
                            "<span x-text="query"></span>" ile eşleşen sonuç bulunamadı
                        </h3>
                        <p class="mx-auto mt-1 max-w-sm text-xs text-stone-500 dark:text-stone-400">
                            Yazımı kontrol edebilir veya doğrudan tüm ilanlar sayfasında genel arama yapabilirsin.
                        </p>
                        <a
                            :href="'/ilanlar?q=' + encodeURIComponent(query.trim())"
                            class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-brand transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400"
                        >
                            <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                            <span>Tüm İlanlarda Ara</span>
                        </a>
                    </div>

                    {{-- 3. DURUM: Canlı ve Eşleşen Sonuçlar --}}
                    <div x-show="query.trim().length > 0 && results.length > 0" class="space-y-1" x-cloak>
                        <div class="mb-2 flex items-center justify-between px-1">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Arama Sonuçları</span>
                            <span class="text-[11px] text-stone-500 dark:text-stone-400" x-text="results.length + ' sonuç'"></span>
                        </div>

                        <template x-for="(item, index) in results" :key="index + '-' + item.title">
                            <a
                                :href="item.url ?? '#'"
                                @click.prevent="choose(index)"
                                @mousemove="activeIndex = index"
                                :class="activeIndex === index ? 'bg-emerald-50/90 border-emerald-300/80 dark:bg-emerald-950/40 dark:border-emerald-800' : 'border-transparent hover:bg-stone-50 dark:hover:bg-stone-800/60'"
                                class="group flex items-center justify-between gap-3 rounded-2xl border px-3 py-2.5 text-sm transition"
                                role="option"
                                :aria-selected="activeIndex === index ? 'true' : 'false'"
                            >
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    {{-- Kategori İkonu --}}
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300 group-hover:scale-105 transition">
                                        <template x-if="item.category === 'AI Asistan'">
                                            <x-heroicon-s-sparkles class="h-4 w-4 text-emerald-600 dark:text-emerald-400 animate-pulse" />
                                        </template>
                                        <template x-if="item.category === 'İş İlanı'">
                                            <x-heroicon-o-briefcase class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                        </template>
                                        <template x-if="item.category === 'Yetenek'">
                                            <x-heroicon-o-user class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                        </template>
                                        <template x-if="item.category === 'Rehber'">
                                            <x-heroicon-o-book-open class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                        </template>
                                        <template x-if="item.category === 'Menü' || item.category === 'Sayfa'">
                                            <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4 text-stone-500 dark:text-stone-400" />
                                        </template>
                                        <template x-if="item.category === 'Aksiyon'">
                                            <x-heroicon-o-sparkles class="h-4 w-4 text-emerald-700 dark:text-emerald-400" />
                                        </template>
                                        <template x-if="!['AI Asistan', 'İş İlanı', 'Yetenek', 'Rehber', 'Menü', 'Sayfa', 'Aksiyon'].includes(item.category)">
                                            <x-heroicon-o-tag class="h-4 w-4 text-emerald-700 dark:text-emerald-400" />
                                        </template>
                                    </span>

                                    <div class="flex min-w-0 flex-col">
                                        <span class="truncate text-xs font-bold text-stone-900 dark:text-stone-100" x-text="item.title"></span>
                                        <span class="truncate text-[11px] text-stone-500 dark:text-stone-400" x-show="item.subtitle" x-text="item.subtitle"></span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="rounded-full bg-stone-100 px-2 py-0.5 text-[10px] font-bold text-stone-600 dark:bg-stone-800 dark:text-stone-300" x-text="item.category"></span>
                                    <x-heroicon-o-arrow-right class="h-3.5 w-3.5 text-stone-500 opacity-0 group-hover:opacity-100 transition" ::class="activeIndex === index ? '!opacity-100 text-emerald-700 dark:text-emerald-400' : ''" />
                                </div>
                            </a>
                        </template>
                    </div>
                </div>

                {{-- Alt Kısayol Bilgi Çubuğu --}}
                <div class="flex items-center justify-between border-t border-stone-100 bg-stone-50/80 px-4 py-2.5 text-[11px] font-medium text-stone-500 dark:border-stone-800/80 dark:bg-stone-900/80 dark:text-stone-400">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1">
                            <kbd class="rounded border border-stone-200 bg-white px-1.5 py-0.5 text-[10px] font-bold shadow-2xs dark:border-stone-700 dark:bg-stone-800">↑↓</kbd>
                            <span>Gezin</span>
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <kbd class="rounded border border-stone-200 bg-white px-1.5 py-0.5 text-[10px] font-bold shadow-2xs dark:border-stone-700 dark:bg-stone-800">↵</kbd>
                            <span>Seç</span>
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <kbd class="rounded border border-stone-200 bg-white px-1.5 py-0.5 text-[10px] font-bold shadow-2xs dark:border-stone-700 dark:bg-stone-800">Esc</kbd>
                            <span>Kapat</span>
                        </span>
                    </div>
                    <span class="hidden sm:inline-flex items-center gap-1 text-[10px] text-stone-500 dark:text-stone-400">
                        <span class="text-emerald-700 dark:text-emerald-400">✨</span>
                        <span>Nisoya AI (Claude) Destekli</span>
                    </span>
                </div>
            </div>
        </div>
    </template>
</div>
