<?php

// Davetiye temaları — tamamı Tailwind sınıflarıyla yerli (harici servis yok).
// Her tema public davetiye sayfasının (resources/views/davet/show.blade.php)
// arka plan, vurgu ve kart renklerini belirler. Yeni tema eklerken buradaki
// anahtarı Event::THEME_KEYS doğrulamasına da yansıtmaya gerek yok — liste
// buradan okunur (tek kaynak).
return [
    'zarif' => [
        'label' => 'Zarif (düğün & nişan)',
        'page' => 'bg-stone-100 dark:bg-stone-950',
        'card' => 'bg-white dark:bg-stone-900 border-emerald-200 dark:border-emerald-900',
        'accent' => 'text-emerald-700 dark:text-emerald-400',
        'button' => 'bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900',
        'ornament' => '❦',
    ],
    'gece' => [
        'label' => 'Gece & Hilal (kına, iftar, mevlid)',
        'page' => 'bg-stone-950',
        'card' => 'bg-stone-900 border-amber-700/40 text-stone-100',
        'accent' => 'text-amber-400',
        'button' => 'bg-amber-500 hover:bg-amber-400 text-stone-900',
        'ornament' => '✦',
        'dark_only' => true, // bu tema her zaman koyu görünür
    ],
    'kutlama' => [
        'label' => 'Kutlama (sünnet, doğum günü, baby shower)',
        'page' => 'bg-sky-50 dark:bg-stone-950',
        'card' => 'bg-white dark:bg-stone-900 border-sky-200 dark:border-sky-900',
        'accent' => 'text-sky-600 dark:text-sky-400',
        'button' => 'bg-sky-600 hover:bg-sky-700 text-white dark:bg-sky-500 dark:hover:bg-sky-400 dark:text-stone-900',
        'ornament' => '🎈',
    ],
    'dogal' => [
        'label' => 'Doğal (mezuniyet, vaftiz, diğer)',
        'page' => 'bg-emerald-50/60 dark:bg-stone-950',
        'card' => 'bg-white dark:bg-stone-900 border-stone-200 dark:border-stone-800',
        'accent' => 'text-stone-700 dark:text-stone-300',
        'button' => 'bg-stone-800 hover:bg-stone-900 text-white dark:bg-stone-200 dark:hover:bg-white dark:text-stone-900',
        'ornament' => '🌿',
    ],
];
