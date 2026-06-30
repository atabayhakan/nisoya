<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Nisoya' }}</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧰</text></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-800 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <a href="{{ url('/') }}" class="mb-6 flex items-center gap-2">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-600 text-xl">🧰</span>
            <span class="text-2xl font-bold tracking-tight text-stone-900">Nisoya</span>
        </a>

        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-stone-200">
            {{ $slot }}
        </div>

        <p class="mt-6 text-xs text-stone-400">© {{ date('Y') }} Nisoya — Ne İş Olursa Yaparım</p>
    </div>
</body>
</html>
