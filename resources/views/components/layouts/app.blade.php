<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- SEO/og/favicon/manifest meta'ları — paylaşılan tek kopya (Vitrin P0
         ekstraksiyonu; vitrin iskeleti de aynı bileşeni kullanacak). --}}
    <x-layout-head-meta :title="$title ?? null" :description="$description ?? null" :og-image="$ogImage ?? null" :noindex="$noindex ?? false" />

    {{-- Tema başlatma (FOUC önleme: head içinde inline çalışmalı) --}}
    <x-theme-init />

    {{-- DNS prefetch + JSON-LD + AdSense meta + consent'e kilitli Analytics
         template'i + nisoyaActivateConsent — paylaşılan tek kopya. --}}
    <x-layout-head-scripts />

    <x-layout-fonts />


    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Marka rengi override'ı (admin panelden — Site Yönetimi → İçerik → Görünüm) --}}
    <x-brand-theme />

    {{-- Tasarım modu override'ı (admin panelden — Tasarım → Tasarım Modu) --}}
    <x-tasarim-theme />

    {{-- Header özel kodu (admin panelden — Site Yönetimi → İçerik).
         GÜVENLİK: Yalnızca admin rolü yazabilir. --}}
    @if (config('services.custom_head_code'))
        {!! config('services.custom_head_code') !!}
    @endif
</head>
<body class="min-h-screen bg-stone-50 text-stone-800 antialiased flex flex-col pb-[calc(4rem+env(safe-area-inset-bottom))] md:pb-0 dark:bg-stone-950 dark:text-stone-200">
    {{-- Erişilebilirlik: klavye kullanıcıları menüyü atlayıp doğrudan içeriğe
         geçebilsin. Ekran okuyucular linki her zaman okur; görsel olarak
         varsayılanda ekran dışında, odaklanınca görünür. Küçük scoped CSS
         (Tailwind focus-varyant cascade belirsizliğine bağımlı olmamak için). --}}
    <style>
        .nisoya-skip-link { position: fixed; left: 1rem; top: 1rem; z-index: 100;
            transform: translateY(-150%); background: var(--color-emerald-700, #047857);
            color: #fff; padding: .5rem 1rem; border-radius: .5rem; font-weight: 600;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / .2); transition: transform .15s ease; }
        .nisoya-skip-link:focus { transform: translateY(0); outline: 2px solid #fff; outline-offset: 2px; }
    </style>
    <a href="#main-content" class="nisoya-skip-link">İçeriğe geç</a>

    {{-- Site üstü duyuru bandı (panelden: Site Yönetimi → Duyuru Bandı) --}}
    <x-announcement-bar />

    {{-- Üst menü --}}
    {{-- Faz H4: kaydırınca hafifçe küçülür/gölge kazanır, aşağı kaydırırken
         gizlenir, yukarı kaydırınca hemen geri açılır (bkz. resources/js/app.js
         headerScroll()). prefers-reduced-motion'da gizleme devre dışı kalır. --}}
    {{-- NOT: yükseklik auto (içerik bazlı) olduğu için gizleme burada
         Tailwind'in "translate" utility'siyle (%-tabanlı) değil, klasik
         transform:translateY() ile yapılıyor — bazı tarayıcılarda yüzde
         tabanlı translate, auto-height sticky elemanlarda çözümlenmiyor. --}}
    <header
        x-data="headerScroll()"
        @scroll.window.throttle.150ms="onScroll()"
        :style="hidden ? 'transform: translateY(-100%)' : ''"
        class="sticky top-0 z-30 border-b border-stone-200 bg-white/90 backdrop-blur transition-transform duration-300 dark:border-stone-800 dark:bg-stone-900/90"
    >
        {{-- Dar ekranda boşluk pahalı: `gap-4` sabitken logo + arama + ülke +
             acil + giriş + üye-ol altı öğe 360px'e sığmıyor ve düğmeler
             eziliyordu. Boşluk mobilde 0.5rem, sm'den itibaren eski hâline
             döner — masaüstünde ferahlık korunur. --}}
        <div :class="scrolled ? 'py-2 shadow-sm' : 'py-3'" class="mx-auto flex max-w-6xl items-center justify-between gap-2 px-4 transition-all duration-300 sm:gap-4">
            <a href="{{ url('/') }}" class="group flex min-w-0 shrink items-center gap-2">
                @if ($logoPath = setting('gorunum.logo_path'))
                    <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ setting('genel.site_adi') }}" class="h-9 w-9 rounded-xl object-cover">
                @else
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-700 text-white transition group-hover:bg-emerald-800 dark:bg-emerald-500 dark:group-hover:bg-emerald-400 dark:text-stone-900">
                        <x-logo-mark class="h-5 w-5" />
                    </span>
                @endif
                {{-- ÇOK DAR EKRANDA YALNIZ İKON. 380px altında marka adı ~70px yer
                         kaplıyor ve başlık taşıyordu (360px'te 17px, 320px'te 57px
                         yatay kaydırma). İkon tek başına markayı taşıyor ve yine
                         ana sayfaya götürüyor. --}}
                @if (\App\Support\TemaJetonlari::logoAnimasyonuAktifMi())
                    <x-hareketli-logo class="h-8 max-[380px]:hidden" />
                @else
                    <span class="text-xl font-bold text-stone-900 max-[380px]:hidden dark:text-stone-50">{{ setting('genel.site_adi') }}</span>
                @endif
            </a>

            <nav class="hidden items-center gap-6 text-sm font-medium text-stone-600 md:flex dark:text-stone-300">
                <x-mega-menu :items="$navLinksMega" />
                @foreach ($navLinksSingle as $navLink)
                    <a href="{{ $navLink->url }}" @if ($navLink->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif class="relative py-1 after:absolute after:-bottom-0.5 after:left-0 after:h-0.5 after:w-0 after:bg-emerald-600 after:transition-all after:duration-300 hover:text-emerald-700 hover:after:w-full dark:after:bg-emerald-400 dark:hover:text-emerald-400">{{ $navLink->label }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-1 sm:gap-2">
                <x-command-palette :nav-links="$navLinks" />

                {{-- Ülke seçici: eski `x-visitor-country-badge` mobilde hiç
                     görünmüyor, masaüstünde de tıklanamıyordu (gerekçe
                     x-ulke-secici'de). --}}
                <x-ulke-secici :country="$visitorCountry" :countries="$emergencyCountries" />

                <x-emergency-button
                    :categories="$emergencyCategories"
                    :countries="$emergencyCountries"
                    :default-country="$emergencyDefaultCountry"
                    :city="$emergencyCity"
                />

                {{-- Dark mode toggle — obsidian teması koyu moda kilitli olduğu
                     için o modda gizlenir (aksi hâlde no-op buton kalırdı). --}}
                {{-- Mobilde GİZLİ: 390px'lik başlıkta yer ülke seçici, arama,
                     acil yardım, favoriler ve hesap düğmesi arasında paylaşılıyor.
                     Bir kez ayarlanan tema tercihi, her ekranda yer kaplayan bir
                     düğmeyi hak etmiyor — mobilde hesap sayfasının içinde
                     (x-mobil-hesap). --}}
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

                {{-- Favoriler — mobilde başlıkta, yalnız giriş yapmışken
                     (favori eklemek zaten hesap gerektiriyor). --}}
                @auth
                    <a href="{{ route('panel.favorites.index') }}" class="rounded-lg p-2 text-stone-600 transition hover:bg-stone-100 md:hidden dark:text-stone-300 dark:hover:bg-stone-800" title="Favorilerim" aria-label="Favorilerim">
                        <x-heroicon-o-heart class="h-5 w-5" />
                    </a>
                @endauth

                {{-- Mobilde kimlik + çıkış (gerekçe bileşenin içinde). --}}
                <x-mobil-hesap />

                {{-- Faz H3: bildirim/profil/Panelim/İlan Ver/Giriş-Kayıt-Çıkış
                     mobilde alt sekme çubuğuna taşındı (bkz. x-mobile-tab-bar) —
                     burada yalnızca masaüstünde (md+) görünür. --}}
                @auth
                    @php($unreadCount = auth()->user()->okunmamisBildirimSayisi())
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
                        {{-- Faz H4: okunmamış varsa sayfa yüklenince zil bir kez "çalar" (bkz. app.css nisoya-bell-ring) --}}
                        <x-heroicon-o-bell class="h-5 w-5 {{ $unreadCount ? 'animate-[nisoya-bell-ring_0.6s_ease-in-out_1]' : '' }}" />
                        @if ($unreadCount)
                            <span class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-2xs font-bold leading-none text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('panel.profile.edit') }}" class="hidden items-center gap-2 rounded-lg py-1.5 pl-1.5 pr-2 text-stone-700 hover:bg-stone-100 md:flex dark:text-stone-200 dark:hover:bg-stone-800" title="Hesabım">
                        <x-avatar :user="auth()->user()" size="h-7 w-7" text="text-xs" />
                        <span class="hidden max-w-[110px] truncate text-sm font-medium md:inline">{{ auth()->user()->name }}</span>
                    </a>
                    <a href="{{ route('dashboard') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-100 md:inline-block dark:text-stone-200 dark:hover:bg-stone-800">Panelim</a>
                    <a href="{{ url('/panel/ilan/yeni') }}" class="hidden rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-800 hover:shadow-brand md:inline-block dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">İlan Ver</a>
                    <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">Çıkış</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-100 md:inline-block dark:text-stone-200 dark:hover:bg-stone-800">Giriş</a>
                    <a href="{{ route('register') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-100 md:inline-block dark:text-stone-200 dark:hover:bg-stone-800">Kayıt</a>
                    <a href="{{ url('/panel/ilan/yeni') }}" class="hidden rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-800 hover:shadow-brand md:inline-block dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">İlan Ver</a>
                @endauth
            </div>
        </div>
    </header>

    <main id="main-content" tabindex="-1" class="flex-1">
        {{ $slot }}
    </main>

    {{-- Alan: footer üstü şerit (sitewide reklam/duyuru) --}}
    <div class="mx-auto max-w-6xl px-4">
        <x-zone zone-key="footer_ust_serit" />
    </div>

    {{-- Alt bilgi --}}
    <footer class="mt-16 border-t border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-2 md:grid-cols-4">
            <div>
                <div class="flex items-center gap-2">
                    @if ($logoPath)
                        <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ setting('genel.site_adi') }}" class="h-8 w-8 rounded-lg object-cover">
                    @else
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900">
                            <x-logo-mark class="h-4 w-4" />
                        </span>
                    @endif
                    <span class="text-lg font-bold text-stone-900 dark:text-stone-50">{{ setting('genel.site_adi') }}</span>
                </div>
                <p class="mt-3 text-sm text-stone-500 dark:text-stone-400">{{ setting('footer.aciklama') }}</p>
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
                <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">Keşfet</h3>
                <ul class="mt-3 space-y-2 text-sm text-stone-500 dark:text-stone-400">
                    <li><a href="{{ url('/ilanlar') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Tüm İlanlar</a></li>
                    @if (\App\Support\Modules::enabled('is_ilanlari'))
                        <li><a href="{{ route('jobs.index') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İş İlanları</a></li>
                        <li><a href="{{ route('candidates.index') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Yetenek Havuzu</a></li>
                    @endif
                    {{-- Rehber bağlantısı ancak yayında içerik varken belirir (F2) —
                         boş rehbere götüren link, linkin yokluğundan kötüdür. --}}
                    @if (\App\Support\Modules::enabled('rehber') && ($rehberFooterUlke = app(\App\Services\RehberYuzeyi::class)->varsayilanUlkeKodu()) !== null)
                        <li><a href="{{ route('rehber.ulke', $rehberFooterUlke) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Ülke Rehberi</a></li>
                    @endif
                    <li><a href="{{ url('/nasil-calisir') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Nasıl Çalışır?</a></li>
                    <li><a href="{{ route('nabiz') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Nisoya Nabzı</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">Hesap</h3>
                <ul class="mt-3 space-y-2 text-sm text-stone-500 dark:text-stone-400">
                    <li><a href="{{ url('/giris') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Giriş Yap</a></li>
                    <li><a href="{{ url('/kayit') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Kayıt Ol</a></li>
                    <li><a href="{{ url('/panel/ilan/yeni') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İlan Ver</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">Kurumsal</h3>
                <ul class="mt-3 space-y-2 text-sm text-stone-500 dark:text-stone-400">
                    @foreach (\App\Models\Page::navPages() as $navPage)
                        <li><a href="{{ url('/'.$navPage->slug) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">{{ $navPage->title }}</a></li>
                    @endforeach
                    <li><a href="{{ route('pages.contact') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İletişim</a></li>
                    <li><a href="/cerez-tercihleri" class="hover:text-emerald-700 dark:hover:text-emerald-400">Çerez Tercihleri</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-stone-100 py-4 dark:border-stone-800">
            <p class="mx-auto max-w-6xl px-4 text-center text-xs text-stone-600 dark:text-stone-400">
                © {{ date('Y') }} {{ setting('footer.telif_metni') }}
                <span class="mx-2 text-stone-300 dark:text-stone-400" aria-hidden="true">·</span>
                Hizmet ücretsizdir. 💛
            </p>
        </div>
    </footer>

    {{-- Footer özel kodu (admin panelden — Site Yönetimi → İçerik).
         GÜVENLİK: Yalnızca admin rolü yazabilir. --}}
    @if (config('services.custom_footer_code'))
        {!! config('services.custom_footer_code') !!}
    @endif

    {{-- Consent'e kilitli AdSense template'i + çerez banner'ı + service
         worker kaydı — paylaşılan tek kopya (Vitrin P0 ekstraksiyonu). --}}
    <x-layout-tail-scripts />

    {{-- Bağış modalı + FAB (Nisoya ücretsiz kalır). Panel sayfalarında
         (form doldurma, ilan yönetimi, mesajlaşma vb. asıl site kullanımı)
         gösterilmez — orada mobilde form/aksiyon butonlarının üzerine
         binip kullanımı engelliyordu. Admin panelden (İçerik → Bağış →
         "Bağış bölümü") kapatılabilir — kapalıysa hiç render edilmez. --}}
    @unless (request()->is('panel*') || setting('bagis.aktif', '1') !== '1')
        <x-donation-modal />
    @endunless

    {{-- Mobil alt sekme çubuğu (Faz H3) — panel sayfaları dahil her yerde;
         bağış FAB'ının aksine kendi yerini body padding'iyle ayırıyor, taşmıyor. --}}
    <x-mobile-tab-bar :nav-links-mega="$navLinksMega" :nav-links-single="$navLinksSingle" />

    {{-- Canlı tema özelleştirici — kendi kendini kapılar (yalnız admin, panel/
         yönetim dışı sayfalarda). Vitrin iskeletinde de AYNI satır var; iki
         düzenden birine eklemeyi unutmak vitrin/README.md'nin uyardığı
         sürüklenme tuzağıdır. --}}
    <x-tema-ozellestirici />
</body>
</html>