<?php

/*
|--------------------------------------------------------------------------
| Vitrin vurgu rampaları
|--------------------------------------------------------------------------
| Vitrin teması sabit bir palet basıyordu; bu dosya vurgu (birincil) rengini
| sahibin seçebileceği hâle getirir. Nötrler (--color-stone-*), yazı tipi ve
| gölgeler BİLİNÇLİ olarak kilitlidir — Vitrin'in kimliği tek bir hex değil,
| gri-mavi nötr sistemi + Plus Jakarta Sans + 12/14/20/24 yarıçap ölçeğidir.
|
| NEDEN SERBEST HEX DEĞİL, ELLE YAZILMIŞ RAMPA:
| Vitrin'in koyu setinde --color-emerald-600 açık moddakinden DAHA AÇIK bir
| tona çıkar (#3E63F0 → #6b8afd). Sebebi, klasikten miras `dark:text-stone-900`
| sınıfının koyu modda #131c2f'e yönlenmesi: buton metni KOYU olduğu için zemin
| açık olmak zorunda. Serbest bir hex'ten color-mix ile rampa türetmek bu
| sözleşmeyi sessizce kırar — açık modda güzel görünen bir renk, koyu modda
| okunmaz butonlar üretir ve sahip bunu fark etmez. Her rampa bu yüzden açık ve
| koyu setini BİRLİKTE taşır; VitrinAksanTest ikisinin kontrastını da doğrular.
|
| 'deniz' varsayılandır ve değerleri, bu dosya eklenmeden önceki
| vitrin-theme.blade.php ile BİREBİR aynıdır — dokunulmamış bir sitede üretilen
| CSS bayt bayt değişmez (tests/Feature/TemaTest.php literal olarak
| '--color-emerald-600: #3E63F0' mühürlüyor).
*/

return [
    'deniz' => [
        'label' => 'Deniz Mavisi — Vitrin varsayılanı',
        'hex' => '#3E63F0',
        'acik' => [
            50 => '#eef2ff', 100 => '#dfe6fb', 200 => '#c3d0fa', 300 => '#a8c0ff',
            400 => '#8ea5ff', 500 => '#5b7cff', 600 => '#3E63F0', 700 => '#2f4fd0',
            800 => '#26409f', 900 => '#1d316f', 950 => '#10203c',
        ],
        'koyu' => [300 => '#a8c0ff', 400 => '#8ea5ff', 500 => '#6b8afd', 600 => '#6b8afd', 700 => '#8ea5ff'],
    ],

    'zumrut' => [
        'label' => 'Zümrüt Yeşili',
        'hex' => '#0f9d6b',
        'acik' => [
            50 => '#ecfdf5', 100 => '#d7f7e9', 200 => '#b0edd3', 300 => '#7fdfb8',
            400 => '#4fcf9b', 500 => '#22b57f', 600 => '#0f9d6b', 700 => '#0c8158',
            800 => '#0a6547', 900 => '#084c36', 950 => '#0a2e22',
        ],
        'koyu' => [300 => '#7fdfb8', 400 => '#4fcf9b', 500 => '#34d399', 600 => '#34d399', 700 => '#6ee7b7'],
    ],

    'mor' => [
        'label' => 'Mor',
        'hex' => '#7549e8',
        'acik' => [
            50 => '#f5f3ff', 100 => '#ece7fe', 200 => '#dbd0fd', 300 => '#c3b0fb',
            400 => '#a688f7', 500 => '#8b5cf6', 600 => '#7549e8', 700 => '#6036c6',
            800 => '#4c2b9c', 900 => '#392173', 950 => '#221444',
        ],
        'koyu' => [300 => '#c3b0fb', 400 => '#a688f7', 500 => '#a78bfa', 600 => '#a78bfa', 700 => '#c4b5fd'],
    ],

    'kiremit' => [
        'label' => 'Kiremit',
        'hex' => '#c9491f',
        'acik' => [
            50 => '#fef3ef', 100 => '#fde5db', 200 => '#fac8b4', 300 => '#f5a486',
            400 => '#ef7f58', 500 => '#e35f34', 600 => '#c9491f', 700 => '#a53a19',
            800 => '#802d14', 900 => '#5e210f', 950 => '#351208',
        ],
        'koyu' => [300 => '#f5a486', 400 => '#ef7f58', 500 => '#fb923c', 600 => '#fb923c', 700 => '#fdba74'],
    ],

    'antrasit' => [
        'label' => 'Antrasit — sakin/kurumsal',
        'hex' => '#3f4c63',
        'acik' => [
            50 => '#f1f5f9', 100 => '#e2e8f0', 200 => '#cbd5e1', 300 => '#a8b4c8',
            400 => '#7d8ba5', 500 => '#5a6880', 600 => '#3f4c63', 700 => '#313c50',
            800 => '#262f3f', 900 => '#1b222e', 950 => '#0f141c',
        ],
        'koyu' => [300 => '#a8b4c8', 400 => '#94a3b8', 500 => '#cbd5e1', 600 => '#cbd5e1', 700 => '#e2e8f0'],
    ],
];
