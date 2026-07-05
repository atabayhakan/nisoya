<?php

/*
|--------------------------------------------------------------------------
| Marka rengi seçenekleri
|--------------------------------------------------------------------------
| Her seçenek Tailwind'in kendi renk ailesine karşılık gelir (emerald,
| blue, rose vb.). Admin bir renk seçtiğinde resources/views/components/
| brand-theme.blade.php, sitede kullanılan --color-emerald-* CSS
| değişkenlerini seçilen ailenin değişkenlerine yönlendirir — hiçbir Blade
| dosyasındaki "emerald-600" gibi class'lara dokunmadan tüm site rengi
| değişir. 'hex' değerleri (Tailwind'in resmi 600 tonu) favicon ve
| <meta name="theme-color"> gibi CSS değişkeni KULLANAMAYAN yerlerde
| kullanılır — bkz. brandColorHex() (app/helpers.php).
|
| Yeni bir renk eklemek istersen: resources/css/app.css'teki
| @source inline(...) satırına o rengi de ekle (aksi halde Tailwind o
| rengin CSS değişkenlerini derlemez, override sessizce çalışmaz).
*/

return [
    'emerald' => ['label' => 'Yeşil (Zümrüt) — Varsayılan', 'hex' => '#059669'],
    'blue' => ['label' => 'Mavi', 'hex' => '#2563eb'],
    'rose' => ['label' => 'Gül Pembesi', 'hex' => '#e11d48'],
    'amber' => ['label' => 'Amber (Sarı-Turuncu)', 'hex' => '#d97706'],
    'violet' => ['label' => 'Mor', 'hex' => '#7c3aed'],
    'teal' => ['label' => 'Turkuaz', 'hex' => '#0d9488'],
    'indigo' => ['label' => 'Lacivert', 'hex' => '#4f46e5'],
    'orange' => ['label' => 'Turuncu', 'hex' => '#ea580c'],
];
