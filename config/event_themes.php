<?php

// Davetiye temaları — tamamı Tailwind sınıflarıyla yerli (harici servis yok).
// Her tema public davetiye sayfasının (resources/views/davet/show.blade.php)
// arka plan, vurgu ve kart renklerini belirler.
//
// ÖNEMLİ: Davetiye TASARLANMIŞ bir üründür; her misafirde AYNI görünmeli.
// Bu yüzden temalar misafirin cihaz temasına (OS light/dark) göre DEĞİŞMEZ —
// her tema kendi sabit paletini taşır. Koyu görünen temalar `dark => true`
// ile işaretlenir (show.blade.php <html>'e statik `dark` sınıfı ekler, böylece
// paylaşılan `dark:` bileşen stilleri — form alanları vb. — tutarlı çalışır).
//
// Her `card` MUTLAKA net bir metin rengi (text-*) içermeli — yoksa koyu kartta
// metin okunmaz (tarayıcı varsayılanı siyah kalır).
return [
    'zarif' => [
        'label' => 'Zarif (düğün & nişan)',
        'page' => 'bg-gradient-to-b from-emerald-50/70 via-stone-50 to-stone-100 text-stone-800',
        'card' => 'bg-white/90 text-stone-800 border-emerald-200/80 shadow-emerald-900/5',
        'accent' => 'text-emerald-700',
        'muted' => 'text-stone-500',
        'button' => 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm shadow-emerald-900/20',
        'ornament' => '❦',
    ],
    'gece' => [
        'label' => 'Gece & Hilal (kına, iftar, mevlid)',
        'page' => 'bg-stone-950 text-stone-100',
        'card' => 'bg-stone-900 text-stone-100 border-amber-700/40 shadow-black/40',
        'accent' => 'text-amber-400',
        'muted' => 'text-stone-400',
        'button' => 'bg-amber-500 hover:bg-amber-400 text-stone-900 shadow-sm shadow-amber-900/40',
        'ornament' => '✦',
        'dark' => true, // bu tema her zaman koyu görünür (statik dark sınıfı)
    ],
    'kutlama' => [
        'label' => 'Kutlama (sünnet, doğum günü, baby shower)',
        'page' => 'bg-gradient-to-b from-sky-50 via-white to-sky-50 text-stone-800',
        'card' => 'bg-white/90 text-stone-800 border-sky-200 shadow-sky-900/5',
        'accent' => 'text-sky-600',
        'muted' => 'text-stone-500',
        'button' => 'bg-sky-600 hover:bg-sky-700 text-white shadow-sm shadow-sky-900/20',
        'ornament' => '🎈',
    ],
    'dogal' => [
        'label' => 'Doğal (mezuniyet, vaftiz, diğer)',
        'page' => 'bg-gradient-to-b from-emerald-50/60 via-stone-50 to-stone-100 text-stone-800',
        'card' => 'bg-white/90 text-stone-800 border-stone-200 shadow-stone-900/5',
        'accent' => 'text-stone-700',
        'muted' => 'text-stone-500',
        'button' => 'bg-stone-800 hover:bg-stone-900 text-white shadow-sm shadow-stone-900/25',
        'ornament' => '🌿',
    ],
];
