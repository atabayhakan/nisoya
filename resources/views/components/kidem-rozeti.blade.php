@props(['user', 'variant' => 'pill'])

{{--
    Paylaşılan kıdem rozeti (2026-08-20 tasarım denetimi): aynı veri
    (User::kidemMetni()/kidemKisa()) 4 bağımsız yüzeyde 4 farklı görsel
    tarifle basılıyordu. `pill` (kart bağlamı, kısa form, boşsa hiç
    basılmaz) ve `text` (detay sayfası, tam cümle) tek birer tarife
    indirildi. Metnin bir cümle akışına gömüldüğü tek yer
    (seller-mobile-strip.blade.php) kasıtlı olarak dışarıda bırakıldı —
    orada ayrı bir sarmalayıcı div yerine mevcut satırın devamı gerekiyor.
--}}
@if ($variant === 'pill')
    @if ($kidem = $user->kidemKisa())
        <span {{ $attributes->merge(['class' => 'rounded-full bg-stone-100 px-2.5 py-1.5 text-2xs font-bold text-stone-600 dark:bg-stone-800 dark:text-stone-400']) }} title="{{ $user->kidemMetni() }}">{{ $kidem }}</span>
    @endif
@else
    <div {{ $attributes->merge(['class' => 'text-xs font-medium text-stone-500 dark:text-stone-400']) }}>{{ $user->kidemMetni() }}</div>
@endif
