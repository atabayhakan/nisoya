<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Services\Ai\Concerns\TracksLastError;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI (ve OpenAI-uyumlu: Azure OpenAI, OpenRouter, yerel sunucular)
 * Chat Completions sağlayıcısı. base_url ile uç değiştirilebilir.
 */
class OpenAiProvider implements AiProvider
{
    use TracksLastError;

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

    public function analyzeImage(string $base64Image, string $mediaType, string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
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
            ->timeout($timeoutSeconds ?? 30)
            ->post($endpoint, $body);

        if (! $response->successful()) {
            // OpenAI/OpenRouter hata gövdesi genelde {"error":{"message":...}}.
            $detail = $response->json('error.message') ?? mb_substr($response->body(), 0, 200);
            $this->lastError = 'HTTP '.$response->status().': '.$detail;
            Log::error('AI: '.$this->name().' yanıtı başarısız', ['status' => $response->status(), 'detail' => $detail]);

            return null;
        }

        $choice = $response->json('choices.0') ?? [];

        // İçerik filtresi tetiklendiyse güvenli değil.
        if (($choice['finish_reason'] ?? null) === 'content_filter') {
            $this->lastError = 'İçerik güvenlik filtresine takıldı.';

            return null;
        }

        $decoded = AiJson::decode($choice['message']['content'] ?? null);
        if ($decoded === null) {
            $this->lastError = 'Yanıt geçerli JSON değil (model JSON döndürmedi).';
        }

        return $decoded;
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
