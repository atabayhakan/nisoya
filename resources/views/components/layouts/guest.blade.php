<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Nisoya' }}</title>
    {{-- Favicon + theme-color marka kaynağından (brandColorHex) — sabit zümrüt
         gömmek, panelden renk değişince giriş sayfalarını eski renkte bırakıyordu. --}}
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='6' fill='%23{{ ltrim(brandColorHex(), '#') }}'/><path d='M7 17V7L17 17V7' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round' fill='none'/></svg>">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="{{ brandColorHex() }}" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0c0a09" media="(prefers-color-scheme: dark)">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <x-theme-init />

    <x-layout-fonts />


    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-800 antialiased dark:bg-stone-950 dark:text-stone-200">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900">
                    <x-logo-mark class="h-5 w-5" />
                </span>
                <span class="text-2xl font-bold text-stone-900 dark:text-stone-50">Nisoya</span>
            </a>
            @unless (\App\Support\Tema::koyuKilit())
                <button
                    type="button"
                    onclick="window.toggleTheme && window.toggleTheme()"
                    class="ml-2 inline-flex rounded-lg p-2 text-stone-600 transition hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800"
                    title="Temayı değiştir"
                    aria-label="Karanlık/aydınlık tema değiştir"
                >
                    <x-heroicon-o-moon class="h-5 w-5 dark:hidden" />
                    <x-heroicon-o-sun class="hidden h-5 w-5 dark:inline" />
                </button>
            @endunless
        </div>

        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-stone-200 dark:bg-stone-900 dark:ring-stone-800">
            {{ $slot }}
        </div>

        {{-- GÜVEN BAĞLANTILARI (2026-08-05).

             Bu layout'u kayıt, giriş, parola sıfırlama ve hesap kurtarma
             sayfaları kullanıyor ve içinde site footer'ı YOK. Yani bir
             reklamdan ya da paylaşımdan doğrudan /kayit'e gelen kişi, formu
             doldurmadan önce sitenin kim olduğuna dair HİÇBİR bağımsız işaret
             göremiyordu — "Hakkımızda", "Gizlilik" gibi bağlantılar yalnız
             onay kutusunun metnine gömülüydü.

             Yeni birinden e-posta ve parola istiyorsan, kim olduğunu
             gösterecek yolu da vermen gerekir. --}}
        <nav class="mt-6 flex flex-wrap justify-center gap-x-4 gap-y-1.5 text-xs text-stone-600 dark:text-stone-400" aria-label="Site bilgileri">
            <a href="{{ url('/hakkimizda') }}" class="hover:text-emerald-700 hover:underline dark:hover:text-emerald-400">Hakkımızda</a>
            <a href="{{ url('/guvenli-alisveris') }}" class="hover:text-emerald-700 hover:underline dark:hover:text-emerald-400">Güvenli alışveriş</a>
            <a href="{{ url('/gizlilik') }}" class="hover:text-emerald-700 hover:underline dark:hover:text-emerald-400">Gizlilik</a>
            <a href="{{ url('/kosullar') }}" class="hover:text-emerald-700 hover:underline dark:hover:text-emerald-400">Kullanım koşulları</a>
            <a href="{{ url('/iletisim') }}" class="hover:text-emerald-700 hover:underline dark:hover:text-emerald-400">İletişim</a>
        </nav>

        <p class="mt-3 text-xs text-stone-600 dark:text-stone-400">© {{ date('Y') }} Nisoya — Ne İş Olursa Yaparız</p>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }
    </script>
</body>
</html>