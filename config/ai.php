<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Yapay Zeka Sağlayıcısı (provider-agnostic)
    |--------------------------------------------------------------------------
    | Sisteme eklenen HER yapay zeka özelliği (şu an: kamera-önce ilan görüntü
    | analizi) bu katmandan geçer. Sağlayıcıyı değiştirmek için tek yapılacak
    | AI_PROVIDER değerini değiştirmek — kodda hiçbir değişiklik gerekmez.
    |
    | Yeni bir sağlayıcı eklemek: App\Contracts\AiProvider'ı uygulayan bir sınıf
    | yaz, aşağıya bir config bloğu ekle ve App\Services\Ai\AiManager'daki
    | $providers eşlemesine kaydet.
    */

    'default' => env('AI_PROVIDER', 'anthropic'),

    /*
    | AI fotoğraf üretim modeli (App\Services\Ai\FotografUretici — şimdilik
    | yalnız demo ilan görselleri). OpenRouter model adı; sağlayıcı yeniden
    | adlandırırsa site_settings'e 'ai.gorsel_model' yazarak panelsiz
    | değiştirilebilir (bkz. AppServiceProvider::mergeAiConfig).
    */
    'gorsel_model' => env('AI_GORSEL_MODEL', 'google/gemini-2.5-flash-image'),

    // Özellik bayrakları — sağlayıcıdan bağımsız. Anahtar yoksa özellik
    // zaten kapanır; bu bayrak anahtar varken bile kapatmak için.
    'features' => [
        'quick_listing' => (bool) env('AI_QUICK_LISTING', true),
        // Serbest metinden ilan taslağı (birkaç kelime ya da WhatsApp'tan
        // yapıştırılan metin). Bkz. App\Services\ListingTextService.
        'text_listing' => (bool) env('AI_TEXT_LISTING', true),
        // İşletmeye tanışma postasındaki tek kişisel cümle. Mektubun gövdesi
        // koddan gelir; model yalnız o cümleyi yazar ve HİÇBİR ŞEY GÖNDERMEZ.
        // Bkz. App\Services\Growth\ErisimMesajiYazari.
        'outreach_draft' => (bool) env('AI_OUTREACH_DRAFT', true),
        // İlan görselleri + sohbet fotoğrafları için otomatik uygunsuz içerik
        // ön-elemesi. Görsel SİLİNMEZ — yalnızca işaretlenir/incelemeye alınır
        // (bkz. App\Services\ImageModerationService).
        'image_moderation' => (bool) env('AI_IMAGE_MODERATION', true),
    ],

    /*
    | ÇİFT ŞEMA UYARISI (2026-07-30, Kâhya F0): Bu dosyayı iki tüketici okur.
    | (1) Bizim AI katmanımız (App\Services\Ai\*) → `api_key`/`model`/`base_url`.
    | (2) laravel/ai paketi (Kâhya'nın ajan çekirdeği) → `driver`/`key`/`url`.
    | Paket kendi config/ai.php'sini mergeConfigFrom ile SIĞ birleştirir: bizim
    | üst-düzey `providers` anahtarımız paketinkini TAMAMEN gölgeler — bu yüzden
    | paketin beklediği anahtarlar her girdide AYRICA taşınmalı. Bir sağlayıcının
    | anahtarını değiştirirken İKİ anahtarı da (api_key + key) güncel tut;
    | çalışma zamanında admin paneli ayarları ikisini birden ezer
    | (bkz. AppServiceProvider::applyAiSettings).
    */

    'providers' => [

        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            // Görüntü destekli, en düşük maliyetli Claude modeli.
            'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
            // laravel/ai anahtarları:
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            // OpenAI-uyumlu (Azure/yerel) uçlar için değiştirilebilir.
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            // laravel/ai anahtarları:
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],

        // OpenRouter — OpenAI-uyumlu tek uçtan yüzlerce model. Model adı
        // sağlayıcı önekli (ör. "openai/gpt-4o-mini", "google/gemini-2.0-flash-001").
        'openrouter' => [
            'api_key' => env('OPENROUTER_API_KEY'),
            'model' => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'referer' => env('APP_URL', 'https://nisoya.com'),
            'title' => 'Nisoya',
            // laravel/ai anahtarları:
            'driver' => 'openrouter',
            'key' => env('OPENROUTER_API_KEY'),
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            // laravel/ai anahtarları:
            'driver' => 'gemini',
            'key' => env('GEMINI_API_KEY'),
            'url' => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta/'),
        ],

    ],

];
