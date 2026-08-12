@props(['gorsel' => null, 'buyuk' => false])

{{-- TEMSİLÎ GÖRSEL ROZETİ — tek kaynak.

     Bu rozet dört yüzeyde birden görünmek zorunda: klasik kart, vitrin
     kartı, klasik ilan detayı, vitrin ilan detayı. Metni ya da rengi
     dosyaların içine ayrı ayrı yazsaydık biri güncellenip diğeri unuturdu
     ve ETİKETSİZ bir yerde AI görseli gerçek fotoğraf gibi görünürdü —
     özelliğin tek gerçek riski bu.

     `is_representative` yoksa hiçbir şey basmaz; gerçek fotoğrafların
     yanına yanlışlıkla eklense bile zararsız. --}}

@if ($gorsel?->is_representative)
    <span {{ $attributes->merge(['class' => 'pointer-events-none inline-flex items-center gap-1 rounded-full bg-stone-900/75 font-semibold text-white backdrop-blur-sm dark:bg-black/70 '.($buyuk ? 'px-3 py-1.5 text-xs' : 'px-2 py-1 text-2xs')]) }}>
        <x-heroicon-o-sparkles class="{{ $buyuk ? 'h-4 w-4' : 'h-3 w-3' }}" />
        Temsilî görsel
    </span>
@endif
