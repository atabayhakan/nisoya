<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- VİTRİN İSKELETİ (P1) — klasik layouts/app'in aynı-ad override'ı.
         Paylaşılan sözleşme bileşenleri (head-meta/head-scripts/tail-scripts)
         AYNEN kullanılır; consent/SEO zinciri tek kopyadır (vitrin/README.md #5).
         Görsel dil: vitrin-theme değişken yönlendirmesi + bu dosyadaki
         header/footer. Composer 'components.layouts.app' ADINA kayıtlı olduğu
         için $navLinks* değişkenleri buraya aynen akar. --}}
    <x-layout-head-meta :title="$title ?? null" :description="$description ?? null" :og-image="$ogImage ?? null" :noindex="$noindex ?? false" />

    {{-- Tema başlatma (FOUC önleme: head içinde inline çalışmalı) --}}
    <x-theme-init />

    <x-layout-head-scripts />

    <x-layout-fonts />


    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Vitrin palet/tipografi motoru — klasikteki brand-theme + tasarim-theme
         yerine geçer (o ikisi klasiğin eksenidir, vitrin'de basılmaz). --}}
    <x-vitrin-theme />

    @if (config('services.custom_head_code'))
        {!! config('services.custom_head_code') !!}
    @endif
</head>
<body data-tema="vitrin" class="min-h-screen bg-stone-50 text-stone-800 antialiased flex flex-col pb-[calc(4rem+env(safe-area-inset-bottom))] md:pb-0 dark:bg-stone-950 dark:text-stone-200">
    <style>
        .nisoya-skip-link { position: fixed; left: 1rem; top: 1rem; z-index: 100;
            transform: translateY(-150%); background: var(--color-emerald-600, #3E63F0);
            color: #fff; padding: .5rem 1rem; border-radius: .5rem; font-weight: 600;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / .2); transition: transform .15s ease; }
        .nisoya-skip-link:focus { transform: translateY(0); outline: 2px solid #fff; outline-offset: 2px; }
    </style>
    <a href="#main-content" class="nisoya-skip-link">İçeriğe geç</a>

    <x-announcement-bar />

    {{-- Üst menü — scroll davranışı (headerScroll) ve mega menü/⌘K klasikten
         aynen; görsel dil Vitrin: beyaz yüzey, ince mürekkep kenarlığı. --}}
    <header
        x-data="headerScroll()"
        @scroll.window.throttle.150ms="onScroll()"
        :style="hidden ? 'transform: translateY(-100%)' : ''"
        class="sticky top-0 z-30 border-b border-stone-200/70 bg-white/95 backdrop-blur transition-transform duration-300 dark:border-stone-800 dark:bg-stone-900/95"
    >
        <div :class="scrolled ? 'py-2 shadow-sm' : 'py-3.5'" class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 transition-all duration-300">
            <a href="{{ url('/') }}" class="group flex items-center gap-2.5">
                <x-logo-ikon>
                    @if ($logoPath = setting('gorunum.logo_path'))
                        <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ setting('genel.site_adi') }}" class="h-9 w-9 rounded-xl object-cover">
                    @else
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-brand transition group-hover:from-emerald-600 group-hover:to-emerald-700 dark:text-stone-900">
                            <x-logo-mark class="h-5 w-5" />
                        </span>
                    @endif
                </x-logo-ikon>
                {{-- ÇOK DAR EKRANDA YALNIZ İKON. 380px altında marka adı ~70px
                     yer kaplıyor ve başlık taşıyordu (360px'te 17px, 320px'te
                     57px yatay kaydırma). İkon tek başına markayı taşıyor ve
                     yine ana sayfaya götürüyor. --}}
                @if (\App\Support\TemaJetonlari::logoAnimasyonuAktifMi())
                    {{-- Hareketli çizim her zaman "Nisoya" (düzgün büyük harfle)
                         gösterir — küçük harfli vitrin stiline BİLEREK uymuyor,
                         çizim yolu bu kelime için üretildi (bkz. bileşen). --}}
                    <x-hareketli-logo class="h-8 max-[380px]:hidden" />
                @else
                    <span class="text-xl font-extrabold text-stone-800 max-[380px]:hidden dark:text-stone-50">{{ Str::lower(setting('genel.site_adi')) }}</span>
                @endif
            </a>

            <nav class="hidden items-center gap-6 text-sm font-semibold text-stone-600 md:flex dark:text-stone-300">
                <x-mega-menu :items="$navLinksMega" />
                @foreach ($navLinksSingle as $navLink)
                    {{-- Gerçek olay (2026-08-21, canlıda ölçüldü): $navLink->url
                         admin panelinden GÖRECELİ yol olarak kaydediliyor
                         ("/nasil-calisir"), request()->url() ise HER ZAMAN TAM
                         adres ("https://nisoya.com/nasil-calisir") döner —
                         ikisi asla birebir eşleşmiyordu, aktif işaret hiç
                         basılmıyordu. parse_url() ile ikisi de saf yola
                         indirgenip öyle karşılaştırılıyor. --}}
                    @php
                        $navLinkHost = parse_url((string) $navLink->url, PHP_URL_HOST);
                        $navLinkAktif = ($navLinkHost === null || $navLinkHost === request()->getHost())
                            && trim((string) parse_url((string) $navLink->url, PHP_URL_PATH), '/') === trim(request()->path(), '/');
                    @endphp
                    <a href="{{ $navLink->url }}" @if ($navLink->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif
                       @if ($navLinkAktif) aria-current="page" @endif
                       class="relative py-1 after:absolute after:-bottom-0.5 after:left-0 after:h-0.5 after:bg-emerald-600 after:transition-all after:duration-300 hover:text-emerald-700 hover:after:w-full dark:after:bg-emerald-400 dark:hover:text-emerald-400 {{ $navLinkAktif ? 'text-emerald-700 after:w-full dark:text-emerald-400' : 'after:w-0' }}">{{ $navLink->label }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-1 sm:gap-2">
                <x-command-palette :nav-links="$navLinks" />

                {{-- Ülke seçici: eski rozet mobilde hiç görünmüyor,
                     masaüstünde de tıklanamıyordu (gerekçe x-ulke-secici'de). --}}
                <x-ulke-secici :country="$visitorCountry" :countries="$emergencyCountries" />

                <x-emergency-button
                    :categories="$emergencyCategories"
                    :countries="$emergencyCountries"
                    :default-country="$emergencyDefaultCountry"
                    :city="$emergencyCity"
                />

                {{-- Mobilde GİZLİ: 390px'lik başlıkta yer ülke seçici, arama,
                     acil yardım, favoriler ve hesap düğmesi arasında paylaşılıyor.
                     Bir kez ayarlanan tema tercihi, her ekranda yer kaplayan bir
                     düğmeyi hak etmiyor — mobilde x-mobil-hesap içinde. --}}
                @unless (\App\Support\Tema::koyuKilit())
                    <button
                        type="button"
                        onclick="window.toggleTheme && window.toggleTheme()"
                        class="hidden rounded-lg p-2 text-stone-600 transition hover:bg-stone-100 md:inline-flex dark:text-stone-300 dark:hover:bg-stone-800"
                        title="Temayı değiştir"
                        aria-label="Karanlık/aydınlık tema değiştir"
                    >
                        <x-heroicon-o-moon class="h-5 w-5 dark:hidden" />
                        <x-heroicon-o-sun class="hidden h-5 w-5 dark:inline" />
                    </button>
                @endunless

                {{-- Favoriler — mobilde başlıkta, yalnız giriş yapmışken. --}}
                @auth
                    <a href="{{ route('panel.favorites.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg text-stone-600 transition hover:bg-stone-100 md:hidden dark:text-stone-300 dark:hover:bg-stone-800" title="Favorilerim" aria-label="Favorilerim">
                        <x-heroicon-o-heart class="h-5 w-5" />
                    </a>
                @endauth

                {{-- Mobilde kimlik + çıkış (gerekçe bileşenin içinde). --}}
                <x-mobil-hesap />

                @auth
                    @php $unreadCount = auth()->user()->okunmamisBildirimSayisi(); @endphp
                    {{-- MESAJLAR — masaüstünde başlıkta HİÇ YOKTU.

                         Mobilde alt sekme çubuğunda tek dokunuş ve kırmızı
                         rozetle duruyor; masaüstünde ise Panelim → Bölümler
                         (iki tık) ve hiçbir okunmamış sinyali yok. Yani
                         asimetri iki yönlüydü: bildirimler mobilde eksikti,
                         mesajlar masaüstünde. --}}
                    <a href="{{ route('panel.messages.index') }}" class="relative hidden rounded-lg p-2 text-stone-600 hover:bg-stone-100 md:inline-flex dark:text-stone-300 dark:hover:bg-stone-800" title="Mesajlar">
                        <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
                        @if ($okunmamisMesaj = auth()->user()->okunmamisMesajSayisi())
                            <span class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-2xs font-bold leading-none text-white">{{ $okunmamisMesaj > 9 ? '9+' : $okunmamisMesaj }}</span>
                        @endif
                    </a>
                    <a href="{{ route('panel.notifications.index') }}" class="relative hidden rounded-lg p-2 text-stone-600 hover:bg-stone-100 md:inline-flex dark:text-stone-300 dark:hover:bg-stone-800" title="Bildirimler">
                        <x-heroicon-o-bell class="h-5 w-5 {{ $unreadCount ? 'animate-[nisoya-bell-ring_0.6s_ease-in-out_1]' : '' }}" />
                        @if ($unreadCount)
                            <span class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-2xs font-bold leading-none text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('panel.profile.edit') }}" class="hidden items-center gap-2 rounded-lg py-1.5 pl-1.5 pr-2 text-stone-700 hover:bg-stone-100 md:flex dark:text-stone-200 dark:hover:bg-stone-800" title="Hesabım">
                        <x-avatar :user="auth()->user()" size="h-7 w-7" text="text-xs" />
                        <span class="hidden max-w-[110px] truncate text-sm font-medium md:inline">{{ auth()->user()->name }}</span>
                    </a>
                    <a href="{{ route('dashboard') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 md:inline-block dark:text-stone-200 dark:hover:bg-stone-800">Panelim</a>
                    <x-button :href="route('panel.listings.create')" class="hidden md:inline-flex">
                        <x-heroicon-o-plus class="h-4 w-4" />İlan Ver
                    </x-button>
                    <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">Çıkış</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-stone-600 hover:bg-stone-100 md:inline-block dark:text-stone-200 dark:hover:bg-stone-800">Giriş</a>
                    <a href="{{ route('register') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-stone-600 hover:bg-stone-100 md:inline-block dark:text-stone-200 dark:hover:bg-stone-800">Kayıt</a>
                    <x-button :href="route('panel.listings.create')" class="hidden md:inline-flex">
                        <x-heroicon-o-plus class="h-4 w-4" />İlan Ver
                    </x-button>
                @endauth
            </div>
        </div>
    </header>

    <main id="main-content" tabindex="-1" class="flex-1">
        {{ $slot }}
    </main>

    <div class="mx-auto max-w-6xl px-4">
        <x-zone zone-key="footer_ust_serit" />
    </div>

    {{-- Alt bilgi — Vitrin: Sınırlar İçinde Kalan Yuvarlatılmış Kart --}}
    <footer class="mx-auto max-w-6xl px-4 mt-16 mb-8 w-full">
        <div class="rounded-3xl border border-stone-200/90 bg-white p-6 sm:p-10 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <div class="grid gap-8 sm:grid-cols-2 md:grid-cols-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        @if ($logoPath)
                            <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ setting('genel.site_adi') }}" class="h-8 w-8 rounded-xl object-cover">
                        @else
                            <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white dark:text-stone-900">
                                <x-logo-mark class="h-4 w-4" />
                            </span>
                        @endif
                        <span class="text-lg font-bold text-stone-800 dark:text-stone-50">{{ Str::lower(setting('genel.site_adi')) }}</span>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-stone-500 dark:text-stone-400">{{ setting('footer.aciklama') }}</p>
                    @if (setting('footer.sosyal_instagram') || setting('footer.sosyal_facebook') || setting('footer.sosyal_whatsapp'))
                        <div class="mt-4 flex items-center gap-3 text-stone-600 dark:text-stone-400">
                            @if (setting('footer.sosyal_instagram'))
                                <a href="{{ setting('footer.sosyal_instagram') }}" target="_blank" rel="noopener noreferrer" class="hover:text-emerald-700 dark:hover:text-emerald-400" aria-label="Instagram">📷</a>
                            @endif
                            @if (setting('footer.sosyal_facebook'))
                                <a href="{{ setting('footer.sosyal_facebook') }}" target="_blank" rel="noopener noreferrer" class="hover:text-emerald-700 dark:hover:text-emerald-400" aria-label="Facebook">📘</a>
                            @endif
                            @if (setting('footer.sosyal_whatsapp'))
                                <a href="{{ setting('footer.sosyal_whatsapp') }}" target="_blank" rel="noopener noreferrer" class="hover:text-emerald-700 dark:hover:text-emerald-400" aria-label="WhatsApp">💬</a>
                            @endif
                        </div>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-bold text-stone-800 dark:text-stone-100">Keşfet</h3>
                    <ul class="mt-3 space-y-2 text-sm font-medium text-stone-500 dark:text-stone-400">
                        <li><a href="{{ route('listings.index') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Tüm İlanlar</a></li>
                        @if (\App\Support\Modules::enabled('is_ilanlari'))
                            <li><a href="{{ route('jobs.index') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İş İlanları</a></li>
                            <li><a href="{{ route('candidates.index') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Yetenek Havuzu</a></li>
                        @endif
                        @if (\App\Support\Modules::enabled('hali_saha'))
                            <li><a href="{{ route('football.index') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">⚽ Halı Saha & Spor</a></li>
                        @endif
                        @if (\App\Support\Modules::enabled('rehber') && ($rehberFooterUlke = app(\App\Services\RehberYuzeyi::class)->girisNoktasiUlkeKodu(request()->user(), request())) !== null)
                            <li><a href="{{ route('rehber.ulke', $rehberFooterUlke) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Ülke Rehberi</a></li>
                        @endif
                        <li><a href="{{ route('pages.how') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Nasıl Çalışır?</a></li>
                        <li><a href="{{ route('nabiz') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Nisoya Nabzı</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-stone-800 dark:text-stone-100">Hesap</h3>
                    <ul class="mt-3 space-y-2 text-sm font-medium text-stone-500 dark:text-stone-400">
                        <li><a href="{{ route('login') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Giriş Yap</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Kayıt Ol</a></li>
                        <li><a href="{{ route('panel.listings.create') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İlan Ver</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-stone-800 dark:text-stone-100">Kurumsal</h3>
                    <ul class="mt-3 space-y-2 text-sm font-medium text-stone-500 dark:text-stone-400">
                        @foreach (\App\Models\Page::navPages() as $navPage)
                            <li><a href="{{ url('/'.$navPage->slug) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">{{ $navPage->title }}</a></li>
                        @endforeach
                        <li><a href="{{ route('pages.contact') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İletişim</a></li>
                        <li><a href="{{ route('pages.cookie-preferences') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Çerez Tercihleri</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-stone-100 pt-4 dark:border-stone-800">
                <p class="text-center text-xs font-medium text-stone-500 dark:text-stone-400">
                    © {{ date('Y') }} {{ setting('footer.telif_metni') }}
                    <span class="mx-2 text-stone-300 dark:text-stone-400" aria-hidden="true">·</span>
                    Hizmet ücretsizdir. 💙
                </p>
            </div>
        </div>
    </footer>

    @if (config('services.custom_footer_code'))
        {!! config('services.custom_footer_code') !!}
    @endif

    <x-layout-tail-scripts />

    @unless (request()->is('panel*') || setting('bagis.aktif', '1') !== '1')
        <x-donation-modal />
    @endunless

    <x-mobile-tab-bar :nav-links-mega="$navLinksMega" :nav-links-single="$navLinksSingle" />

    {{-- Klasik iskeletteki notun eşi: özelleştirici İKİ düzende de basılmalı. --}}
    <x-tema-ozellestirici />
</body>
</html>
