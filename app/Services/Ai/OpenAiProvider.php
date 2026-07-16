<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI (ve OpenAI-uyumlu: Azure OpenAI, OpenRouter, yerel sunucular)
 * Chat Completions sağlayıcısı. base_url ile uç değiştirilebilir.
 */
class OpenAiProvider implements AiProvider
{
    /** @param  array<string, mixed>  $config */
    public function __construct(protected readonly array $config) {}

    public function isConfigured(): bool
    {
        return filled($this->config['api_key'] ?? null);
    }

    public function name(): string
    {
        return 'OpenAI';
    }

    public function analyzeImage(string $base64Image, string $mediaType, string $prompt, ?array $jsonSchema = null): ?array
    {
        $body = [
            'model' => $this->config['model'],
            'max_tokens' => 1024,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'image_url', 'image_url' => ['url' => "data:{$mediaType};base64,{$base64Image}"]],
                ],
            ]],
            'response_format' => $this->responseFormat($jsonSchema),
        ];

        $endpoint = rtrim($this->config['base_url'] ?? 'https://api.openai.com/v1', '/').'/chat/completions';

        $response = Http::withToken($this->config['api_key'])
            ->withHeaders($this->extraHeaders())
            ->timeout(30)
            ->post($endpoint, $body);

        if (! $response->successful()) {
            Log::warning('AI: '.$this->name().' yanıtı başarısız', ['status' => $response->status()]);

            return null;
        }

        $choice = $response->json('choices.0') ?? [];

        // İçerik filtresi tetiklendiyse güvenli değil.
        if (($choice['finish_reason'] ?? null) === 'content_filter') {
            return null;
        }

        return AiJson::decode($choice['message']['content'] ?? null);
    }

    /**
     * Yanıt biçimi. Şema verilirse strict json_schema; yoksa geçerli-JSON modu.
     * Alt sınıflar (ör. OpenRouter) çok-modelli uyumluluk için override eder.
     *
     * @param  array<string, mixed>|null  $jsonSchema
     * @return array<string, mixed>
     */
    protected function responseFormat(?array $jsonSchema): array
    {
        return $jsonSchema
            ? ['type' => 'json_schema', 'json_schema' => ['name' => 'sonuc', 'strict' => true, 'schema' => $jsonSchema]]
            : ['type' => 'json_object'];
    }

    /**
     * İsteğe eklenecek ek başlıklar (alt sınıflar için — ör. OpenRouter
     * HTTP-Referer / X-Title).
     *
     * @return array<string, string>
     */
    protected function extraHeaders(): array
    {
        return [];
    }
}
