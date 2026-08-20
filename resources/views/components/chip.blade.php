@props(['tone' => 'neutral'])

{{--
    Paylaşılan durum/kategori etiketi (2026-08-20 tasarım denetimi): teal
    tonu ("Online"/"Doğrulandı" rozetleri) 6 dosyaya harfi harfine kopyalanmış
    ham hex olarak duruyordu. Tonlar bilerek marka tokenından (--shadow-brand
    vb.) ayrı — bunlar kategori/durum ayrımı için nötr, dekoratif renkler,
    marka rengine bağlı DEĞİL.
--}}
@php
    $tonlar = [
        'neutral' => 'bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300',
        'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400',
        'amber' => 'bg-[#fff6e8] text-[#b9741a] dark:bg-amber-950/60 dark:text-amber-300',
        'teal' => 'bg-[#e7f7f1] text-[#0f9d76] dark:bg-teal-950/60 dark:text-teal-300',
        'rose' => 'bg-[#fdeeeb] text-[#c2452f] dark:bg-rose-950/60 dark:text-rose-300',
        'violet' => 'bg-[#f1ecfe] text-[#8a6bf2] dark:bg-violet-950/60 dark:text-violet-300',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-1.5 text-2xs font-bold '.($tonlar[$tone] ?? $tonlar['neutral'])]) }}>{{ $slot }}</span>
