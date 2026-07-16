@props(['width' => null, 'height' => null])

@php
    $w = (int) $width;
    $h = (int) $height;
    $showItem = $w >= 1 && $h >= 1;
    // En az bir ölçü varsa göster (biri yoksa diğerini yüksekliğe düşürürüz).
    $render = $w >= 1 || $h >= 1;

    // Sadece biri girildiyse: yükseklik yoksa eni yükseklik say (dikey kutu gibi
    // gösterme), en yoksa yüksekliğin yarısı kadar makul bir en varsay.
    $iw = $w >= 1 ? $w : max(20, (int) round($h * 0.6));
    $ih = $h >= 1 ? $h : max(20, (int) round($w * 0.6));

    $humanCm = 170;
    $maxCm = max($ih, $humanCm);
    $drawH = 230;                       // en uzun nesnenin px yüksekliği
    $scale = $drawH / $maxCm;           // px / cm
    $pad = 20;
    $groundY = $pad + $drawH;

    // İnsan (referans)
    $humanHpx = $humanCm * $scale;
    $headR = max(6, $humanHpx / 13);
    $humanWpx = $headR * 2.6;
    $humanX = $pad;
    $humanCx = $humanX + $humanWpx / 2;
    $topY = $groundY - $humanHpx;

    // Ürün kutusu
    $itemWpx = $iw * $scale;
    $itemHpx = $ih * $scale;
    $gap = 46;
    $itemX = $humanX + $humanWpx + $gap;
    $itemY = $groundY - $itemHpx;

    $totalW = $itemX + $itemWpx + $pad + 4;
    $totalH = $groundY + 34;

    $dimLabel = ($w >= 1 && $h >= 1)
        ? $w.' × '.$h.' cm'
        : ($w >= 1 ? 'Genişlik '.$w.' cm' : 'Yükseklik '.$h.' cm');
@endphp

@if ($render)
    <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-800 dark:bg-stone-900/40">
        <h2 class="flex items-center gap-2 text-sm font-semibold text-stone-800 dark:text-stone-100">
            📏 Boyut karşılaştırma
        </h2>
        <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">Ürünün ~170 cm'lik bir yetişkinle ölçekli kıyası.</p>

        <div class="mt-3 overflow-x-auto">
            <svg viewBox="0 0 {{ round($totalW) }} {{ round($totalH) }}" class="h-auto max-w-full text-stone-400 dark:text-stone-500" style="min-width: {{ round(min($totalW, 520)) }}px" role="img" aria-label="{{ $dimLabel }} ürün, 170 cm insan ile karşılaştırma">
                {{-- Zemin çizgisi --}}
                <line x1="{{ round($pad - 6) }}" y1="{{ round($groundY) }}" x2="{{ round($totalW - $pad + 6) }}" y2="{{ round($groundY) }}" stroke="currentColor" stroke-opacity="0.35" stroke-width="1.5" />

                {{-- İnsan silüeti (referans) --}}
                <g fill="currentColor">
                    <circle cx="{{ round($humanCx) }}" cy="{{ round($topY + $headR) }}" r="{{ round($headR) }}" />
                    <rect x="{{ round($humanCx - $humanWpx / 2) }}" y="{{ round($topY + $headR * 1.7) }}" width="{{ round($humanWpx) }}" height="{{ round($humanHpx - $headR * 1.7) }}" rx="{{ round($humanWpx / 2) }}" />
                </g>
                <text x="{{ round($humanCx) }}" y="{{ round($groundY + 22) }}" text-anchor="middle" fill="currentColor" font-size="12">≈170 cm</text>

                {{-- Ürün kutusu --}}
                <rect x="{{ round($itemX) }}" y="{{ round($itemY) }}" width="{{ round($itemWpx) }}" height="{{ round($itemHpx) }}" rx="6" fill="#10b981" fill-opacity="0.85" stroke="#059669" stroke-width="1.5" />
                <text x="{{ round($itemX + $itemWpx / 2) }}" y="{{ round($groundY + 22) }}" text-anchor="middle" fill="#059669" font-size="12" font-weight="600">{{ $dimLabel }}</text>
            </svg>
        </div>
    </div>
@endif
