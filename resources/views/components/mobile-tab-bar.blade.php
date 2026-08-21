@props(['navLinksMega', 'navLinksSingle'])

@php
    // Mesajlar sekmesindeki rozet: karşı taraftan gelen, henüz okunmamış
    // mesaj sayısı (bkz. App\Http\Controllers\MessageController@show — aynı
    // "sender != ben + read_at null" tanımı orada tek tek okundu işaretlenir).
    // Tek kaynak: User::okunmamisMesajSayisi() istek başına bir kez sorgular.
    // Panel de aynı metodu çağırır → panelin bu sinyali 0 ek sorguya mal olur.
    $unreadMessages = auth()->user()?->okunmamisMesajSayisi() ?? 0;
@endphp

{{-- Faz H3: native-app hissi veren sabit alt sekme çubuğu. Panel sayfaları
     dahil HER sayfada gösterilir (bağış FAB'ının aksine — bu sabit bir çubuk,
     kendi yerini ayırır/taşmaz, bkz. app.blade.php'deki body padding'i).

     TEK İSTİSNA: SOHBET EKRANI (2026-08-13, gerçek cihazda görüldü).
     Dar bir telefonda üst menü + başlık kartı + anlaşma paneli + mesaj kutusu
     + yazma alanı + bu çubuk aynı ekrana sığmıyordu; yazma alanı eziliyordu.
     Mesajlaşma tam-ekran bir iş — WhatsApp/Telegram da sohbette sekme çubuğu
     göstermez. Gezinme kaybolmuyor: sayfanın başında "Tüm mesajlar" bağlantısı
     var. Çubuğun yerini tutan gövde dolgusu da sohbet sayfasında sıfırlanıyor
     (bkz. panel/messages/show.blade.php) — iki iskelete birden dokunmamak
     için kural orada, sebebiyle birlikte duruyor. --}}
@if (request()->routeIs('panel.messages.show'))
    @php(null)
