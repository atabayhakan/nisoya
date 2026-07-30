{{--
    Kâhya'nın avatar rozeti — balon, tam sayfa ve mesaj akışında AYNI görünüm.
    Bare emoji yerine tutarlı bir marka öğesi: hafif tonlu daire + ince halka.
    $durum true ise sağ altta "aktif" noktası gösterir (yalnız üstbilgide kullanılır).
--}}
@props(['boyut' => 'h-9 w-9 text-base', 'durum' => false])

<span {{ $attributes->merge(['class' => "relative inline-flex shrink-0 items-center justify-center {$boyut} rounded-full bg-primary-50 ring-1 ring-primary-600/20 dark:bg-primary-500/10 dark:ring-primary-400/30"]) }}>
    <span aria-hidden="true">🤵</span>
    @if ($durum)
        <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-green-500 ring-2 ring-white dark:ring-gray-900"></span>
    @endif
</span>
