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
    <x-layout-head-meta :title="$title ?? null" :description="$description ?? null" :og-image="$ogImage ?? null" />

    {{-- Tema başlatma (FOUC önleme: head içinde inline çalışmalı) --}}
    <x-theme-init />

    <x-layout-head-scripts />

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
                @if ($logoPath = setting('gorunum.logo_path'))
                    <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ setting('genel.site_adi') }}" class="h-9 w-9 rounded-xl object-cover">
                @else
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-brand transition group-hover:from-emerald-600 group-hover:to-emerald-700 dark:text-stone-900">
                        <x-logo-mark class="h-5 w-5" />
                    </span>
                @endif
                <span class="text-xl font-extrabold tracking-tight text-stone-800 dark:text-stone-50">{{ Str::lower(setting('genel.site_adi')) }}</span>
            </a>

            <nav class="hidden items-center gap-6 text-sm font-semibold text-stone-600 md:flex dark:text-stone-300">
                <x-mega-menu :items="$navLinksMega" />
                @foreach ($navLinksSingle as $navLink)
                    <a href="{{ $navLink->url }}" @if ($navLink->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif class="relative py-1 after:absolute after:-bottom-0.5 after:left-0 after:h-0.5 after:w-0 after:bg-emerald-600 after:transition-all after:duration-300 hover:text-emerald-700 hover:after:w-full dark:after:bg-emerald-400 dark:hover:text-emerald-400">{{ $navLink->label }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <x-command-palette :nav-links="$navLinks" />

                <x-visitor-country-badge :country="$visitorCountry" />

                <x-emergency-button
                    :categories="$emergencyCategories"
                    :countries="$emergencyCountries"
                    :default-country="$emergencyDefaultCountry"
                />

                @unless (\App\Support\Tema::koyuKilit())
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
                @endunless

                @auth
                    @php $unreadCount = auth()->user()->okunmamisBildirimSayisi(); @endphp
                    <a href="{{ route('panel.notifications.index') }}" class="relative hidden rounded-lg p-2 text-stone-600 hover:bg-stone-100 md:inline-flex dark:text-stone-300 dark:hover:bg-stone-800" title="Bildirimler">
                        <x-heroicon-o-bell class="h-5 w-5 {{ $unreadCount ? 'animate-[nisoya-bell-ring_0.6s_ease-in-out_1]' : '' }}" />
                        @if ($unreadCount)
                            <span class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('panel.profile.edit') }}" class="hidden items-center gap-2 rounded-lg py-1.5 pl-1.5 pr-2 text-stone-700 hover:bg-stone-100 md:flex dark:text-stone-200 dark:hover:bg-stone-800" title="Hesabım">
                        <x-avatar :user="auth()->user()" size="h-7 w-7" text="text-xs" />
                        <span class="hidden max-w-[110px] truncate text-sm font-medium md:inline">{{ auth()->user()->name }}</span>
                    </a>
                    <a href="{{ route('dashboard') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 md:inline-block dark:text-stone-200 dark:hover:bg-stone-800">Panelim</a>
                    <a href="{{ url('/panel/ilan/yeni') }}" class="hidden items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-[0_10px_20px_-10px_rgba(62,99,240,.9)] transition hover:-translate-y-0.5 hover:bg-emerald-700 md:inline-flex dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900 dark:shadow-none">
                        <x-heroicon-o-plus class="h-4 w-4" />İlan Ver
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-stone-500 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">Çıkış</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-stone-600 hover:bg-stone-100 md:inline-block dark:text-stone-200 dark:hover:bg-stone-800">Giriş</a>
                    <a href="{{ route('register') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-stone-600 hover:bg-stone-100 md:inline-block dark:text-stone-200 dark:hover:bg-stone-800">Kayıt</a>
                    <a href="{{ url('/panel/ilan/yeni') }}" class="hidden items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-[0_10px_20px_-10px_rgba(62,99,240,.9)] transition hover:-translate-y-0.5 hover:bg-emerald-700 md:inline-flex dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900 dark:shadow-none">
                        <x-heroicon-o-plus class="h-4 w-4" />İlan Ver
                    </a>
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

    {{-- Alt bilgi — Vitrin: beyaz kart yüzeyi, gri-mavi metinler --}}
    <footer class="mt-16 border-t border-stone-200/70 bg-white dark:border-stone-800 dark:bg-stone-900">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 md:grid-cols-4">
            <div>
                <div class="flex items-center gap-2.5">
                    @if ($logoPath)
                        <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ setting('genel.site_adi') }}" class="h-8 w-8 rounded-lg object-cover">
                    @else
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 text-white dark:text-stone-900">
                            <x-logo-mark class="h-4 w-4" />
                        </span>
                    @endif
                    <span class="text-lg font-extrabold tracking-tight text-stone-800 dark:text-stone-50">{{ Str::lower(setting('genel.site_adi')) }}</span>
                </div>
                <p class="mt-3 text-sm leading-relaxed text-stone-500 dark:text-stone-400">{{ setting('footer.aciklama') }}</p>
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
                <h3 class="text-sm font-bold text-stone-800 dark:text-stone-100">Keşfet</h3>
                <ul class="mt-3 space-y-2 text-sm font-medium text-stone-500 dark:text-stone-400">
                    <li><a href="{{ url('/ilanlar') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Tüm İlanlar</a></li>
                    @if (\App\Support\Modules::enabled('is_ilanlari'))
                        <li><a href="{{ route('jobs.index') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İş İlanları</a></li>
                        <li><a href="{{ route('candidates.index') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Yetenek Havuzu</a></li>
                    @endif
                    <li><a href="{{ url('/nasil-calisir') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Nasıl Çalışır?</a></li>
                    <li><a href="{{ route('nabiz') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Nisoya Nabzı</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-bold text-stone-800 dark:text-stone-100">Hesap</h3>
                <ul class="mt-3 space-y-2 text-sm font-medium text-stone-500 dark:text-stone-400">
                    <li><a href="{{ url('/giris') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Giriş Yap</a></li>
                    <li><a href="{{ url('/kayit') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Kayıt Ol</a></li>
                    <li><a href="{{ url('/panel/ilan/yeni') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İlan Ver</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-bold text-stone-800 dark:text-stone-100">Kurumsal</h3>
                <ul class="mt-3 space-y-2 text-sm font-medium text-stone-500 dark:text-stone-400">
                    @foreach (\App\Models\Page::navPages() as $navPage)
                        <li><a href="{{ url('/'.$navPage->slug) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">{{ $navPage->title }}</a></li>
                    @endforeach
                    <li><a href="{{ route('pages.contact') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İletişim</a></li>
                    <li><a href="/cerez-tercihleri" class="hover:text-emerald-700 dark:hover:text-emerald-400">Çerez Tercihleri</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-stone-100 py-4 dark:border-stone-800">
            <p class="mx-auto max-w-6xl px-4 text-center text-xs font-medium text-stone-400 dark:text-stone-500">
                © {{ date('Y') }} {{ setting('footer.telif_metni') }}
                <span class="mx-2 text-stone-300 dark:text-stone-600">·</span>
                Hizmet ücretsizdir. 💙
            </p>
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
