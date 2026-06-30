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
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧰</text></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-800 antialiased flex flex-col">
    {{-- Üst menü --}}
    <header class="sticky top-0 z-30 border-b border-stone-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-600 text-lg">🧰</span>
                <span class="text-xl font-bold tracking-tight text-stone-900">Nisoya</span>
            </a>

            <nav class="hidden items-center gap-6 text-sm font-medium text-stone-600 md:flex">
                <a href="{{ route('listings.index') }}" class="hover:text-emerald-700">İlanlar</a>
                <a href="{{ route('listings.map') }}" class="hover:text-emerald-700">Harita</a>
                <a href="{{ route('listings.index') }}" class="hover:text-emerald-700">Kategoriler</a>
                <a href="{{ route('pages.how') }}" class="hover:text-emerald-700">Nasıl Çalışır?</a>
            </nav>

            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-100 sm:inline-block">Panelim</a>
                    <a href="{{ url('/panel/ilan/yeni') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">İlan Ver</a>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-stone-500 hover:bg-stone-100">Çıkış</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-100 sm:inline-block">Giriş</a>
                    <a href="{{ route('register') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-100 sm:inline-block">Kayıt</a>
                    <a href="{{ url('/panel/ilan/yeni') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">İlan Ver</a>
                @endauth
            </div>
        </div>

        {{-- Mobil gezinme --}}
        <nav class="flex items-center gap-5 overflow-x-auto border-t border-stone-100 px-4 py-2 text-sm font-medium text-stone-600 md:hidden">
            <a href="{{ route('listings.index') }}" class="whitespace-nowrap hover:text-emerald-700">İlanlar</a>
            <a href="{{ route('listings.map') }}" class="whitespace-nowrap hover:text-emerald-700">Harita</a>
            <a href="{{ route('pages.how') }}" class="whitespace-nowrap hover:text-emerald-700">Nasıl Çalışır?</a>
            @guest
                <a href="{{ route('login') }}" class="whitespace-nowrap hover:text-emerald-700">Giriş</a>
                <a href="{{ route('register') }}" class="whitespace-nowrap hover:text-emerald-700">Kayıt</a>
            @else
                <a href="{{ route('dashboard') }}" class="whitespace-nowrap hover:text-emerald-700">Panelim</a>
            @endguest
        </nav>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Alt bilgi --}}
    <footer class="mt-16 border-t border-stone-200 bg-white">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-2 md:grid-cols-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-600 text-base">🧰</span>
                    <span class="text-lg font-bold text-stone-900">Nisoya</span>
                </div>
                <p class="mt-3 text-sm text-stone-500">Ne İş Olursa Yaparım. Yurt dışındaki Türklerin kendi aralarında yetenek ve hizmet pazaryeri.</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-stone-900">Keşfet</h3>
                <ul class="mt-3 space-y-2 text-sm text-stone-500">
                    <li><a href="{{ url('/ilanlar') }}" class="hover:text-emerald-700">Tüm İlanlar</a></li>
                    <li><a href="{{ route('listings.index') }}" class="hover:text-emerald-700">Kategoriler</a></li>
                    <li><a href="{{ url('/nasil-calisir') }}" class="hover:text-emerald-700">Nasıl Çalışır?</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-stone-900">Hesap</h3>
                <ul class="mt-3 space-y-2 text-sm text-stone-500">
                    <li><a href="{{ url('/giris') }}" class="hover:text-emerald-700">Giriş Yap</a></li>
                    <li><a href="{{ url('/kayit') }}" class="hover:text-emerald-700">Kayıt Ol</a></li>
                    <li><a href="{{ url('/panel/ilan/yeni') }}" class="hover:text-emerald-700">İlan Ver</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-stone-900">Kurumsal</h3>
                <ul class="mt-3 space-y-2 text-sm text-stone-500">
                    <li><a href="{{ url('/hakkimizda') }}" class="hover:text-emerald-700">Hakkımızda</a></li>
                    <li><a href="{{ url('/kosullar') }}" class="hover:text-emerald-700">Kullanım Koşulları</a></li>
                    <li><a href="{{ url('/gizlilik') }}" class="hover:text-emerald-700">Gizlilik</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-stone-100 py-4">
            <p class="mx-auto max-w-6xl px-4 text-xs text-stone-400">© {{ date('Y') }} Nisoya. Tüm hakları saklıdır.</p>
        </div>
    </footer>
</body>
</html>
