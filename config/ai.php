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

    // Özellik bayrakları — sağlayıcıdan bağımsız. Anahtar yoksa özellik
    // zaten kapanır; bu bayrak anahtar varken bile kapatmak için.
    'features' => [
        'quick_listing' => (bool) env('AI_QUICK_LISTING', true),
        // İlan görselleri + sohbet fotoğrafları için otomatik uygunsuz içerik
        // ön-elemesi. Görsel SİLİNMEZ — yalnızca işaretlenir/incelemeye alınır
        // (bkz. App\Services\ImageModerationService).
        'image_moderation' => (bool) env('AI_IMAGE_MODERATION', true),
    ],

    'providers' => [

        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            // Görüntü destekli, en düşük maliyetli Claude modeli.
            'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            // OpenAI-uyumlu (Azure/yerel) uçlar için değiştirilebilir.
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],

        // OpenRouter — OpenAI-uyumlu tek uçtan yüzlerce model. Model adı
        // sağlayıcı önekli (ör. "openai/gpt-4o-mini", "google/gemini-2.0-flash-001").
        'openrouter' => [
            'api_key' => env('OPENROUTER_API_KEY'),
            'model' => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'referer' => env('APP_URL', 'https://nisoya.com'),
            'title' => 'Nisoya',
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        ],

    ],

];
