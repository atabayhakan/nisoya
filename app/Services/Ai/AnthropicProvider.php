<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Services\Ai\Concerns\TracksLastError;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Anthropic (Claude) Messages API sağlayıcısı. Görüntü destekli, structured
 * output (output_config.format) ile en güvenilir şema zorlaması sunar.
 */
class AnthropicProvider implements AiProvider
{
    use TracksLastError;

    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';

    private const API_VERSION = '2023-06-01';

    /** @param  array<string, mixed>  $config */
    public function __construct(private readonly array $config) {}

    public function isConfigured(): bool
    {
        return filled($this->config['api_key'] ?? null);
    }

    public function name(): string
    {
        return 'Anthropic (Claude)';
    }

    public function analyzeImage(string $base64Image, string $mediaType, string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
    {
        $body = [
            'model' => $this->config['model'],
            'max_tokens' => 1024,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'image',
                        'source' => ['type' => 'base64', 'media_type' => $mediaType, 'data' => $base64Image],
                    ],
                    ['type' => 'text', 'text' => $prompt],
                ],
            ]],
        ];

        if ($jsonSchema) {
            $body['output_config'] = ['format' => ['type' => 'json_schema', 'schema' => $jsonSchema]];
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->config['api_key'],
            'anthropic-version' => self::API_VERSION,
            'content-type' => 'application/json',
        ])->timeout($timeoutSeconds ?? 30)->post(self::ENDPOINT, $body);

        if (! $response->successful()) {
            $detail = $response->json('error.message') ?? mb_substr($response->body(), 0, 200);
            $this->lastError = 'HTTP '.$response->status().': '.$detail;
            Log::error('AI: Anthropic yanıtı başarısız', ['status' => $response->status(), 'detail' => $detail]);

            return null;
        }

        $json = $response->json();

        // Güvenlik sınıflandırıcısı reddettiyse içerik yok.
        if (($json['stop_reason'] ?? null) === 'refusal') {
            $this->lastError = 'İçerik güvenlik filtresine takıldı.';

            return null;
        }

        $text = collect($json['content'] ?? [])->firstWhere('type', 'text')['text'] ?? null;

        $decoded = AiJson::decode($text);
        if ($decoded === null) {
            $this->lastError = 'Yanıt geçerli JSON değil.';
        }

        return $decoded;
    }
}
