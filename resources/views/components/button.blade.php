@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

{{--
    Paylaşılan buton (2026-08-20 tasarım denetimi): aynı iş için sitede 84
    farklı elle yazılmış sınıf kombinasyonu vardı — köşe, gölge ve hover
    davranışı her yerde biraz farklıydı. `primary` gölgesi `shadow-brand`
    kullanır (bkz. resources/css/app.css) — marka rengini panelden
    değiştirince otomatik takip eder; `shadow-[rgba(62,99,240,…)]` gibi ham
    bir renk asla buraya sabitlenmemeli.

    GERÇEK OLAY (2026-08-21, mobilde ölçüldü): çağıran `class="hidden
    md:inline-flex"` verdiğinde buton mobilde de görünüp taşıyordu. Sebep
    CSS SIRASI: derlenmiş dosyada `.inline-flex` `.hidden`'DAN SONRA geliyor
    (ikisi de tek-sınıf, eşit özgüllük) — bu bileşenin kendi sabit
    `inline-flex`'i her zaman kazanıyordu, HTML'deki sınıf SIRASININ hiçbir
    önemi yok. Çağıran zaten bir görünürlük/display sınıfı veriyorsa
    (hidden/flex/block/…, düz ya da duyarlı önekli) kendi `inline-flex`'imizi
    HİÇ eklemiyoruz — aksi hâlde ikisi çakışıp bu bileşen her zaman kazanır.
--}}
@php
    $gorunurlukDeseni = '/(^|\s)((sm|md|lg|xl|2xl):)?(hidden|block|inline-block|inline|flex|inline-flex|grid|inline-grid|contents|table)(\s|$)/';
    $caginanGorunurlukVeriyor = (bool) preg_match($gorunurlukDeseni, (string) $attributes->get('class', ''));

    $taban = ($caginanGorunurlukVeriyor ? '' : 'inline-flex ').'items-center justify-center gap-1.5 rounded-xl font-bold transition disabled:cursor-not-allowed disabled:opacity-60';

    $varyantlar = [
        'primary' => 'bg-emerald-700 text-white shadow-brand hover:-translate-y-0.5 hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900 dark:shadow-none',
        'inverse' => 'bg-white text-stone-800 hover:bg-stone-100',
        'outline-dark' => 'border border-white/25 text-white hover:bg-white/10',
        'secondary' => 'border border-stone-300 text-stone-700 hover:bg-stone-50 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800',
    ];

    $boyutlar = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-3 text-sm',
    ];

    $siniflar = trim($taban.' '.($varyantlar[$variant] ?? $varyantlar['primary']).' '.($boyutlar[$size] ?? $boyutlar['md']));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $siniflar]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $siniflar]) }}>{{ $slot }}</button>
@endif
