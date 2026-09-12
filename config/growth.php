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

    // Keşif kaynağı: 'overpass' (OpenStreetMap — ÜCRETSİZ, kart/anahtar yok),
    // 'google' (Google Places — anahtar + faturalandırma gerekir),
    // 'fixture' (demo), 'auto' (anahtar varsa Google, yoksa fixture — güvenli
    // varsayılan). Admin panelden (Büyüme Ajanı) değiştirilir.
    'source' => env('GROWTH_SOURCE', 'auto'),

    // Google Places (Text Search) — anahtar yoksa/kaynak seçili değilse fixture.
    'google_places' => [
        'api_key' => env('GOOGLE_PLACES_API_KEY'),
    ],

    // Otomatik Tersine Katılım (Reverse Onboarding): Keşfedilen yüksek güvenli Türk
    // işletmeleri için doğrudan sahiplenilebilir vitrin ilanı oluşturulsun mu?
    'auto_create_listings' => (bool) env('GROWTH_AUTO_CREATE_LISTINGS', false),

    // Yapay Zekâ (LLM) ile şüpheli/sınırda esnaf tespiti yapılsın mı?
    'use_llm' => (bool) env('GROWTH_USE_LLM', false),

    // LLM ve tespit güven eşiği (bu yüzdenin üstü doğrudan geçer, altı inceleme bekler)
    'min_confidence' => (int) env('GROWTH_MIN_CONFIDENCE', 70),

    // Günlük maksimum taranacak işletme kotası (Google Places bütçe koruması)
    'daily_limit' => (int) env('GROWTH_DAILY_LIMIT', 100),

    // Kalite filtresi: Minimum Google Places puanı
    'min_rating' => (float) env('GROWTH_MIN_RATING', 3.5),

    // Kalite filtresi: Minimum Google Places yorum sayısı
    'min_reviews' => (int) env('GROWTH_MIN_REVIEWS', 3),

    // WhatsApp ve davet mesajı imza / yetkili adı
    'whatsapp_signature' => env('GROWTH_WHATSAPP_SIGNATURE', 'Hakan · nisoya.com'),
];
