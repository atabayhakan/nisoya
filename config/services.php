<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google AdSense & Analytics
    |--------------------------------------------------------------------------
    | Bu sistem gelirini AdSense reklamlarından elde eder ve Analytics ile
    | ölçüm yapar. Kimlikler .env'den okunur; boşsa script'ler hiç yüklenmez.
    */

    'adsense' => [
        'enabled' => (bool) env('ADSENSE_ENABLED', false),
        'publisher_id' => env('ADSENSE_PUBLISHER_ID'),       // ca-pub-XXXXXXXXXXXXXXXX
        'auto_ads_code' => env('ADSENSE_AUTO_ADS_CODE'),     // tam <script> içeriği (admin panelden — Site Yönetimi → İçerik)
    ],

    'analytics' => [
        'enabled' => (bool) env('ANALYTICS_ENABLED', false),
        'measurement_id' => env('ANALYTICS_MEASUREMENT_ID'), // G-XXXXXXXXXX
        'custom_code' => null,                                // admin panelden — Site Yönetimi → İçerik
    ],

    // Admin panelden (Site Yönetimi → İçerik) yönetilen ham HTML/JS enjeksiyon
    // noktaları — </head> ve </body> öncesi. Varsayılan boş; sadece DB'de
    // değer varsa AppServiceProvider::mergeRuntimeConfig() doldurur.
    'custom_head_code' => null,
    'custom_footer_code' => null,

    /*
    |--------------------------------------------------------------------------
    | Bağış (Donation)
    |--------------------------------------------------------------------------
    | Sistem uzun süre ücretsiz hizmet verir. Gelir yalnızca AdSense + bağış
    | ile sağlanır. PayPal.me linki ve IBAN admin panelden yönetilir.
    */

    'donation' => [
        'paypal_me' => env('DONATION_PAYPAL_ME'),    // örn: paypal.me/nisoya
        'iban' => env('DONATION_IBAN'),              // TR ile başlayan IBAN
        'iban_owner' => env('DONATION_IBAN_OWNER'),  // Hesap sahibi adı
    ],

    /*
    |--------------------------------------------------------------------------
    | Reverse Geocoding (GPS → adres)
    |--------------------------------------------------------------------------
    | Görsel EXIF'inden çıkarılan GPS koordinatlarından şehir/ülke tespiti.
    | Nominatim (OpenStreetMap) ücretsiz API'sini kullanır; rate limit 1 req/s.
    | Test ortamında otomatik devre dışı.
    */
    'reverse_geocoding' => [
        'enabled' => (bool) env('REVERSE_GEOCODING_ENABLED', true),
        'cache_days' => (int) env('REVERSE_GEOCODING_CACHE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | MaxMind GeoLite2 (ziyaretçi ülkesi tespiti)
    |--------------------------------------------------------------------------
    | Header'da "hangi ülkeden giriliyorsa o bayrak" özelliği için. Ücretsiz
    | ip-api.com/ipapi.co gibi servisler ticari sitelerde kullanım şartlarını
    | ihlal ettiğinden, ücretsiz + ticari kullanıma uygun tek yerel çözüm olan
    | MaxMind GeoLite2 veritabanı tercih edildi (dış API çağrısı yok).
    | Lisans anahtarı: https://www.maxmind.com/en/geolite2/signup (ücretsiz).
    */
    'maxmind' => [
        'license_key' => env('MAXMIND_LICENSE_KEY'),
        'database_path' => storage_path('app/geoip/GeoLite2-Country.mmdb'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anthropic (Claude) — Kamera-önce hızlı ilan (Faz M3)
    |--------------------------------------------------------------------------
    | Fotoğraftan başlık/kategori/fiyat önerisi üretir (görüntü analizi).
    | API anahtarı yoksa özellik kendiliğinden kapanır; giriş noktası gizlenir.
    | Model varsayılanı: Claude Haiku 4.5 — görüntü destekli, en düşük maliyetli
    | Claude modeli. Ücretsiz platform bütçesine uygun; .env ile değiştirilebilir.
    */
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
        // Anahtar varsa varsayılan açık; ANTHROPIC_QUICK_LISTING=false ile kapatılır.
        'quick_listing_enabled' => (bool) env('ANTHROPIC_QUICK_LISTING', true),
    ],

];
