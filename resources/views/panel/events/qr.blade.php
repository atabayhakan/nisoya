<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $event->title }} — QR Masa Kartı</title>
    <meta name="robots" content="noindex, nofollow">
    <x-layout-fonts />

    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .print-card { box-shadow: none !important; border: 1px dashed #d6d3d1; }
        }
    </style>
</head>
<body class="min-h-screen bg-stone-100 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center gap-6 px-4 py-10">

        <div class="no-print flex items-center gap-3">
            <button onclick="window.print()" class="rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-800">🖨️ Yazdır</button>
            <a href="{{ route('panel.events.show', $event) }}" class="text-sm font-medium text-stone-500 hover:text-stone-700">← Davetiye yönetimine dön</a>
        </div>
        <p class="no-print max-w-md text-center text-sm text-stone-500">
            Bu kartı yazdırıp masalara koy — misafirler QR'ı okutunca davetiye ve anı akışı sayfası açılır,
            çektikleri fotoğrafları oradan paylaşırlar.
        </p>

        {{-- Yazdırılacak kart (A6 oranı) --}}
        <div class="print-card w-[420px] rounded-3xl border border-stone-200 bg-white p-10 text-center shadow-lg">
            <div class="text-4xl">{{ $event->type->emoji() }}</div>
            <h1 class="mt-3 text-2xl font-bold text-stone-900">{{ $event->title }}</h1>
            <p class="mt-1 text-sm text-stone-500">{{ $event->starts_at->translatedFormat('j F Y') }}</p>

            <div class="mx-auto mt-6 w-64 [&>svg]:h-full [&>svg]:w-full">{!! $qrSvg !!}</div>

            <p class="mt-6 text-lg font-semibold text-stone-800">📸 Fotoğraflarını bizimle paylaş!</p>
            <p class="mt-1 text-sm text-stone-500">QR kodu telefonunla okut,<br>çektiğin kareleri anı akışına yükle.</p>

            <p class="mt-6 text-2xs uppercase tracking-widest text-stone-300">nisoya.com</p>
        </div>
    </div>
</body>
</html>
