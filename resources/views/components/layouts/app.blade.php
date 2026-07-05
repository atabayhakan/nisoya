<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Nisoya — Ne İş Olursa Yaparım' }}</title>
    <meta name="description" content="{{ $description ?? 'Yurt dışındaki Türklerin yetenek, hizmet ve ev ürünleri pazaryeri. Kendi insanından güvenle hizmet al, yeteneğini paraya dönüştür.' }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="Nisoya">
    <meta property="og:title" content="{{ $title ?? 'Nisoya — Ne İş Olursa Yaparım' }}">
    <meta property="og:description" content="{{ $description ?? 'Yurt dışındaki Türklerin yetenek ve hizmet pazaryeri.' }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('og.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $ogImage ?? asset('og.png') }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='6' fill='%23059669'/><path d='M7 17V7L17 17V7' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round' fill='none'/></svg>">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#059669" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0c0a09" media="(prefers-color-scheme: dark)">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    {{-- Tema başlatma (FOUC önleme: head içinde inline çalışmalı) --}}
    <x-theme-init />

    {{-- DNS prefetch / preconnect: AdSense + Analytics için bağlantı kurulumunu erkenden başlat --}}
    <link rel="dns-prefetch" href="//pagead2.googlesyndication.com">
    <link rel="dns-prefetch" href="//www.googletagmanager.com">
    <link rel="dns-prefetch" href="//googleads.g.doubleclick.net">
    <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
    <link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin>

    {{-- JSON-LD: WebSite + Organization (SEO + AdSense kalite) --}}
    <x-json-ld type="WebSite" :data="[
        'name' => setting('genel.site_adi'),
        'alternateName' => 'Nisoya',
        'url' => url('/'),
        'description' => setting('footer.aciklama'),
        'inLanguage' => 'tr-TR',
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => route('listings.index').'?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ]" />
    <x-json-ld type="Organization" :data="[
        'name' => setting('genel.site_adi'),
        'url' => url('/'),
        'logo' => asset('icons/icon-192.png'),
        'description' => setting('footer.aciklama'),
    ]" />

    {{-- Google AdSense doğrulama meta etiketi (yayıncı id .env / admin panelden) --}}
    @if (config('services.adsense.enabled') && config('services.adsense.publisher_id'))
        <meta name="google-adsense-account" content="{{ config('services.adsense.publisher_id') }}">
    @endif

    {{-- Google Analytics 4 — yalnızca analytics etkinse ve ölçüm id varsa --}}
    @if (config('services.analytics.enabled') && config('services.analytics.measurement_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.analytics.measurement_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json(config('services.analytics.measurement_id')), { anonymize_ip: true });
        </script>
        {{-- Analytics özel kodu (admin panelden — Site Yönetimi → İçerik).
             GÜVENLİK: Bu alana sadece admin rolündeki kullanıcılar yazabilir
             (Filament `canAccessPanel()` ile korunur). Üretim ortamında
             admin'in kendi itibarı ve KVKK gereği sadece güvenilir 3. parti
             (AdSense, Analytics, vb.) kodları eklemesi beklenir. Bilinmeyen
             kullanıcıya bu alanı kullandırmayın. --}}
        @if (config('services.analytics.custom_code'))
            {!! config('services.analytics.custom_code') !!}
        @endif
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Header özel kodu (admin panelden — Site Yönetimi → İçerik).
         GÜVENLİK: Yalnızca admin rolü yazabilir. --}}
    @if (config('services.custom_head_code'))
        {!! config('services.custom_head_code') !!}
    @endif
