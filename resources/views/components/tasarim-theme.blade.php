@php
    $mod = setting('gorunum.tasarim_modu', 'eski');
@endphp

{{-- Faz İ1 (2027 vizyon pilotu, bkz. /yonetim Tasarım Modu): "2. Tasarım"
     seçiliyken marka rengini "Vitrin Yeşili"ye, sayfa zeminini "Tezgah
     Kremi"ye çeker. brand-theme.blade.php'den SONRA render edilir — admin
     ayrıca özel bir marka rengi (mor, mavi vb.) seçmiş olsa bile "2. Tasarım"
     kendi imza yeşilini kazanır (bilinçli tercih, bkz. vizyon belgesindeki
     "admin'in marka rengi seçme özgürlüğü" notu). --}}
@if ($mod === 'yeni')
    <style>
        :root {
            --color-emerald-50: #f1f9f6;
            --color-emerald-100: #def2eb;
            --color-emerald-200: #bce6d8;
            --color-emerald-300: #8ed7be;
            --color-emerald-400: #57c7a1;
            --color-emerald-500: #38b289;
            --color-emerald-600: #0f5c42;
            --color-emerald-700: #0c4a35;
            --color-emerald-800: #0a3829;
            --color-emerald-900: #09291e;
            --color-emerald-950: #061a13;
            --color-stone-50: #f3eee4;
            /* Tailwind ailesi değil, tek amaçlı özel bir jeton — Mühür Kızılı.
               bkz. components/pulse-map.blade.php (canlı nabız rengi). */
            --nisoya-seal: #c1440e;
        }
    </style>
@endif
