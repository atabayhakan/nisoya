<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tüm yapay zekâ sağlayıcıları için güncel ve çalışan modelleri yöneten kayıt defteri.
 *
 * Sağlayıcıların API'lerinden (OpenRouter, NVIDIA NIM, Groq, DeepSeek vb.) canlı model
 * listelerini çeker, görüntü (Vision) desteklerini belirler, 24 saat önbellekler ve
 * günlük otomatik denetim komutuyla (`ai:modelleri-denetle`) modellerin sağlığını doğrular.
 */
class AiModelRegistry
{
    public const CACHE_TTL_HOURS = 24;

    public const CACHE_PREFIX = 'ai_registry_models_';

    /**
     * Sağlayıcı için kullanılabilir model listesini döndürür (id => Etiket).
     *
     * @return array<string, string>
     */
    public function getAvailableModels(string $provider, bool $forceRefresh = false): array
    {
        $cacheKey = self::CACHE_PREFIX.$provider;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        /** @var array<string, string> */
        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($provider): array {
            return $this->fetchModelsForProvider($provider);
        });
    }

    /**
     * Sağlayıcı için modelleri kategorize edilmiş (optgroup uyumlu) dizi olarak döndürür.
     *
     * @return array<string, array<string, string>>
     */
    public function getGroupedModels(string $provider, ?string $currentModel = null): array
    {
        $all = $this->getAvailableModels($provider);
        $groups = [];

        $free = [];
        $vision = [];
        $textOnly = [];

        foreach ($all as $id => $label) {
            $isFree = str_contains($id, ':free')
                || str_contains($label, 'Ücretsiz')
                || str_contains($label, 'Free');

            if ($isFree) {
                $free[$id] = $label;
            } elseif (str_contains($label, '[Vision]')) {
                $vision[$id] = $label;
            } else {
                $textOnly[$id] = $label;
            }
        }

        if ($free !== []) {
            $groups['🎁 Ücretsiz Modeller (Free Tier — $0 Maliyet)'] = $free;
        }

        if ($vision !== []) {
            $groups['⭐ Vision Destekli Modeller (Görüntü + Metin — Fotoğraflı İlan İçin Önerilen)'] = $vision;
        }

        if ($textOnly !== []) {
            $groups['⚡ Metin & Hızlı Muhakeme Modelleri (Vision Yok)'] = $textOnly;
        }

        if (filled($currentModel) && ! isset($all[$currentModel])) {
            $groups['🛠️ Özel / Mevcut Model'] = [
                $currentModel => "{$currentModel} (Özel / Mevcut Seçim)",
            ];
        }

        return $groups;
    }

    /**
     * Sağlayıcının canlı API'sinden veya güncel kataloğundan modelleri çeker.
     *
     * @return array<string, string>
     */
    public function fetchModelsForProvider(string $provider): array
    {
        return match ($provider) {
            'openrouter' => $this->fetchOpenRouterModels(),
            'nvidia' => $this->fetchNvidiaModels(),
            'groq' => $this->fetchGroqModels(),
            'deepseek' => $this->deepseekModels(),
            'mistral' => $this->fetchMistralModels(),
            'openai' => $this->openaiModels(),
            'anthropic' => $this->anthropicModels(),
            'gemini' => $this->geminiModels(),
            default => [],
        };
    }

    /**
     * OpenRouter API'sinden canlı modelleri çeker ve Vision destekli olanları öne çıkarır.
     *
     * @return array<string, string>
     */
    private function fetchOpenRouterModels(): array
    {
        if (app()->runningUnitTests()) {
            return $this->curatedOpenRouterModels();
        }

        try {
            $response = Http::timeout(10)->get('https://openrouter.ai/api/v1/models');

            if ($response->successful()) {
                $data = $response->json('data') ?? [];
                $popularVendors = ['openai', 'google', 'anthropic', 'meta-llama', 'mistralai', 'qwen', 'deepseek'];
                $models = [];
                $otherVision = [];

                foreach ($data as $m) {
                    $id = (string) ($m['id'] ?? '');
                    if ($id === '') {
                        continue;
                    }

                    $name = (string) ($m['name'] ?? $id);
                    $inputMods = (array) ($m['architecture']['input_modalities'] ?? []);
                    $isVision = in_array('image', $inputMods, true) || str_contains((string) ($m['architecture']['modality'] ?? ''), 'image');
                    $vendor = explode('/', $id)[0] ?? '';

                    $label = $name.($isVision ? ' [Vision]' : '');

                    if (in_array($vendor, $popularVendors, true)) {
                        $models[$id] = $label;
                    } elseif ($isVision) {
                        $otherVision[$id] = $label;
                    }
                }

                // Öncelik: En popüler modeller, ardından diğer vision modeller
                $result = array_merge($models, array_slice($otherVision, 0, 30));

                // Temel varsayılan modeli her zaman en başta garantile
                if (! isset($result['openai/gpt-4o-mini'])) {
                    $result = ['openai/gpt-4o-mini' => 'OpenAI: GPT-4o Mini [Vision] (Varsayılan)'] + $result;
                }

                return $result;
            }
        } catch (Throwable $e) {
            Log::warning('AiModelRegistry: OpenRouter modelleri çekilemedi, yedeğe dönülüyor', ['error' => $e->getMessage()]);
        }

        return $this->curatedOpenRouterModels();
    }

    /**
     * NVIDIA NIM modellerini çeker veya bilinen kararlı modelleri döner.
     *
     * NOT: NVIDIA /v1/models endpoint'i herkese açıktır (auth gerekmez).
     * Chat/Vision dışı modeller (embedding, guard, reward, translate vb.) filtrelenir.
     *
     * @return array<string, string>
     */
    private function fetchNvidiaModels(): array
    {
        if (app()->runningUnitTests()) {
            return $this->curatedNvidiaModels();
        }

        try {
            // NVIDIA /v1/models endpoint'i PUBLIC — API anahtarı gerekmez
            $response = Http::timeout(10)
                ->get('https://integrate.api.nvidia.com/v1/models');

            if ($response->successful()) {
                $data = $response->json('data') ?? [];

                // Chat/completion dışı modelleri filtrele
                $excludePatterns = [
                    'embed', 'clip', 'reward', 'starcoder', 'ranking', 'parse',
                    'detector', 'calibration', 'guard', 'safety', 'translate',
                    'rerank', 'nemo-retriever', 'parakeet', 'canary', 'fastpitch',
                    'riva', 'audio', 'whisper', 'speech', 'tts',
                ];

                $vision = [];
                $text = [];

                foreach ($data as $m) {
                    $id = (string) ($m['id'] ?? '');
                    if ($id === '') {
                        continue;
                    }

                    // Filtrelenecek model türlerini atla
                    $skip = false;
                    foreach ($excludePatterns as $pattern) {
                        if (stripos($id, $pattern) !== false) {
                            $skip = true;

                            break;
                        }
                    }
                    if ($skip) {
                        continue;
                    }

                    $label = $this->nvidiaModelLabel($id);
                    $isVision = stripos($id, 'vision') !== false
                        || stripos($id, '-vl') !== false
                        || stripos($id, 'pixtral') !== false;

                    if ($isVision) {
                        $vision[$id] = $label.' [Vision]';
                    } else {
                        $text[$id] = $label;
                    }
                }

                // Vision modelleri önce, ardından metin modelleri
                $result = array_merge($vision, $text);

                if ($result !== []) {
                    return $result;
                }
            }
        } catch (Throwable $e) {
            Log::warning('AiModelRegistry: NVIDIA modelleri API ile çekilemedi', ['error' => $e->getMessage()]);
        }

        return $this->curatedNvidiaModels();
    }

    /**
     * NVIDIA model ID'sinden okunabilir Türkçe etiket üretir.
     */
    private function nvidiaModelLabel(string $id): string
    {
        // vendor/model-name → güzel etiket
        $parts = explode('/', $id);
        $vendor = $parts[0] ?? '';
        $modelName = $parts[1] ?? $id;

        // Vendor eşlemesi
        $vendorMap = [
            'meta' => 'Meta',
            'nvidia' => 'NVIDIA',
            'mistralai' => 'Mistral',
            'google' => 'Google',
            'deepseek-ai' => 'DeepSeek',
            'qwen' => 'Qwen',
            'microsoft' => 'Microsoft',
            'ibm' => 'IBM',
            'writer' => 'Writer',
            'abacusai' => 'Abacus AI',
            'nv-mistralai' => 'NVIDIA Mistral',
            'rakuten' => 'Rakuten',
            'upstage' => 'Upstage',
            'snowflake' => 'Snowflake',
            'baichuan-inc' => 'Baichuan',
            'thudm' => 'Tsinghua',
            'aisingapore' => 'AI Singapore',
            'yentinglin' => 'Taiwan LLM',
            'mediatek' => 'MediaTek',
            'tokyotech-llm' => 'Tokyo Tech',
            'institute-of-science-tokyo' => 'IST Tokyo',
            'databricks' => 'Databricks',
        ];

        $vendorLabel = $vendorMap[$vendor] ?? ucfirst($vendor);

        // Model adını okunabilir hale getir
        $readable = str_replace(['-', '_'], ' ', $modelName);
        $readable = ucwords($readable);

        // Önerilen modelleri işaretle
        $onerilen = str_contains($id, 'nemotron') || str_contains($id, 'llama-3.2-11b-vision');
        $suffix = $onerilen ? ' [Önerilen]' : '';

        return "{$vendorLabel}: {$readable}{$suffix}";
    }

    /**
     * Groq modellerini çeker veya bilinen modelleri döner.
     *
     * @return array<string, string>
     */
    private function fetchGroqModels(): array
    {
        if (app()->runningUnitTests()) {
            return $this->curatedGroqModels();
        }

        $key = config('ai.providers.groq.api_key');
        if (filled($key)) {
            try {
                $response = Http::withToken($key)
                    ->timeout(10)
                    ->get('https://api.groq.com/openai/v1/models');

                if ($response->successful()) {
                    $list = [];
                    foreach ($response->json('data') ?? [] as $m) {
                        $id = (string) ($m['id'] ?? '');
                        if ($id !== '' && ($m['active'] ?? true)) {
                            $isVision = str_contains($id, 'vision');
                            $list[$id] = $id.($isVision ? ' [Vision, Ultra Hızlı]' : ' [Ultra Hızlı]');
                        }
                    }
                    if ($list !== []) {
                        return $list;
                    }
                }
            } catch (Throwable $e) {
                Log::warning('AiModelRegistry: Groq modelleri API ile çekilemedi', ['error' => $e->getMessage()]);
            }
        }

        return $this->curatedGroqModels();
    }

    /** @return array<string, string> */
    private function curatedNvidiaModels(): array
    {
        return [
            // Vision modelleri (fotoğraflı ilan analizi için)
            'meta/llama-3.2-11b-vision-instruct' => 'Meta: Llama 3.2 11B Vision Instruct [Vision, Önerilen]',
            'meta/llama-3.2-90b-vision-instruct' => 'Meta: Llama 3.2 90B Vision Instruct [Vision]',
            'google/gemma-3-27b-it' => 'Google: Gemma 3 27B IT',
            'mistralai/mistral-large-2-instruct' => 'Mistral: Mistral Large 2 Instruct',

            // Nemotron serisi (NVIDIA'nın kendi modelleri)
            'nvidia/llama-3.1-nemotron-70b-instruct' => 'NVIDIA: Llama 3.1 Nemotron 70B Instruct [Önerilen]',
            'nvidia/nemotron-4-340b-instruct' => 'NVIDIA: Nemotron 4 340B Instruct',
            'nvidia/llama-3.3-nemotron-super-49b-v1' => 'NVIDIA: Llama 3.3 Nemotron Super 49B',

            // Açık kaynak güçlü modeller
            'deepseek-ai/deepseek-r1' => 'DeepSeek: R1 (Derin Akıl Yürütme)',
            'qwen/qwen2.5-72b-instruct' => 'Qwen: Qwen 2.5 72B Instruct',
            'meta/llama-3.3-70b-instruct' => 'Meta: Llama 3.3 70B Instruct',
            'meta/llama-3.1-405b-instruct' => 'Meta: Llama 3.1 405B Instruct (En Büyük)',

            // Hızlı / hafif modeller
            'microsoft/phi-4' => 'Microsoft: Phi 4 (Kompakt, Hızlı)',
            'mistralai/mistral-small-24b-instruct-2501' => 'Mistral: Mistral Small 24B Instruct',
            'ibm/granite-3.1-8b-instruct' => 'IBM: Granite 3.1 8B Instruct (Kompakt)',
            'writer/palmyra-fin-70b-32k' => 'Writer: Palmyra Fin 70B (Finans)',
        ];
    }

    /** @return array<string, string> */
    private function curatedGroqModels(): array
    {
        return [
            'llama-3.2-11b-vision-preview' => 'Llama 3.2 11B Vision Preview [Vision, Ultra Hızlı, Önerilen]',
            'llama-3.2-90b-vision-preview' => 'Llama 3.2 90B Vision Preview [Vision, Ultra Hızlı]',
            'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile [Ultra Hızlı]',
            'llama-3.1-8b-instant' => 'Llama 3.1 8B Instant [En Yüksek Hız]',
            'mixtral-8x7b-32768' => 'Mixtral 8x7B (32k bağlam)',
        ];
    }

    /**
     * Mistral AI modelleri.
     *
     * @return array<string, string>
     */
    private function fetchMistralModels(): array
    {
        return [
            'pixtral-12b-2409' => 'Pixtral 12B [Vision, Önerilen]',
            'mistral-large-latest' => 'Mistral Large Latest',
            'mistral-small-latest' => 'Mistral Small Latest',
            'open-mistral-nemo' => 'Mistral NeMo 12B',
            'codestral-latest' => 'Codestral Latest',
        ];
    }

    /**
     * DeepSeek modelleri.
     *
     * @return array<string, string>
     */
    private function deepseekModels(): array
    {
        return [
            'deepseek-chat' => 'DeepSeek-V3 Chat (Yüksek Başarım, Ekonomik, Önerilen)',
            'deepseek-reasoner' => 'DeepSeek-R1 Reasoner (Derin Akıl Yürütme)',
        ];
    }

    /**
     * OpenAI modelleri.
     *
     * @return array<string, string>
     */
    private function openaiModels(): array
    {
        return [
            'gpt-4o-mini' => 'GPT-4o Mini [Vision, Hızlı, Önerilen]',
            'gpt-4o' => 'GPT-4o [Vision, En Yetenekli]',
            'o3-mini' => 'o3 Mini (Akıl Yürütme)',
            'gpt-4-turbo' => 'GPT-4 Turbo [Vision]',
        ];
    }

    /**
     * Anthropic modelleri.
     *
     * @return array<string, string>
     */
    private function anthropicModels(): array
    {
        return [
            'claude-haiku-4-5' => 'Claude Haiku 4.5 [Vision, Önerilen]',
            'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku [Vision, Hızlı]',
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet [Vision, Üstün Muhakeme]',
            'claude-3-opus-20240229' => 'Claude 3 Opus [Vision]',
        ];
    }

    /**
     * Google Gemini modelleri.
     *
     * @return array<string, string>
     */
    private function geminiModels(): array
    {
        return [
            'gemini-2.0-flash' => 'Gemini 2.0 Flash [Vision, Çok Hızlı, Önerilen]',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash [Vision]',
            'gemini-1.5-pro' => 'Gemini 1.5 Pro [Vision, 2M Bağlam]',
        ];
    }

    /**
     * OpenRouter bağlantı koptuğunda kullanılan yedek kararlı model kataloğu.
     *
     * @return array<string, string>
     */
    private function curatedOpenRouterModels(): array
    {
        return [
            // Ücretsiz Modeller (Free Tier — $0 Maliyet)
            'meta-llama/llama-3.2-11b-vision-instruct:free' => 'Meta Llama 3.2 11B Vision [Ücretsiz / Free, Vision]',
            'google/gemini-2.0-flash-exp:free' => 'Google Gemini 2.0 Flash Exp [Ücretsiz / Free, Vision]',
            'deepseek/deepseek-r1:free' => 'DeepSeek R1 [Ücretsiz / Free, Muhakeme]',
            'qwen/qwen-2.5-vl-72b-instruct:free' => 'Qwen 2.5 VL 72B [Ücretsiz / Free, Vision]',
            'inclusionai/ling-3.0-flash-vl:free' => 'inclusionAI: Ling 3.0 Flash VL [Ücretsiz / Free, Vision]',

            // Standart & Önerilen Modeller
            'openai/gpt-4o-mini' => 'OpenAI: GPT-4o Mini [Vision, Önerilen]',
            'google/gemini-2.0-flash-001' => 'Google: Gemini 2.0 Flash [Vision, Çok Hızlı]',
            'anthropic/claude-3-5-haiku' => 'Anthropic: Claude 3.5 Haiku [Vision]',
            'openai/gpt-4o' => 'OpenAI: GPT-4o [Vision]',
            'anthropic/claude-3.5-sonnet' => 'Anthropic: Claude 3.5 Sonnet [Vision]',
            'meta-llama/llama-3.2-11b-vision-instruct' => 'Meta Llama 3.2 11B Vision [Vision, Açık Kaynak]',
            'deepseek/deepseek-chat' => 'DeepSeek: DeepSeek-V3 Chat',
            'mistralai/pixtral-12b' => 'Mistral: Pixtral 12B [Vision]',
            'qwen/qwen-2.5-72b-instruct' => 'Qwen: Qwen 2.5 72B Instruct',
        ];
    }

    /**
     * Belirtilen sağlayıcı ve modelin anlık sağlık durumunu test eder.
     *
     * @return array{ok: bool, latency_ms: int, message: string}
     */
    public function checkHealth(string $provider, ?string $model = null): array
    {
        $start = microtime(true);

        try {
            $config = config("ai.providers.{$provider}", []);
            if ($model) {
                $config['model'] = $model;
            }

            /** @var AiProvider $instance */
            $instance = app(AiManager::class)->make($provider, $config);

            if (! $instance->isConfigured()) {
                return [
                    'ok' => false,
                    'latency_ms' => 0,
                    'message' => 'API anahtarı girilmemiş.',
                ];
            }

            $res = $instance->analyzeText(
                'Bu bir sağlık kontrolüdür. Sadece şu JSON nesnesini döndür: {"ok": true}',
                null,
                15
            );

            $latency = (int) round((microtime(true) - $start) * 1000);

            if ($res !== null) {
                return [
                    'ok' => true,
                    'latency_ms' => $latency,
                    'message' => "Bağlantı sağlıklı ({$latency}ms).",
                ];
            }

            $error = $instance->lastError() ?? 'Sağlayıcı yanıt vermedi.';

            return [
                'ok' => false,
                'latency_ms' => $latency,
                'message' => $error,
            ];
        } catch (Throwable $e) {
            $latency = (int) round((microtime(true) - $start) * 1000);

            return [
                'ok' => false,
                'latency_ms' => $latency,
                'message' => 'Hata: '.$e->getMessage(),
            ];
        }
    }
}