</head>
<body class="min-h-screen bg-stone-50 text-stone-800 antialiased flex flex-col dark:bg-stone-950 dark:text-stone-200">
    {{-- Üst menü --}}
    <header class="sticky top-0 z-30 border-b border-stone-200 bg-white/90 backdrop-blur dark:border-stone-800 dark:bg-stone-900/90">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ url('/') }}" class="group flex items-center gap-2">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-600 text-white transition group-hover:bg-emerald-700 dark:bg-emerald-500 dark:group-hover:bg-emerald-400 dark:text-stone-900">
                    <x-logo-mark class="h-5 w-5" />
                </span>
                <span class="text-xl font-bold tracking-tight text-stone-900 dark:text-stone-50">{{ setting('genel.site_adi') }}</span>
            </a>

            <nav class="hidden items-center gap-6 text-sm font-medium text-stone-600 md:flex dark:text-stone-300">
                <a href="{{ route('listings.index') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İlanlar</a>
                <a href="{{ route('jobs.index') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İş İlanları</a>
                <a href="{{ route('listings.map') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Harita</a>
                <a href="{{ route('pages.how') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Nasıl Çalışır?</a>
            </nav>

            <div class="flex items-center gap-2">
                <x-emergency-button
                    :categories="$emergencyCategories"
                    :countries="$emergencyCountries"
                    :default-country="$emergencyDefaultCountry"
                />

                {{-- Dark mode toggle --}}
                <button
                    type="button"
                    onclick="window.toggleTheme && window.toggleTheme()"
                    class="inline-flex rounded-lg p-2 text-stone-600 transition hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800"
                    title="Temayı değiştir"
                    aria-label="Karanlık/aydınlık tema değiştir"
                >
                    <x-heroicon-o-moon class="h-5 w-5 dark:hidden" />
                    <x-heroicon-o-sun class="hidden h-5 w-5 dark:inline" />
                </button>

                @auth
                    @php($unreadCount = auth()->user()->unreadNotifications()->count())
                    <a href="{{ route('panel.notifications.index') }}" class="relative inline-flex rounded-lg p-2 text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800" title="Bildirimler">
                        <x-heroicon-o-bell class="h-5 w-5" />
                        @if ($unreadCount)
                            <span class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('panel.profile.edit') }}" class="flex items-center gap-2 rounded-lg py-1.5 pl-1.5 pr-2 text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800" title="Hesabım">
                        <span class="grid h-7 w-7 shrink-0 place-items-center overflow-hidden rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            @if (auth()->user()->avatar_path)
                                <img src="{{ Storage::url(auth()->user()->avatar_path) }}" alt="" class="h-full w-full object-cover">
                            @else
                                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                            @endif
                        </span>
                        <span class="hidden max-w-[110px] truncate text-sm font-medium sm:inline">{{ auth()->user()->name }}</span>
                    </a>
                    <a href="{{ route('dashboard') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-100 sm:inline-block dark:text-stone-200 dark:hover:bg-stone-800">Panelim</a>
                    <a href="{{ url('/panel/ilan/yeni') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-brand dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">İlan Ver</a>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-stone-500 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">Çıkış</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-100 sm:inline-block dark:text-stone-200 dark:hover:bg-stone-800">Giriş</a>
                    <a href="{{ route('register') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-100 sm:inline-block dark:text-stone-200 dark:hover:bg-stone-800">Kayıt</a>
                    <a href="{{ url('/panel/ilan/yeni') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-brand dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">İlan Ver</a>
                @endauth
            </div>
        </div>

        {{-- Mobil gezinme --}}
        <nav class="flex items-center gap-5 overflow-x-auto border-t border-stone-100 px-4 py-2 text-sm font-medium text-stone-600 md:hidden dark:border-stone-800 dark:text-stone-300">
            <a href="{{ route('listings.index') }}" class="whitespace-nowrap hover:text-emerald-700 dark:hover:text-emerald-400">İlanlar</a>
            <a href="{{ route('jobs.index') }}" class="whitespace-nowrap hover:text-emerald-700 dark:hover:text-emerald-400">İş İlanları</a>
            <a href="{{ route('listings.map') }}" class="whitespace-nowrap hover:text-emerald-700 dark:hover:text-emerald-400">Harita</a>
            <a href="{{ route('pages.how') }}" class="whitespace-nowrap hover:text-emerald-700 dark:hover:text-emerald-400">Nasıl Çalışır?</a>
            @guest
                <a href="{{ route('login') }}" class="whitespace-nowrap hover:text-emerald-700 dark:hover:text-emerald-400">Giriş</a>
                <a href="{{ route('register') }}" class="whitespace-nowrap hover:text-emerald-700 dark:hover:text-emerald-400">Kayıt</a>
            @else
                <a href="{{ route('dashboard') }}" class="whitespace-nowrap hover:text-emerald-700 dark:hover:text-emerald-400">Panelim</a>
            @endguest
        </nav>
    </header>

    <main class="flex-1">
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
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-600 text-white dark:bg-emerald-500 dark:text-stone-900">
                        <x-logo-mark class="h-4 w-4" />
                    </span>
                    <span class="text-lg font-bold text-stone-900 dark:text-stone-50">{{ setting('genel.site_adi') }}</span>
                </div>
                <p class="mt-3 text-sm text-stone-500 dark:text-stone-400">{{ setting('footer.aciklama') }}</p>
                @if (setting('footer.sosyal_instagram') || setting('footer.sosyal_facebook') || setting('footer.sosyal_whatsapp'))
                    <div class="mt-4 flex items-center gap-3 text-stone-400 dark:text-stone-500">
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
                    <li><a href="{{ route('jobs.index') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İş İlanları</a></li>
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
            <p class="mx-auto max-w-6xl px-4 text-center text-xs text-stone-400 dark:text-stone-500">
                © {{ date('Y') }} {{ setting('footer.telif_metni') }}
                <span class="mx-2 text-stone-300 dark:text-stone-600">·</span>
                Hizmet ücretsizdir. 💛
            </p>
        </div>
    </footer>

    {{-- Footer özel kodu (admin panelden — Site Yönetimi → İçerik).
         GÜVENLİK: Yalnızca admin rolü yazabilir. --}}
    @if (config('services.custom_footer_code'))
        {!! config('services.custom_footer_code') !!}
    @endif

    {{-- Google AdSense Auto Ads — kullanıcı çerez onayı verdikten sonra yüklenir.
         Admin panelden Auto Ads kodu girildiyse onu kullan (adsbygoogle.js'i
         zaten kendi içinde yükler); yoksa temel script'e düş. --}}
    @if (config('services.adsense.enabled') && config('services.adsense.publisher_id'))
        @if (config('services.adsense.auto_ads_code'))
            {!! config('services.adsense.auto_ads_code') !!}
        @else
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ config('services.adsense.publisher_id') }}" crossorigin="anonymous" data-consent="ads"></script>
        @endif
    @endif

    {{-- Çerez onayı banner'ı (AdSense + Analytics için) --}}
    <x-cookie-consent />

    {{-- Bağış modalı + FAB (Nisoya ücretsiz kalır). Panel sayfalarında
         (form doldurma, ilan yönetimi, mesajlaşma vb. asıl site kullanımı)
         gösterilmez — orada mobilde form/aksiyon butonlarının üzerine
         binip kullanımı engelliyordu. --}}
    @unless (request()->is('panel*'))
        <x-donation-modal />
    @endunless

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }
    </script>
</body>
</html>