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
        class="fixed inset-x-0 bottom-0 z-30 flex items-stretch justify-around border-t border-stone-200/90 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-md md:hidden dark:border-stone-800 dark:bg-stone-900/95"
        aria-label="Alt gezinme"
    >
        <a href="{{ url('/') }}" class="flex flex-1 flex-col items-center justify-center gap-1 py-2 text-[11px] font-semibold transition {{ request()->routeIs('home') ? 'text-emerald-700 dark:text-emerald-400' : 'text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200' }}">
            <x-heroicon-o-home class="h-5 w-5 {{ request()->routeIs('home') ? 'stroke-[2.2]' : '' }}" />
            <span>Ana Sayfa</span>
        </a>

        <button
            type="button"
            @click="ac()"
            class="flex flex-1 flex-col items-center justify-center gap-1 py-2 text-[11px] font-semibold text-stone-500 transition hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200"
            :aria-expanded="acik ? 'true' : 'false'"
            aria-haspopup="dialog"
        >
            <x-heroicon-o-squares-2x2 class="h-5 w-5" />
            <span>Keşfet</span>
        </button>

        @auth
            {{-- İKON + ETİKET: Nazar boncuğu yükseltilmiş FAB --}}
            <a href="{{ url('/panel/ilan/yeni') }}" aria-label="İlan Ver" class="group flex flex-1 flex-col items-center justify-center py-1">
                <span class="-mt-5 grid h-12 w-12 place-items-center rounded-full bg-gradient-to-tr from-emerald-700 to-emerald-500 shadow-brand ring-4 ring-white transition duration-200 group-hover:scale-105 group-active:scale-95 dark:from-emerald-600 dark:to-emerald-400 dark:ring-stone-900">
                    <x-nazar-boncugu class="h-10 w-10 transition duration-300 group-hover:rotate-12" />
                </span>
            </a>
        @else
            <div
                x-data="{
                    acik: false,
                    ac() { this.acik = true; this.$nextTick(() => this.$refs.ilk?.focus()); },
                    kapat(odakla = false) { this.acik = false; if (odakla) this.$refs.tetik?.focus(); },
                }"
                @keydown.escape.window="acik && kapat(true)"
                class="relative flex flex-1 flex-col items-center justify-center"
            >
                <button
                    type="button"
                    x-ref="tetik"
                    @click="acik ? kapat() : ac()"
                    :aria-expanded="acik ? 'true' : 'false'"
                    aria-controls="misafir-yelpaze"
                    aria-label="İlan Ver"
                    class="group flex flex-col items-center justify-center py-1 outline-none"
                >
                    <span class="-mt-5 grid h-12 w-12 place-items-center rounded-full bg-gradient-to-tr from-emerald-700 to-emerald-500 shadow-brand ring-4 ring-white transition duration-200 group-hover:scale-105 group-active:scale-95 dark:from-emerald-600 dark:to-emerald-400 dark:ring-stone-900">
                        <x-nazar-boncugu class="h-10 w-10 transition duration-300 group-hover:rotate-12" />
                    </span>
                </button>

                <template x-teleport="body">
                    <div
                        x-show="acik"
                        x-transition.opacity.duration.200ms
                        class="fixed inset-0 z-50 flex items-end justify-center bg-stone-900/60 p-4 pb-[calc(5rem+env(safe-area-inset-bottom))] backdrop-blur-xs md:hidden"
                        @click.self="kapat()"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Hızlı İşlemler"
                        x-cloak
                    >
                        <div
                            id="misafir-yelpaze"
                            x-show="acik"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                            class="w-full max-w-sm overflow-hidden rounded-3xl border border-stone-200/90 bg-white p-4 shadow-2xl ring-1 ring-black/5 dark:border-stone-800 dark:bg-stone-900"
                        >
                            {{-- Başlık --}}
                            <div class="mb-3 flex items-center justify-between pb-2 border-b border-stone-100 dark:border-stone-800">
                                <div class="flex items-center gap-2">
                                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                        <x-nazar-boncugu class="h-5 w-5" />
                                    </span>
                                    <span class="text-xs font-bold text-stone-900 dark:text-stone-50">Hızlı İşlemler</span>
                                </div>
                                <button type="button" @click="kapat()" class="-mr-1 grid h-8 w-8 place-items-center rounded-full text-stone-500 hover:bg-stone-100 hover:text-stone-800 dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-stone-200" aria-label="Kapat">
                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                </button>
                            </div>

                            <div class="space-y-2">
                                {{-- 1. İlan Ver (Primary Banner) --}}
                                <a
                                    href="{{ route('register') }}"
                                    class="yelpaze-oge flex items-center justify-between gap-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-700 p-3 text-white shadow-brand transition active:scale-[0.98] hover:from-emerald-700 hover:to-emerald-800 dark:from-emerald-600 dark:to-emerald-700"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white/20 text-white">
                                            <x-heroicon-o-plus-circle class="h-6 w-6 stroke-2" />
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-white">İlan Ver</div>
                                            <div class="truncate text-[10px] text-emerald-100">Yeni ilan oluştur veya hizmet listele</div>
                                        </div>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-bold text-white">Ücretsiz</span>
                                </a>

                                {{-- 2. Giriş & Üye Ol (2 Kolon) --}}
                                <div class="grid grid-cols-2 gap-2">
                                    <a
                                        href="{{ route('register') }}"
                                        x-ref="ilk"
                                        class="yelpaze-oge flex flex-col items-center gap-1.5 rounded-2xl border border-stone-200/90 bg-stone-50/80 p-3 text-center transition hover:border-emerald-300 hover:bg-white active:scale-[0.98] dark:border-stone-800 dark:bg-stone-800/60 dark:hover:border-emerald-600"
                                    >
                                        <span class="grid h-9 w-9 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300">
                                            <x-heroicon-o-user-plus class="h-5 w-5" />
                                        </span>
                                        <div>
                                            <div class="text-xs font-bold text-stone-800 dark:text-stone-100">Üye ol</div>
                                            <div class="text-[10px] text-stone-500 dark:text-stone-400">Yeni hesap aç</div>
                                        </div>
                                    </a>

                                    <a
                                        href="{{ route('login') }}"
                                        class="yelpaze-oge flex flex-col items-center gap-1.5 rounded-2xl border border-stone-200/90 bg-stone-50/80 p-3 text-center transition hover:border-emerald-300 hover:bg-white active:scale-[0.98] dark:border-stone-800 dark:bg-stone-800/60 dark:hover:border-emerald-600"
                                    >
                                        <span class="grid h-9 w-9 place-items-center rounded-full bg-stone-200/90 text-stone-700 dark:bg-stone-700 dark:text-stone-200">
                                            <x-heroicon-o-arrow-right-on-rectangle class="h-5 w-5" />
                                        </span>
                                        <div>
                                            <div class="text-xs font-bold text-stone-800 dark:text-stone-100">Giriş yap</div>
                                            <div class="text-[10px] text-stone-500 dark:text-stone-400">Panelime git</div>
                                        </div>
                                    </a>
                                </div>

                                {{-- 3. Acil Yardım --}}
                                <button
                                    type="button"
                                    @click="$dispatch('acil-yardim-ac'); kapat()"
                                    class="yelpaze-oge flex w-full items-center justify-between gap-3 rounded-2xl border border-rose-200/90 bg-rose-50/80 p-3 text-left transition hover:border-rose-400 hover:bg-rose-100/70 active:scale-[0.98] dark:border-rose-900/60 dark:bg-rose-950/40 dark:hover:bg-rose-950/60"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-red-600 text-white shadow-2xs">
                                            <x-heroicon-s-exclamation-triangle class="h-5 w-5" />
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-rose-700 dark:text-rose-300">Acil</div>
                                            <div class="truncate text-[10px] text-rose-600/80 dark:text-rose-400">112 ve konsolosluk acil hatları</div>
                                        </div>
                                    </div>
                                    <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-rose-400 dark:text-rose-500" />
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        @endauth

        <a href="{{ route('panel.messages.index') }}" class="flex flex-1 flex-col items-center justify-center gap-1 py-2 text-[11px] font-semibold transition {{ request()->routeIs('panel.messages.*') ? 'text-emerald-700 dark:text-emerald-400' : 'text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200' }}">
            <span class="relative">
                <x-heroicon-o-chat-bubble-left-right class="h-5 w-5 {{ request()->routeIs('panel.messages.*') ? 'stroke-[2.2]' : '' }}" />
                @if ($unreadMessages)
                    <span class="absolute -right-1.5 -top-1 grid h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white">{{ $unreadMessages > 9 ? '9+' : $unreadMessages }}</span>
                @endif
            </span>
            <span>Mesajlar</span>
        </a>

        <a href="{{ route('dashboard') }}" class="flex flex-1 flex-col items-center justify-center gap-1 py-2 text-[11px] font-semibold transition {{ request()->routeIs('dashboard') ? 'text-emerald-700 dark:text-emerald-400' : 'text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200' }}">
            <x-heroicon-o-user-circle class="h-5 w-5 {{ request()->routeIs('dashboard') ? 'stroke-[2.2]' : '' }}" />
            <span>Panelim</span>
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
