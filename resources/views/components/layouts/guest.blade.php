<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Nisoya' }}</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='6' fill='%23059669'/><path d='M7 17V7L17 17V7' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round' fill='none'/></svg>">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#059669" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0c0a09" media="(prefers-color-scheme: dark)">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <x-theme-init />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-800 antialiased dark:bg-stone-950 dark:text-stone-200">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-600 text-white dark:bg-emerald-500 dark:text-stone-900">
                    <x-logo-mark class="h-5 w-5" />
                </span>
                <span class="text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-50">Nisoya</span>
            </a>
            <button
                type="button"
                onclick="window.toggleTheme && window.toggleTheme()"
                class="ml-2 inline-flex rounded-lg p-2 text-stone-500 transition hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800"
                title="Temayı değiştir"
                aria-label="Karanlık/aydınlık tema değiştir"
            >
                <x-heroicon-o-moon class="h-5 w-5 dark:hidden" />
                <x-heroicon-o-sun class="hidden h-5 w-5 dark:inline" />
            </button>
        </div>

        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-stone-200 dark:bg-stone-900 dark:ring-stone-800">
            {{ $slot }}
        </div>

        <p class="mt-6 text-xs text-stone-400 dark:text-stone-500">© {{ date('Y') }} Nisoya — Ne İş Olursa Yaparım</p>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }
    </script>
</body>
</html>