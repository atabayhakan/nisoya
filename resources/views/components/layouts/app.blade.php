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
        class="sticky top-0 z-30 border-b border-stone-200/80 bg-white/95 backdrop-blur-md transition-all duration-300 dark:border-stone-800/80 dark:bg-stone-900/95"
    >
        <div :class="scrolled ? 'py-2 shadow-sm' : 'py-3'" class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 transition-all duration-300 sm:gap-4">
            {{-- Sol: Logo + Nav Menüleri --}}
            <div class="flex items-center gap-5 sm:gap-6">
                <a href="{{ url('/') }}" class="group flex items-center gap-2 shrink-0">
                    <x-logo-ikon>
                        @if ($logoPath = setting('gorunum.logo_path'))
                            <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ setting('genel.site_adi') }}" class="h-9 w-9 rounded-xl object-cover">
                        @else
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-700 text-white transition group-hover:bg-emerald-800 dark:bg-emerald-500 dark:group-hover:bg-emerald-400 dark:text-stone-900">
                                <x-logo-mark class="h-5 w-5" />
                            </span>
                        @endif
                    </x-logo-ikon>
                    @if (\App\Support\TemaJetonlari::logoAnimasyonuAktifMi())
                        <x-hareketli-logo class="h-8 max-[380px]:hidden" />
                    @else
                        <span class="text-xl font-bold text-stone-900 max-[380px]:hidden dark:text-stone-50">{{ setting('genel.site_adi') }}</span>
                    @endif
                </a>

                <nav class="hidden items-center gap-5 text-[13px] font-semibold text-stone-600 lg:flex dark:text-stone-300">
                    <x-mega-menu :items="$navLinksMega" />
                    @foreach ($navLinksSingle as $navLink)
                        <a href="{{ $navLink->url }}" @if ($navLink->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif class="relative py-1 transition hover:text-emerald-700 dark:hover:text-emerald-400">{{ $navLink->label }}</a>
                    @endforeach
                </nav>
            </div>

            {{-- Sağ: Arama + Ülke + Acil + Tema + Kullanıcı & CTA --}}
            <div class="flex items-center gap-1.5 sm:gap-2">
                <x-command-palette :nav-links="$navLinks" />
                <x-ulke-secici :country="$visitorCountry" :countries="$emergencyCountries" />
                <div class="{{ auth()->check() ? '' : 'hidden md:block' }}">
                    <x-emergency-button
                        :categories="$emergencyCategories"
                        :countries="$emergencyCountries"
                        :default-country="$emergencyDefaultCountry"
                        :city="$emergencyCity"
                    />
                </div>

                @unless (\App\Support\Tema::koyuKilit())
                    <button
                        type="button"
                        onclick="window.toggleTheme && window.toggleTheme()"
                        class="hidden h-9 w-9 place-items-center rounded-full text-stone-500 transition hover:bg-stone-100 hover:text-stone-800 md:grid dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-stone-200"
                        title="Temayı değiştir"
                        aria-label="Karanlık/aydınlık tema değiştir"
                    >
                        <x-heroicon-o-moon class="h-4 w-4 dark:hidden" />
                        <x-heroicon-o-sun class="hidden h-4 w-4 dark:inline" />
                    </button>
                @endunless

                {{-- Favoriler — mobilde başlıkta --}}
                @auth
                    <a href="{{ route('panel.favorites.index') }}" class="flex h-9 w-9 items-center justify-center rounded-full text-stone-600 transition hover:bg-stone-100 md:hidden dark:text-stone-300 dark:hover:bg-stone-800" title="Favorilerim" aria-label="Favorilerim">
                        <x-heroicon-o-heart class="h-4 w-4" />
                    </a>
                @endauth

                {{-- Mobilde kimlik + çıkış --}}
                <x-mobil-hesap />

                @auth
                    <span class="hidden h-5 w-px bg-stone-200 md:block dark:bg-stone-800"></span>

                    @php($unreadCount = auth()->user()->okunmamisBildirimSayisi())
                    <a href="{{ route('panel.messages.index') }}" class="relative hidden h-9 w-9 place-items-center rounded-full text-stone-600 transition hover:bg-stone-100 md:grid dark:text-stone-300 dark:hover:bg-stone-800" title="Mesajlar">
                        <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" />
                        @if ($okunmamisMesaj = auth()->user()->okunmamisMesajSayisi())
                            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white dark:ring-stone-900">{{ $okunmamisMesaj > 9 ? '9+' : $okunmamisMesaj }}</span>
                        @endif
                    </a>
                    <a href="{{ route('panel.notifications.index') }}" class="relative hidden h-9 w-9 place-items-center rounded-full text-stone-600 transition hover:bg-stone-100 md:grid dark:text-stone-300 dark:hover:bg-stone-800" title="Bildirimler">
                        <x-heroicon-o-bell class="h-5 w-5 {{ $unreadCount ? 'animate-[nisoya-bell-ring_0.6s_ease-in-out_1]' : '' }}" />
                        @if ($unreadCount)
                            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white dark:ring-stone-900">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('panel.profile.edit') }}" class="hidden items-center gap-2 rounded-full border border-stone-200/90 bg-stone-50/80 py-1 pl-1 pr-3 text-xs font-bold text-stone-700 shadow-2xs transition hover:border-emerald-300 hover:bg-white hover:text-emerald-700 md:flex dark:border-stone-800 dark:bg-stone-800/60 dark:text-stone-200 dark:hover:border-emerald-600 dark:hover:text-emerald-300" title="Hesabım">
                        <x-avatar :user="auth()->user()" size="h-6 w-6" text="text-[10px]" />
                        <span class="max-w-[100px] truncate">{{ auth()->user()->name }}</span>
                    </a>

                    <a href="{{ route('dashboard') }}" class="hidden h-9 items-center rounded-full px-2.5 text-xs font-bold text-stone-700 transition hover:bg-stone-100 md:inline-flex dark:text-stone-300 dark:hover:bg-stone-800">Panelim</a>

                    <a href="{{ url('/panel/ilan/yeni') }}" class="hidden h-9 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full bg-emerald-700 px-3.5 text-xs font-bold text-white shadow-brand transition hover:-translate-y-0.5 hover:bg-emerald-800 hover:shadow-md md:inline-flex dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                        <x-heroicon-o-plus class="h-4 w-4 stroke-2" />
                        <span>İlan Ver</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                        @csrf
                        <button type="submit" class="inline-flex h-9 items-center rounded-full px-2 text-xs font-semibold text-stone-500 transition hover:bg-red-50 hover:text-red-600 dark:text-stone-400 dark:hover:bg-red-950/30 dark:hover:text-red-400" title="Çıkış Yap">
                            <x-heroicon-o-arrow-right-on-rectangle class="h-4 w-4" />
                            <span class="sr-only">Çıkış</span>
                        </button>
                    </form>
                @else
                    <span class="hidden h-5 w-px bg-stone-200 md:block dark:bg-stone-800"></span>
                    <a href="{{ route('login') }}" class="hidden h-9 items-center rounded-full px-3 text-xs font-bold text-stone-700 transition hover:bg-stone-100 md:inline-flex dark:text-stone-200 dark:hover:bg-stone-800">Giriş</a>
                    <a href="{{ route('register') }}" class="hidden h-9 items-center rounded-full px-3 text-xs font-bold text-stone-700 transition hover:bg-stone-100 md:inline-flex dark:text-stone-200 dark:hover:bg-stone-800">Kayıt</a>
                    <a href="{{ url('/panel/ilan/yeni') }}" class="hidden h-9 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full bg-emerald-700 px-3.5 text-xs font-bold text-white shadow-brand transition hover:-translate-y-0.5 hover:bg-emerald-800 hover:shadow-md md:inline-flex dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                        <x-heroicon-o-plus class="h-4 w-4 stroke-2" />
                        <span>İlan Ver</span>
                    </a>
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

    {{-- Alt bilgi (Konteyner İçinde / Taşmasız Çerçeveli Düzen) --}}
    <footer class="mx-auto max-w-6xl px-4 mt-20 mb-10 w-full" aria-label="Site alt bilgisi">
        <div class="relative overflow-hidden rounded-3xl border border-stone-200/90 bg-white p-8 sm:p-12 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            {{-- Arka plan parlama --}}
            <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-[radial-gradient(circle,color-mix(in_srgb,var(--color-emerald-500)_12%,transparent),transparent_70%)]" aria-hidden="true"></div>

            {{-- Üst Bölüm: Topluluk & Hızlı Eylem Banner'ı --}}
            <div class="relative z-10 border-b border-stone-100 pb-8 dark:border-stone-800">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Diaspora Topluluğu
                            </span>
                            <span class="text-xs font-medium text-stone-500 dark:text-stone-400">12+ Ülke · 150+ Şehirde Türkler İçin Tek Çatı</span>
                        </div>
                        <h2 class="mt-2 text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                            Yurt dışında aradığın usta, iş, ev veya halı saha takımı burada.
                        </h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('panel.listings.create') }}"
                           class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800">
                            <x-heroicon-o-plus class="h-4 w-4" />
                            <span>İlan Ver</span>
                        </a>
                        @if (\App\Support\Modules::enabled('hali_saha'))
                            <a href="{{ route('football.index') }}"
                               class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200 bg-stone-50 px-4 py-2.5 text-xs sm:text-sm font-semibold text-stone-700 transition hover:bg-stone-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/80">
                                <span>⚽</span>
                                <span>Halı Saha Hub'ı</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Orta Bölüm: 4 Dengeli Sütun --}}
            <div class="relative z-10 mt-10 grid gap-10 sm:grid-cols-2 lg:grid-cols-5">
                
                {{-- Sütun 1: Marka & Sosyal (lg:col-span-2) --}}
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-2.5">
                        @if ($logoPath)
                            <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ setting('genel.site_adi') }}" class="h-8 w-8 rounded-xl object-cover">
                        @else
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-700 text-white shadow-xs dark:bg-emerald-500 dark:text-stone-900">
                                <x-logo-mark class="h-4 w-4" />
                            </span>
                        @endif
                        <div class="flex flex-col">
                            <span class="text-lg font-bold tracking-tight text-stone-900 dark:text-stone-50">{{ setting('genel.site_adi') }}</span>
                            <span class="text-[11px] font-medium text-stone-500 dark:text-stone-400">Gurbetteki Güvenli Buluşma Noktan</span>
                        </div>
                    </div>

                    <p class="mt-3.5 max-w-sm text-sm leading-relaxed text-stone-500 dark:text-stone-400">
                        {{ setting('footer.aciklama') ?: 'Yurt dışında yaşayan Türklerin kendi aralarında güvenle hizmet bulduğu, iş ve emlak ilanları paylaştığı topluluk platformu.' }}
                    </p>

                    {{-- Sosyal Medya İkonları --}}
                    @if (setting('footer.sosyal_instagram') || setting('footer.sosyal_facebook') || setting('footer.sosyal_whatsapp'))
                        <div class="mt-5 flex items-center gap-2 text-stone-600 dark:text-stone-400">
                            @if (setting('footer.sosyal_instagram'))
                                <a href="{{ setting('footer.sosyal_instagram') }}" target="_blank" rel="noopener noreferrer"
                                   class="grid h-9 w-9 place-items-center rounded-xl border border-stone-200/80 bg-stone-50 text-stone-600 transition hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-800/60 dark:text-stone-400 dark:hover:border-emerald-400 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300"
                                   aria-label="Instagram">
                                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                </a>
                            @endif
                            @if (setting('footer.sosyal_facebook'))
                                <a href="{{ setting('footer.sosyal_facebook') }}" target="_blank" rel="noopener noreferrer"
                                   class="grid h-9 w-9 place-items-center rounded-xl border border-stone-200/80 bg-stone-50 text-stone-600 transition hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-800/60 dark:text-stone-400 dark:hover:border-emerald-400 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300"
                                   aria-label="Facebook">
                                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                            @endif
                            @if (setting('footer.sosyal_whatsapp'))
                                <a href="{{ setting('footer.sosyal_whatsapp') }}" target="_blank" rel="noopener noreferrer"
                                   class="grid h-9 w-9 place-items-center rounded-xl border border-stone-200/80 bg-stone-50 text-stone-600 transition hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-800/60 dark:text-stone-400 dark:hover:border-emerald-400 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300"
                                   aria-label="WhatsApp">
                                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Sütun 2: Keşfet & İlanlar --}}
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-900 dark:text-stone-100">İlanlar &amp; Hizmetler</h3>
                    <ul class="mt-4 space-y-2.5 text-sm font-medium text-stone-500 dark:text-stone-400">
                        <li>
                            <a href="{{ url('/ilanlar') }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">Tüm İlanlar</a>
                        </li>
                        <li>
                            <a href="{{ url('/ilanlar?q=usta') }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">Usta &amp; Hizmetler</a>
                        </li>
                        @if (\App\Support\Modules::enabled('is_ilanlari'))
                            <li>
                                <a href="{{ route('jobs.index') }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">İş İlanları</a>
                            </li>
                            <li>
                                <a href="{{ route('candidates.index') }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">Yetenek Havuzu</a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ url('/ilanlar?q=emlak') }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">Emlak &amp; Barınma</a>
                        </li>
                    </ul>
                </div>

                {{-- Sütun 3: Topluluk & Yaşam --}}
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-900 dark:text-stone-100">Topluluk &amp; Yaşam</h3>
                    <ul class="mt-4 space-y-2.5 text-sm font-medium text-stone-500 dark:text-stone-400">
                        @if (\App\Support\Modules::enabled('hali_saha'))
                            <li>
                                <a href="{{ route('football.index') }}" class="inline-flex items-center gap-1.5 font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300">
                                    <span>⚽</span>
                                    <span>Halı Saha Ligi</span>
                                    <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">Yeni</span>
                                </a>
                            </li>
                        @endif
                        @if (\App\Support\Modules::enabled('rehber') && ($rehberFooterUlke = app(\App\Services\RehberYuzeyi::class)->girisNoktasiUlkeKodu(request()->user(), request())) !== null)
                            <li>
                                <a href="{{ route('rehber.ulke', $rehberFooterUlke) }}" class="inline-flex items-center gap-1.5 transition hover:text-emerald-700 dark:hover:text-emerald-400">
                                    <span>🏛️</span>
                                    <span>Ülke Rehberi</span>
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ url('/nasil-calisir') }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">Nasıl Çalışır?</a>
                        </li>
                        <li>
                            <a href="{{ route('nabiz') }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">Nisoya Nabzı</a>
                        </li>
                        <li>
                            <a href="{{ url('/giris') }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">Giriş Yap</a>
                        </li>
                        <li>
                            <a href="{{ url('/kayit') }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">Kayıt Ol</a>
                        </li>
                    </ul>
                </div>

                {{-- Sütun 4: Kurumsal & Destek --}}
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-900 dark:text-stone-100">Kurumsal &amp; Destek</h3>
                    <ul class="mt-4 space-y-2.5 text-sm font-medium text-stone-500 dark:text-stone-400">
                        @foreach (\App\Models\Page::navPages() as $navPage)
                            <li>
                                <a href="{{ url('/'.$navPage->slug) }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">{{ $navPage->title }}</a>
                            </li>
                        @endforeach
                        <li>
                            <a href="{{ route('pages.contact') }}" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">İletişim</a>
                        </li>
                        <li>
                            <a href="/cerez-tercihleri" class="transition hover:text-emerald-700 dark:hover:text-emerald-400">Çerez Tercihleri</a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Alt Bölüm: Telif & Güven Rozetleri --}}
            <div class="relative z-10 mt-10 border-t border-stone-100 pt-6 dark:border-stone-800">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between text-xs font-medium text-stone-500 dark:text-stone-400">
                    <p>
                        © {{ date('Y') }} {{ setting('footer.telif_metni') }}
                        <span class="mx-2 text-stone-300 dark:text-stone-400" aria-hidden="true">·</span>
                        Hizmet ücretsizdir. 💛
                    </p>
                    <div class="flex items-center gap-4 text-stone-500 dark:text-stone-400">
                        <span class="inline-flex items-center gap-1">
                            <x-heroicon-s-lock-closed class="h-3.5 w-3.5 text-emerald-700 dark:text-emerald-400" />
                            <span>SSL Güvenli Bağlantı</span>
                        </span>
                        <span>·</span>
                        <span class="inline-flex items-center gap-1">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span>%100 Topluluk Odaklı</span>
                        </span>
                    </div>
                </div>
            </div>
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