<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- SEO/og/favicon/manifest meta'ları — paylaşılan tek kopya (app.blade.php
         ile aynı bileşen). Öncesinde bu layout kendi title/favicon/manifest'ini
         elle basıyordu ve canonical etiketi hiç yoktu — giriş/kayıt sayfaları
         sitenin "tek kaynak" SEO sözleşmesinin dışında kalmıştı. --}}
    <x-layout-head-meta :title="$title ?? null" />

    <x-theme-init />

    <x-layout-fonts />


    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-100/70 text-stone-800 antialiased dark:bg-stone-950 dark:text-stone-200">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-8 sm:py-12">
        <div class="mb-6 flex items-center justify-between gap-4 w-full max-w-md px-1">
            <a href="{{ url('/') }}" class="group flex items-center gap-2.5 transition active:scale-95">
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-tr from-emerald-700 to-emerald-500 text-white shadow-brand transition duration-200 group-hover:scale-105">
                    <x-logo-mark class="h-5 w-5" />
                </span>
                <span class="text-2xl font-black tracking-tight text-stone-900 dark:text-stone-50">Nisoya</span>
            </a>
            @unless (\App\Support\Tema::koyuKilit())
                <button
                    type="button"
                    onclick="window.toggleTheme && window.toggleTheme()"
                    class="grid h-9 w-9 place-items-center rounded-xl border border-stone-200/80 bg-white/80 text-stone-600 shadow-2xs transition hover:bg-stone-100 hover:text-stone-900 dark:border-stone-800 dark:bg-stone-900/80 dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-stone-200"
                    title="Temayı değiştir"
                    aria-label="Karanlık/aydınlık tema değiştir"
                >
                    <x-heroicon-o-moon class="h-4 w-4 dark:hidden" />
                    <x-heroicon-o-sun class="hidden h-4 w-4 dark:inline" />
                </button>
            @endunless
        </div>

        <div class="w-full max-w-md rounded-3xl border border-stone-200/90 bg-white p-7 sm:p-9 shadow-xl shadow-stone-200/40 ring-1 ring-black/5 dark:border-stone-800 dark:bg-stone-900/95 dark:shadow-none">
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