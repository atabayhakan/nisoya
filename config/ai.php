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
        // İlana temsilî kapak görseli üretme YETENEĞİ (tüm ilan tipleri —
        // hizmet/ürün/emlak/vasıta). Kapalıysa ne panel düğmesi ne otomatik
        // komut çalışır. Bkz. App\Services\TemsiliGorselUretici.
        'service_image' => (bool) env('AI_SERVICE_IMAGE', true),
        // Yukarıdakinin OTOMATİK tetiklenmesi: 2+ gündür görselsiz aktif
        // ilanlara komut kendiliğinden görsel üretir. Yeteneğin kendisinden
        // (service_image) ayrı bayrak — sahip düğmeyi açık tutup otomatik
        // taramayı kapatabilsin (ya da tersi) diye.
        // Bkz. App\Console\Commands\TemsiliGorselOtomatikUret.
        'auto_representative_image' => (bool) env('AI_AUTO_REPRESENTATIVE_IMAGE', true),
        // Otomatik görsel üretiminden ÖNCE ilan metnini ahlaki/uygunluk
        // açısından ön-eler — dolandırıcılık deseninden (text_moderation)
        // FARKLI bir soru: "bu ilana kamuya açık bir görsel üretmek uygun mu"
        // (yetişkin içerik, yasa dışı mal/hizmet, nefret söylemi vb.).
        // Uygun değilse görsel üretilmez, ilan Beklemede'ye alınır.
        // Bkz. App\Services\AhlakDenetimi.
        'content_ethics_check' => (bool) env('AI_CONTENT_ETHICS_CHECK', true),
        // İlanı bulunduğu ülkenin diline çevirme (yerel arama trafiği).
        // Bkz. App\Services\IlanCevirmeni.
        'listing_translation' => (bool) env('AI_LISTING_TRANSLATION', true),
        // İlan METNİNDE dolandırıcılık deseni ön-elemesi — görsel
        // moderasyonunun ikizi. Bkz. App\Services\DolandiricilikTespiti.
        'text_moderation' => (bool) env('AI_TEXT_MODERATION', true),
        // Doğal dille arama: cümleyi var olan süzgeçlere çevirir (yeni bir
        // arama motoru DEĞİL). Bkz. App\Services\DogalDilArama.
        'natural_search' => (bool) env('AI_NATURAL_SEARCH', true),
        // Anasayfa "Nisoya AI ile ara" çubuğu: soruyu Rehber (konsolosluk/
        // resmî işlem) veya ilan aramasına yönlendirir — kendi cevap ÜRETMEZ.
        // Bkz. App\Services\NisoyaAiYonlendirici.
        'nisoya_ai_arama' => (bool) env('AI_NISOYA_ARAMA', true),
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

        // NVIDIA NIM — OpenAI-uyumlu kurumsal uç (Llama 3.2 Vision, Nemotron vb.).
        'nvidia' => [
            'api_key' => env('NVIDIA_API_KEY'),
            'model' => env('NVIDIA_MODEL', 'meta/llama-3.2-11b-vision-instruct'),
            'base_url' => env('NVIDIA_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
            // laravel/ai anahtarları:
            'driver' => 'openai',
            'key' => env('NVIDIA_API_KEY'),
            'url' => env('NVIDIA_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
        ],

        // Groq — OpenAI-uyumlu ultra-hızlı LPU çıkarımı (Llama 3.2 Vision vb.).
        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'llama-3.2-11b-vision-preview'),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            // laravel/ai anahtarları:
            'driver' => 'openai',
            'key' => env('GROQ_API_KEY'),
            'url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        ],

        // DeepSeek — OpenAI-uyumlu DeepSeek-V3 ve DeepSeek-R1 uçları.
        'deepseek' => [
            'api_key' => env('DEEPSEEK_API_KEY'),
            'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
            // laravel/ai anahtarları:
            'driver' => 'openai',
            'key' => env('DEEPSEEK_API_KEY'),
            'url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
        ],

        // Mistral AI — OpenAI-uyumlu Pixtral (vision) ve Mistral Large modelleri.
        'mistral' => [
            'api_key' => env('MISTRAL_API_KEY'),
            'model' => env('MISTRAL_MODEL', 'pixtral-12b-2409'),
            'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
            // laravel/ai anahtarları:
            'driver' => 'openai',
            'key' => env('MISTRAL_API_KEY'),
            'url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Model Context Protocol (MCP) Sunucusu Ayarları
    |--------------------------------------------------------------------------
    | Claude, ChatGPT, Cursor ve diğer yapay zekâ asistanlarının Nisoya ile
    | güvenli konuşmasını sağlayan API anahtarı ve yetkilendirme ayarları.
    */
    'mcp' => [
        'enabled' => (bool) env('NISOYA_MCP_ENABLED', true),
        'api_key' => env('NISOYA_MCP_KEY'),
        'route' => 'api/mcp',
    ],

];