@else
<div x-data="altSayfa" @keydown.escape.window="kapat()">
    <nav
        class="fixed inset-x-0 bottom-0 z-30 flex items-stretch justify-around border-t border-stone-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden dark:border-stone-800 dark:bg-stone-900/95"
        aria-label="Alt gezinme"
    >
        <a href="{{ url('/') }}" class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-2xs font-medium {{ request()->routeIs('home') ? 'text-emerald-700 dark:text-emerald-400' : 'text-stone-500 dark:text-stone-400' }}">
            <x-heroicon-o-home class="h-5 w-5" />
            Ana Sayfa
        </a>

        <button
            type="button"
            @click="ac()"
            class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-2xs font-medium text-stone-500 dark:text-stone-400"
            :aria-expanded="acik ? 'true' : 'false'"
            aria-haspopup="dialog"
        >
            <x-heroicon-o-squares-2x2 class="h-5 w-5" />
            Keşfet
        </button>

        @auth
            {{-- İKON + ETİKET (2026-08-21): "+" nazar boncuğuyla değişti (bkz.
                 x-nazar-boncugu gerekçesi), etiket metni kaldırıldı — beş sekme
                 arasında yalnız bu FAB zaten büyük/yükseltilmiş, ikon tek
                 başına ayırt edici. `aria-label` görünür metnin yerini alıyor,
                 ekran okuyucu hâlâ "İlan Ver" duyar. --}}
            <a href="{{ url('/panel/ilan/yeni') }}" aria-label="İlan Ver" class="flex flex-1 flex-col items-center justify-center py-1">
                <span class="-mt-5 grid h-11 w-11 place-items-center rounded-full bg-emerald-700 shadow-lg ring-4 ring-white dark:bg-emerald-500 dark:ring-stone-900">
                    <x-nazar-boncugu class="h-6 w-6" />
                </span>
            </a>
        @else
            {{-- MİSAFİR: YELPAZE (2026-08-21) — eskiden başlıkta ayrı bir "Üye
                 ol" düğmesiydi (bkz. x-mobil-hesap git geçmişi). Taşınma iki
                 sebepten:

                 1. YER. Başlıkta acil+giriş+üye-ol aynı 390px'i paylaşıyordu.
                    Bu FAB zaten alt çubukta, tetikleyicinin ETRAFINDA —
                    özellikle YUKARISINDA — boş sayfa var; dairesel/yelpaze
                    açılım için başlığın köşesinde hiç olmayan bir şey.
                 2. TEKRAR YOK. "İlan Ver" zaten buradaydı; misafir için aynı
                    düğmeye "Giriş" ve "Üye ol"u da yükleyince ayrı bir başlık
                    düğmesine gerek kalmadı — tek FAB, tek yer, dört eylem.

                 SIRA BİLİNÇLİ (miras: eski başlık yelpazesinden): birincil
                 eylem hâlâ ÜYE OLMAK — o öğe daha büyük ve dolu emerald.
                 "İlan Ver" misafir için de register'a gider, `/panel/ilan/yeni`
                 DEĞİL: rota `auth` altında olduğu için misafir oraya dokununca
                 GİRİŞ formuna düşerdi — hesabı olmayan biri için yanlış form.
                 Kayıt sayfasında "Zaten hesabın var mı?" bağlantısı var, dönen
                 kullanıcı kaybolmuyor.

                 ACİL DE BURADA: eskiden başlıkta ayrı kırmızı düğmeydi,
                 mobilde misafir için kaldırıldı (bkz. layouts/app.blade.php)
                 ve tek gerçek kaynağı x-emergency-button'ı `acil-yardim-ac`
                 olayıyla buradan açıyor — numara/konsolosluk mantığı
                 ÇOĞALTILMADI, tek modal tek yerde kalıyor. --}}
            <div
                x-data="{
                    acik: false,
                    ac() { this.acik = true; this.$nextTick(() => this.$refs.ilk?.focus()); },
                    kapat(odakla = false) { this.acik = false; if (odakla) this.$refs.tetik?.focus(); },
                }"
                @keydown.escape.window="acik && kapat(true)"
                @click.outside="kapat()"
                class="relative flex flex-1 flex-col items-center justify-center"
            >
                {{-- İKON + ETİKET (2026-08-21): "+" nazar boncuğuyla değişti,
                     etiket metni kaldırıldı (bkz. yukarıdaki @auth dalındaki
                     aynı gerekçe). Dönen "+/×" ikonu da kalktı: nazar boncuğu
                     45° döndüğünde "kapat" anlamına gelen bir şekle
                     dönüşmüyor (artı gibi), o yüzden sabit duruyor — açık/kapalı
                     durumu zaten `aria-expanded` + yelpazenin kendisi anlatıyor. --}}
                <button
                    type="button"
                    x-ref="tetik"
                    @click="acik ? kapat() : ac()"
                    :aria-expanded="acik ? 'true' : 'false'"
                    aria-controls="misafir-yelpaze"
                    aria-label="İlan Ver"
                    class="flex flex-col items-center justify-center py-1"
                >
                    <span class="-mt-5 grid h-11 w-11 place-items-center rounded-full bg-emerald-700 shadow-lg ring-4 ring-white dark:bg-emerald-500 dark:ring-stone-900">
                        <x-nazar-boncugu class="h-6 w-6" />
                    </span>
                </button>

                {{-- Konum matematiği (macOS Dock "Fan" hissi): 4 öğe, FAB'ın
                     YUKARISINDA yarım dairede, ölçüler sabit (N sabit 4 olduğu
                     için trigonometriyi çalışma anında hesaplamaya gerek yok).
                     `--tx`/`--ty` her öğenin merkezden kayması; satır-içi
                     `transform` animasyondan BAĞIMSIZ olarak zaten o değeri
                     taşıyor, yani `animation: none` olunca (motion-reduce)
                     öğe son karede kalır, başlangıç noktasına dönmez. --}}
                <div
                    id="misafir-yelpaze"
                    x-show="acik"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-100 motion-reduce:transition-none"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute bottom-full left-1/2 z-40"
                >
                    @foreach ([
                        ['route' => 'login', 'etiket' => 'Giriş yap', 'ikon' => 'arrow-right-on-rectangle', 'tx' => -87, 'ty' => -23],
                        ['route' => 'register', 'etiket' => 'Üye ol', 'ikon' => 'user-plus', 'tx' => -38, 'ty' => -82, 'birincil' => true],
                        ['route' => 'register', 'etiket' => 'İlan Ver', 'ikon' => 'plus-circle', 'tx' => 38, 'ty' => -82],
                    ] as $i => $oge)
                        <a
                            href="{{ route($oge['route']) }}"
                            @if ($i === 0) x-ref="ilk" @endif
                            style="--tx: {{ $oge['tx'] }}px; --ty: {{ $oge['ty'] }}px; transform: translateX(-50%) translate(var(--tx), var(--ty)); animation-delay: {{ $i * 45 }}ms"
                            class="yelpaze-oge absolute bottom-0 left-0 flex flex-col items-center gap-1"
                        >
                            <span class="grid {{ ($oge['birincil'] ?? false) ? 'h-14 w-14' : 'h-12 w-12' }} place-items-center rounded-full shadow-lg ring-4 ring-white transition dark:ring-stone-900 {{ ($oge['birincil'] ?? false) ? 'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' : 'bg-white text-emerald-700 dark:bg-stone-800 dark:text-emerald-400' }}">
                                <x-dynamic-component :component="'heroicon-o-'.$oge['ikon']" class="{{ ($oge['birincil'] ?? false) ? 'h-6 w-6' : 'h-5 w-5' }}" />
                            </span>
                            <span class="whitespace-nowrap rounded-full bg-white/95 px-2 py-0.5 text-2xs font-semibold text-stone-700 shadow-sm dark:bg-stone-800/95 dark:text-stone-200">{{ $oge['etiket'] }}</span>
                        </a>
                    @endforeach

                    {{-- ACİL — link değil düğme: modal x-emergency-button'da
                         yaşıyor, burada yalnız olayı fırlatıyoruz. --}}
                    <button
                        type="button"
                        @click="$dispatch('acil-yardim-ac'); kapat()"
                        style="--tx: 87px; --ty: -23px; transform: translateX(-50%) translate(var(--tx), var(--ty)); animation-delay: 135ms"
                        class="yelpaze-oge absolute bottom-0 left-0 flex flex-col items-center gap-1"
                    >
                        <span class="grid h-12 w-12 place-items-center rounded-full bg-[#E30A17] text-white shadow-lg ring-4 ring-white transition dark:ring-stone-900">
                            <x-heroicon-s-exclamation-triangle class="h-5 w-5" />
                        </span>
                        <span class="whitespace-nowrap rounded-full bg-white/95 px-2 py-0.5 text-2xs font-semibold text-rose-700 shadow-sm dark:bg-stone-800/95 dark:text-rose-400">Acil</span>
                    </button>
                </div>
            </div>

            @once
                <style>
                    /* Yelpaze (alt çubuk): macOS Dock "Fan" hissi — öğeler
                       FAB'ın merkezinden yarım daire üstünde belirir, hafif
                       fazla-sıçramalı (spring) easing ile. Yalnız GİRİŞ
                       (opacity/scale) animasyonu var; kapanış sarmalayıcının
                       basit fade'ine bırakılıyor (bkz. mobil-hesap.blade.php
                       tarihindeki aynı karar: bu ölçüde bir yelpaze için
                       ikisi de gerekli değil). */
                    @keyframes ilanYelpazeAc {
                        from { opacity: 0; transform: translateX(-50%) translate(0, 0) scale(.4); }
                        to   { opacity: 1; transform: translateX(-50%) translate(var(--tx), var(--ty)) scale(1); }
                    }
                    .yelpaze-oge {
                        animation: ilanYelpazeAc .28s cubic-bezier(0.34, 1.56, 0.64, 1) both;
                    }
                    @media (prefers-reduced-motion: reduce) {
                        .yelpaze-oge { animation: none; opacity: 1; }
                    }
                </style>
            @endonce
        @endauth

        <a href="{{ route('panel.messages.index') }}" class="relative flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-2xs font-medium {{ request()->routeIs('panel.messages.*') ? 'text-emerald-700 dark:text-emerald-400' : 'text-stone-500 dark:text-stone-400' }}">
            <span class="relative">
                <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
                @if ($unreadMessages)
                    <span class="absolute -right-1.5 -top-1 grid h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-2xs font-bold leading-none text-white">{{ $unreadMessages > 9 ? '9+' : $unreadMessages }}</span>
                @endif
            </span>
            Mesajlar
        </a>

        <a href="{{ route('dashboard') }}" class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-2xs font-medium {{ request()->routeIs('dashboard') ? 'text-emerald-700 dark:text-emerald-400' : 'text-stone-500 dark:text-stone-400' }}">
            <x-heroicon-o-user-circle class="h-5 w-5" />
            Panelim
        </a>
    </nav>

    {{-- "Keşfet" alt sayfası: masaüstü mega menüsüyle aynı group_key verisi
         (bkz. x-nav-link-cards) + tekil linkler (Harita, Nasıl Çalışır?). --}}
    <template x-teleport="body">
        <div
            x-show="acik"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-50 bg-stone-900/60 md:hidden"
            @click.self="kapat()"
            role="dialog"
            aria-modal="true"
            aria-label="Keşfet"
            x-cloak
        >
            <div
                x-show="acik"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="fixed inset-x-0 bottom-0 max-h-[80vh] overflow-y-auto rounded-t-3xl bg-white p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] dark:bg-stone-900"
            >
                <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-stone-200 dark:bg-stone-700"></div>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-bold text-stone-900 dark:text-stone-50">Keşfet</h2>
                    {{-- Kapat düğmesi: eskiden YOKTU. Tek kapanma yolu arka
                         plana dokunmaktı, o da panel ekranın %80'ini kaplayınca
                         ince bir şeride iniyordu. --}}
                    <button type="button" @click="kapat()" class="-mr-1 grid h-11 w-11 place-items-center rounded-full text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800" aria-label="Kapat">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                {{-- Bir karta dokununca sayfa değişir; örtü kapanmadan gitmek
                     gövde kilidini asılı bırakırdı (bkz. altSayfa.destroy). --}}
                <x-nav-link-cards :items="$navLinksMega" grid-class="" on-select="kapat()" />

                @if ($navLinksSingle->isNotEmpty())
                    <div class="mt-3 border-t border-stone-100 pt-3 dark:border-stone-800">
                        @foreach ($navLinksSingle as $link)
                            <a
                                href="{{ $link->url }}"
                                @if ($link->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                @click="kapat()"
                                class="block rounded-xl px-3 py-2.5 text-sm font-medium text-stone-600 hover:bg-stone-50 dark:text-stone-300 dark:hover:bg-stone-800"
                            >{{ $link->label }}</a>
                        @endforeach
                    </div>
                @endif

                {{-- Çıkış.

                     Mobilde çıkış HİÇBİR YERDE yoktu: header'daki düğme
                     `hidden md:block`, sekme çubuğunun beş yuvası da dolu
                     (Ana Sayfa/Keşfet/İlan Ver/Mesajlar/Panelim). Telefonda
                     çıkmanın tek yolu /panel'e gitmekti. Keşfet sayfası her
                     sayfadan tek dokunuşla açılıyor — çıkış için doğru yer
                     burası; profil ekranındaki "Oturum" bölümü ise aramaya
                     oradan başlayanlar için duruyor. --}}
                {{-- TEMA — HERKESE AÇIK.

                     2026-08-05'te başlıktaki tema düğmesi mobilde gizlenip
                     "hesap sayfasında var" denmişti; ama oradaki düğme @auth
                     bloğunun içindeydi. Sonuç: giriş yapmamış mobil ziyaretçi
                     temayı HİÇ değiştiremiyordu. Bir eksiği kapatırken açılan
                     bir gerileme. Keşfet her sayfadan tek dokunuşla açılıyor
                     ve misafire de açık — doğru yer burası. --}}
                @unless (\App\Support\Tema::koyuKilit())
                    <div class="mt-3 border-t border-stone-100 pt-3 dark:border-stone-800">
                        <button type="button" onclick="window.toggleTheme && window.toggleTheme()" class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-stone-600 hover:bg-stone-50 dark:text-stone-300 dark:hover:bg-stone-800">
                            <x-heroicon-o-moon class="h-4 w-4 dark:hidden" />
                            <x-heroicon-o-sun class="hidden h-4 w-4 dark:inline" />
                            Karanlık / aydınlık
                        </button>
                    </div>
                @endunless

                @auth
                    <form method="POST" action="{{ route('logout') }}" class="mt-3 border-t border-stone-100 pt-3 dark:border-stone-800">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-stone-600 hover:bg-stone-50 dark:text-stone-300 dark:hover:bg-stone-800">
                            <x-heroicon-o-arrow-right-start-on-rectangle class="h-4 w-4" />
                            Çıkış yap
                        </button>
                    </form>
                @endauth

                {{-- PWA yükleme ipucu (Faz M1.4). Android'de native yükleme istemi
                     (beforeinstallprompt) bir düğmeye bağlı — gerçek bir eylemi
                     tetikliyor. iOS'ta öyle bir API YOK (Apple desteklemiyor);
                     bu yüzden talimat bir tıklamanın arkasına gizlenmiyor,
                     doğrudan gösteriliyor — aksi halde "Yükle"ye basınca küçük,
                     fark edilmeyen bir metin belirmesi "tepki vermiyor" hissi
                     veriyordu (2026-07 kullanıcı raporu). Yüklüyse veya 30 gün
                     içinde kapatıldıysa hiç görünmez — bkz. resources/js/app.js
                     → Alpine.store('pwa'). --}}
                <template x-if="$store.pwa.visible">
                    <div class="mt-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-3 dark:border-emerald-900 dark:bg-emerald-900/20">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-700 text-white dark:bg-emerald-500">
                                <x-heroicon-o-device-phone-mobile class="h-5 w-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                {{-- iOS'ta gerçek bir "yükleme" yok (Apple beforeinstallprompt'u
                                     desteklemiyor) — sadece Safari'nin "Ana Ekrana Ekle" kısayolu.
                                     Android'deki gibi "uygulama olarak yükle" demek yanlış beklenti
                                     yaratıyordu; iOS'ta metin buna göre değişiyor. --}}
                                <div class="text-sm font-semibold text-stone-800 dark:text-stone-100" x-text="$store.pwa.isIos ? 'Ana ekrana kısayol ekle' : 'Nisoya\'yı uygulama olarak yükle'"></div>
                                <div class="text-xs text-stone-500 dark:text-stone-400" x-text="$store.pwa.isIos ? 'Hızlı erişim için ekranına ekle' : 'Ana ekranından tek dokunuşla aç'"></div>
                            </div>
                            <template x-if="$store.pwa.installEvent">
                                <button
                                    type="button"
                                    @click="$store.pwa.install()"
                                    class="shrink-0 rounded-full bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-800"
                                >Yükle</button>
                            </template>
                            <button type="button" @click="$store.pwa.dismiss()" class="shrink-0 p-1 text-stone-600 hover:text-stone-600 dark:hover:text-stone-200" aria-label="Kapat">
                                <x-heroicon-o-x-mark class="h-4 w-4" />
                            </button>
                        </div>
                        <template x-if="$store.pwa.isIos && !$store.pwa.installEvent">
                            <div class="mt-2 flex items-start gap-1.5 rounded-lg bg-white/70 px-2.5 py-2 text-xs leading-relaxed text-stone-700 dark:bg-stone-900/40 dark:text-stone-300">
                                <x-heroicon-o-arrow-up-on-square class="mt-0.5 h-4 w-4 shrink-0 text-emerald-700 dark:text-emerald-400" />
                                <span>Safari'de alttaki <strong>Paylaş</strong> düğmesine dokun, sonra <strong>"Ana Ekrana Ekle"</strong>yi seç.</span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
@endif
