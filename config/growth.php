<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Büyüme (Tanıtım) Ajanı
    |--------------------------------------------------------------------------
    | Keşif KÜRESELDİR (AB/TR dahil her yer taranıp saklanabilir — pazar zekâsı),
    | GÖNDERİM ise bölge-kapılıdır. marketing_status kararı App\Support\Growth\
    | RegionPolicy tarafından bu ayarlara göre verilir. Bkz. docs/06-tanitim-agenti-plani.md.
    */

    // Gönderim yalnızca bu ülkelere açık (ISO-2). Boş bırakılırsa allowlist
    // kısıtı uygulanmaz (yalnızca hariç/kısıtlı listeleri işler).
    'sending_allowlist' => array_filter(array_map('trim', explode(
        ',',
        (string) env('GROWTH_SENDING_ALLOWLIST', 'US,KZ,KG,UZ,TH,KH'),
    ))),

    // Hukuken riskli → keşifte olabilir ama gönderimden hariç. Rusya (152-FZ +
    // Reklam Kanunu ön-onay ister); AB-27 + TR zaten RegionPolicy sabitinde.
    'restricted_countries' => ['RU'],

    // Google Places (Text Search) — anahtar yoksa keşif fixture kaynağına düşer.
    'google_places' => [
        'api_key' => env('GOOGLE_PLACES_API_KEY'),
    ],

];
