@props(['categories', 'countries', 'defaultCountry' => ''])

@if ($categories->isNotEmpty())
    <div
        x-data="{
            open: false,
            ulke: @js($defaultCountry),
            numaralar: @js(\App\Support\AcilNumaralar::harita()),
            rehberliUlkeler: @js(collect($countries)->filter(fn ($u) => !empty($u->rehber_var))->pluck('code')->values()),
            konumDurumu: '',

            /* Seçili ülkenin acil numarası; ülke seçili değilse null. */
            acilNumara() {
                return (this.ulke && this.numaralar[this.ulke]) ? this.numaralar[this.ulke] : null;
            },

            /* Yabancı ülkede adres tarif edememe sorununun ucuz çözümü.
               Panoya koordinat + harita bağlantısı yazar; kullanıcı bunu
               telefonda okur ya da yapıştırır. */
            konumuKopyala() {
                if (! navigator.geolocation) { this.konumDurumu = 'Bu cihaz konum vermiyor'; return; }
                this.konumDurumu = 'Konum alınıyor…';
                navigator.geolocation.getCurrentPosition(
                    async (p) => {
                        const e = p.coords.latitude.toFixed(6), b = p.coords.longitude.toFixed(6);
                        const metin = e + ', ' + b + ' — https://maps.google.com/?q=' + e + ',' + b;
                        try { await navigator.clipboard.writeText(metin); this.konumDurumu = 'Konum kopyalandı ✓'; }
                        catch (_) { this.konumDurumu = e + ', ' + b; }
                        setTimeout(() => { this.konumDurumu = ''; }, 6000);
                    },
                    () => { this.konumDurumu = 'Konum izni verilmedi'; setTimeout(() => { this.konumDurumu = ''; }, 4000); },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            },
        }"
        @keydown.escape.window="open = false"
    >
        {{-- ACİL DÜĞMESİ — ÖNCE ANLAŞILSIN, SONRA TÜRK OLSUN.

             Önceki tasarım ay-yıldızlıydı ve sahip haklı olarak "kimse bunun
             acil durum düğmesi olduğunu anlamaz" dedi. İki sebebi vardı:

             1. ETİKET MOBİLDE GİZLİYDİ (`hidden sm:inline`). Telefonda geriye
                yalnız ikon kalıyordu — ve ay-yıldız "Türk" der, "acil" demez.
                Bir düğmenin ne yaptığını anlatan en güçlü şey kelimenin
                kendisidir; artık her ekranda "Acil" yazıyor.
             2. Bayrak motifi kimlik anlatır, ACİLİYET anlatmaz. Ünlem üçgeni
                kültürden bağımsız okunur; tehlike/dikkat için evrensel işaret.

             Kırmızı kaldı ve şanslı bir örtüşme: hem acil rengi hem bayrak
             kırmızısı (#E30A17). Türklüğü renk taşıyor, anlaşılırlığı ikon ve
             kelime taşıyor. Ay-yıldız modal başlığında duruyor — orada bağlam
             zaten kurulmuş oluyor ("Acil Yardım" başlığının yanında). --}}
        <button
            type="button"
            @click="open = true"
            class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-lg bg-[#E30A17] px-2 py-2 sm:gap-1.5 sm:px-2.5 text-sm font-bold text-white shadow-sm ring-1 ring-inset ring-white/20 transition hover:-translate-y-0.5 hover:bg-[#C10914] hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
            aria-label="Acil yardım — hızlı erişim"
            title="Acil yardım — hızlı erişim"
        >
            <x-heroicon-s-exclamation-triangle class="h-4 w-4 shrink-0" />
            Acil
        </button>

        {{-- x-teleport: modal, header'ın backdrop-blur'unun (position:fixed
        için yeni bir containing block oluşturur) İÇİNDEN kaçıp <body>
        sonuna taşınır — aksi halde "fixed inset-0" header'ın küçük
        yüksekliğine sıkışıp modal neredeyse tamamen ekran dışına taşar. --}}
        <template x-teleport="body">
            <div
                x-show="open"
                x-transition.opacity.duration.200ms
                class="fixed inset-0 z-50 flex items-end justify-center bg-stone-900/60 p-3 sm:items-center sm:p-6"
                @click.self="open = false"
                role="dialog"
                aria-modal="true"
                aria-labelledby="emergency-title"
                x-cloak
            >
                <div
                    x-show="open"
                    x-transition.duration.200ms
                    class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-stone-200 dark:bg-stone-900 dark:ring-stone-800"
                >
                    <div class="flex items-start justify-between gap-4 border-b border-rose-100 bg-rose-50 px-5 py-4 dark:border-rose-900/40 dark:bg-rose-950/30">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#E30A17] text-white">
                                <x-ay-yildiz class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 id="emergency-title" class="text-lg font-bold text-rose-900 dark:text-rose-200">Acil Yardım</h2>
                                <p class="mt-0.5 text-sm text-rose-800/80 dark:text-rose-300/80">Bulunduğun ülkede hızlıca ulaşabileceğin Türkler</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="open = false"
                            class="rounded-full p-1.5 text-rose-700 transition hover:bg-rose-100 dark:text-rose-300 dark:hover:bg-rose-900/40"
                            aria-label="Kapat"
                        >
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    @if ($countries->isNotEmpty())
                        <div class="border-b border-stone-100 px-5 py-3 dark:border-stone-800">
                            <label for="acil-ulke" class="text-xs font-semibold text-stone-500 dark:text-stone-400">Hangi ülkedesin?</label>
                            <select
                                id="acil-ulke"
                                x-model="ulke"
                                class="mt-1 w-full rounded-lg border-stone-200 bg-stone-50 py-2 text-sm text-stone-700 focus:border-rose-400 focus:ring-rose-400 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200"
                            >
                                <option value="">Tüm ülkeler</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->code }}">{{ $country->emoji }} {{ $country->name_tr }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- ÜÇ KATMAN, ACİLİYET SIRASINA GÖRE.

                         Eskiden modal yalnız ilan kategorisi linkleri
                         gösteriyordu. Gerçek bir acil durumda kimse ilan
                         kategorisine tıklamaz; üstelik envanter boşken o
                         linkler boş sayfaya götürüyordu.

                         Sıra hayatî olandan başlar:
                           1. Bulunduğun ülkenin acil numarası (tek dokunuş)
                           2. Konsolosluk — Türk'e özgü olan katman
                           3. Türkçe konuşan yardım (ilanlar), dürüst boş hâl
                    --}}

                    {{-- KATMAN 1 — ÜLKENİN ACİL NUMARASI --}}
                    <div class="border-b border-stone-100 px-5 py-4 dark:border-stone-800">
                        <template x-if="acilNumara()">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Acil servis</p>
                                <div class="mt-2 flex gap-2">
                                    <a :href="'tel:' + acilNumara().genel"
                                       class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-[#E30A17] px-4 py-3 text-lg font-bold text-white shadow-sm transition hover:bg-[#C10914]">
                                        <x-heroicon-s-phone class="h-5 w-5 shrink-0" />
                                        <span x-text="acilNumara().genel"></span>
                                    </a>
                                    <template x-if="acilNumara().polis">
                                        <a :href="'tel:' + acilNumara().polis"
                                           class="flex items-center justify-center gap-2 rounded-xl border border-stone-300 px-4 py-3 text-base font-bold text-stone-800 transition hover:bg-stone-50 dark:border-stone-600 dark:text-stone-100 dark:hover:bg-stone-800">
                                            <span>Polis</span><span x-text="acilNumara().polis"></span>
                                        </a>
                                    </template>
                                </div>
                                <p class="mt-2 text-xs text-stone-600 dark:text-stone-400" x-text="acilNumara().not"></p>
                            </div>
                        </template>
                        <template x-if="! acilNumara()">
                            <p class="text-sm text-stone-600 dark:text-stone-400">
                                Ülkeni seçersen o ülkenin acil numarasını burada gösteririz.
                                Emin değilsen <a href="tel:112" class="font-bold text-[#E30A17] hover:underline">112</a>'yi dene —
                                Avrupa'nın tamamında ve pek çok ülkede yönlendirir.
                            </p>
                        </template>
                    </div>

                    {{-- KATMAN 2 — KONSOLOSLUK (Türk'e özgü olan) --}}
                    @php $ccm = \App\Support\AcilNumaralar::konsoloslukCagriMerkezi(); @endphp
                    <div class="border-b border-stone-100 px-5 py-4 dark:border-stone-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Konsolosluk</p>
                        <a href="tel:{{ $ccm['numara'] }}"
                           class="mt-2 flex items-center gap-3 rounded-xl border border-stone-200 px-4 py-3 transition hover:border-emerald-300 hover:bg-emerald-50 dark:border-stone-700 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/20">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                <x-ay-yildiz class="h-5 w-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block font-bold text-stone-800 dark:text-stone-100">{{ $ccm['gosterim'] }}</span>
                                <span class="block text-xs text-stone-600 dark:text-stone-400">{{ $ccm['aciklama'] }}</span>
                            </span>
                        </a>
                        {{-- Rehber bağlantısı YALNIZ rehberi olan ülkelerde:
                             olmayan bir ülkede bağlantı vermek, acil durumdaki
                             kişiyi boş sayfaya götürmek olurdu. --}}
                        <template x-if="rehberliUlkeler.includes(ulke)">
                            <a :href="'/' + ulke.toLowerCase()"
                               class="mt-2 flex items-center justify-between gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/20">
                                Ülkendeki temsilcilikler ve işlemler
                                <x-heroicon-o-arrow-right class="h-4 w-4 shrink-0" />
                            </a>
                        </template>
                    </div>

                    {{-- KONUM — yabancı ülkede adres tarif edememek gerçek bir
                         sorun; koordinatı kopyalayıp okumak ya da yapıştırmak
                         en hızlı çözüm. İzin istenerek yapılır. --}}
                    <div class="border-b border-stone-100 px-5 py-3 dark:border-stone-800">
                        <button type="button" @click="konumuKopyala()"
                                class="flex w-full items-center gap-2 rounded-xl px-1 py-1.5 text-sm font-semibold text-stone-700 transition hover:text-stone-900 dark:text-stone-300 dark:hover:text-stone-100">
                            <x-heroicon-o-map-pin class="h-4 w-4 shrink-0" />
                            <span x-text="konumDurumu || 'Konumumu kopyala'"></span>
                        </button>
                    </div>

                    {{-- KATMAN 3 — TÜRKÇE KONUŞAN YARDIM --}}
                    <div class="space-y-2 px-5 py-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Türkçe konuşan yardım</p>
                        @foreach ($categories as $cat)
                            <a
                                :href="'{{ route('listings.category', $cat->slug) }}' + (ulke ? '?ulke=' + ulke : '')"
                                class="flex items-center justify-between gap-3 rounded-xl border border-stone-200 px-4 py-3 transition hover:border-rose-300 hover:bg-rose-50 dark:border-stone-700 dark:hover:border-rose-700 dark:hover:bg-rose-950/20"
                            >
                                <span class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300">
                                        <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($cat->icon)" class="h-5 w-5" />
                                    </span>
                                    <span class="font-semibold text-stone-800 dark:text-stone-100">{{ $cat->name }}</span>
                                </span>
                                <x-heroicon-o-arrow-right class="h-4 w-4 text-stone-600" />
                            </a>
                        @endforeach
                        {{-- DÜRÜST BOŞ HÂL: ilan yoksa bunu ÖNCEDEN söyle.
                             Acil durumdaki birini boş sonuç sayfasına
                             göndermek en kötü zamanda hayal kırıklığıdır. --}}
                        <p class="pt-1 text-xs text-stone-600 dark:text-stone-400">
                            Nisoya yeni bir platform; seçtiğin ülkede henüz ilan olmayabilir.
                        </p>
                    </div>

                    <div class="border-t border-stone-100 bg-stone-50 px-5 py-3 text-center text-xs text-stone-500 dark:border-stone-800 dark:bg-stone-800 dark:text-stone-400">
                        Nisoya bir ilan platformudur, acil servis değildir — hayatî bir durumda önce yukarıdaki resmî numarayı ara.
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif
