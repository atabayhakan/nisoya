{{--
    HAREKETLİ LOGO YAZISI (2026-08-19, sahibin isteği).

    "Nisoya" kelimesi el yazısıyla çiziliyormuş gibi animasyonla açılır.
    Çizim yolu ÖNCEDEN ÜRETİLMİŞ (bkz. TemaJetonlari::EL_YAZISI_FONTLAR) —
    tarayıcı hiçbir font dosyası indirip işlemez, düz bir SVG path'i.

    Bu bileşen YALNIZCA site adı tam olarak "Nisoya" ise ve ayar açıksa
    çağrılmalı — kontrol çağıran tarafta (TemaJetonlari::logoAnimasyonuAktifMi),
    çünkü kapalıyken/uyuşmazken çağıran zaten mevcut düz metin span'ini basmaya
    devam ediyor. `pathLength="1"` ile çizgi uzunluğu normalize edilir, yani
    stroke-dasharray/dashoffset her font için AYNI "1" değeriyle çalışır —
    fontlar arası gerçek path uzunluğu farkı hesaba katılmaz.
--}}
@props(['class' => ''])
@php
    $font = \App\Support\TemaJetonlari::elYazisiFontu(setting('gorunum.logo_yazi_tipi', 'indie-flower'));
    $renk = setting('gorunum.logo_rengi', '#059669');
@endphp
<svg
    viewBox="{{ $font['viewBox'] }}"
    class="{{ $class }}"
    role="img"
    aria-label="{{ setting('genel.site_adi') }}"
    fill="none"
>
    <path
        d="{{ $font['yol'] }}"
        stroke="{{ $renk }}"
        stroke-width="4"
        stroke-linecap="round"
        stroke-linejoin="round"
        pathLength="1"
        stroke-dasharray="1"
        stroke-dashoffset="1"
        class="hareketli-logo-cizgi"
    />
</svg>
