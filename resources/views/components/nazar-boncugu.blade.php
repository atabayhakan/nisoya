{{--
    Nazar boncuğu — İlan Ver FAB'ının Türkiye-ikonik simgesi (2026-08-21).

    NEDEN AY-YILDIZ DEĞİL: `<x-ay-yildiz>` bu depoda özellikle Nisoya'nın
    "acil yardım kimliği" olarak tanımlı (bkz. o dosyanın kendi yorumu) —
    "ülkendeki Türklere/temsilciliğe hızlı ulaş" anlamı taşıyor. İlan Ver gibi
    ilgisiz bir eylemde kullanmak yanlış sinyal verir (acil/resmî çağrışımı).
    Nazar boncuğu böyle bir anlam yükü taşımıyor, sadece evrensel tanınan bir
    Türk motifi.

    SABİT RENKLER, `currentColor` DEĞİL (ay-yıldız'dan farkı): nazar
    boncuğunun kimliği renginde — mavi/beyaz/lacivert halkalar olmadan
    tanınmaz. Hangi arka plana konursa konsun aynı görünmeli.

    GRADYAN + BENZERSİZ ID (2026-08-21, "daha gerçekçi olsun" isteği üzerine):
    cam/boncuk hissi için radyal gradyan şart, ama `<defs>` içindeki `id`
    sayfada BİRDEN FAZLA basılırsa çakışıp SESSİZCE bozulur — tam da
    ay-yıldız'ın yorumunda uyarılan tuzak (o yüzden ay-yıldız maske değil
    evenodd path kullanıyor). Burada gradyan kaçınılmaz olduğu için çözüm
    farklı: `uniqid()` ile HER basımda benzersiz id — iki kez basılsa bile
    çakışmaz.
--}}
@php $uid = 'nazar-'.uniqid(); @endphp
<svg {{ $attributes->merge(['class' => 'h-4 w-4']) }}
     viewBox="0 0 24 24" aria-hidden="true">
    <defs>
        <radialGradient id="{{ $uid }}-outer" cx="38%" cy="32%" r="75%">
            <stop offset="0%" stop-color="#3D63C9" />
            <stop offset="60%" stop-color="#1B3D8F" />
            <stop offset="100%" stop-color="#0E2258" />
        </radialGradient>
        <radialGradient id="{{ $uid }}-mid" cx="36%" cy="30%" r="75%">
            <stop offset="0%" stop-color="#6EA0FF" />
            <stop offset="55%" stop-color="#2F6FE0" />
            <stop offset="100%" stop-color="#1B4BB0" />
        </radialGradient>
        <radialGradient id="{{ $uid }}-pupil" cx="34%" cy="28%" r="80%">
            <stop offset="0%" stop-color="#2F4066" />
            <stop offset="65%" stop-color="#0B1730" />
            <stop offset="100%" stop-color="#000410" />
        </radialGradient>
    </defs>

    <circle cx="12" cy="12" r="11.6" fill="url(#{{ $uid }}-outer)" />
    <circle cx="12" cy="12" r="9.3" fill="#F5F7FA" />
    <circle cx="12" cy="12" r="7.6" fill="url(#{{ $uid }}-mid)" />
    <circle cx="12" cy="12" r="4.5" fill="#F5F7FA" />
    <circle cx="12" cy="12" r="2.7" fill="url(#{{ $uid }}-pupil)" />

    {{-- Cam parlaklığı — iki katmanlı, gerçek bir boncuğun üstündeki ışık
         yansımasını taklit ediyor (geniş+soluk + dar+parlak). --}}
    <ellipse cx="8.6" cy="7.9" rx="2.9" ry="1.7" fill="#FFFFFF" opacity=".45" transform="rotate(-32 8.6 7.9)" />
    <ellipse cx="9.4" cy="8.7" rx="1" ry="0.6" fill="#FFFFFF" opacity=".85" transform="rotate(-32 9.4 8.7)" />
</svg>
