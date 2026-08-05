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
     kendi yerini ayırır/taşmaz, bkz. app.blade.php'deki body padding'i). --}}
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

        <a href="{{ url('/panel/ilan/yeni') }}" class="flex flex-1 flex-col items-center justify-center gap-0.5 py-1 text-2xs font-medium text-stone-500 dark:text-stone-400">
            <span class="-mt-5 grid h-11 w-11 place-items-center rounded-full bg-emerald-700 text-white shadow-lg ring-4 ring-white dark:bg-emerald-500 dark:ring-stone-900">
                <x-heroicon-o-plus class="h-6 w-6" />
            </span>
            İlan Ver
        </a>

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
