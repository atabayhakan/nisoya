<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini (generateContent) sağlayıcısı. Şema formatı diğerlerinden
 * farklı olduğundan (OpenAPI alt kümesi) burada şemayı zorlamak yerine
 * responseMimeType=application/json + prompt yönlendirmesi kullanılır;
 * çıktı sunucu tarafında zaten doğrulanır (bkz. ListingVisionService).
 */
class GeminiProvider implements AiProvider
{
    private const BASE = 'https://generativelanguage.googleapis.com/v1beta/models';

    /** @param  array<string, mixed>  $config */
    public function __construct(private readonly array $config) {}

    public function isConfigured(): bool
    {
        return filled($this->config['api_key'] ?? null);
    }

    public function name(): string
    {
        return 'Google Gemini';
    }

    public function analyzeImage(string $base64Image, string $mediaType, string $prompt, ?array $jsonSchema = null): ?array
    {
        $body = [
            'contents' => [[
                'parts' => [
                    ['inline_data' => ['mime_type' => $mediaType, 'data' => $base64Image]],
                    ['text' => $prompt],
                ],
            ]],
            'generationConfig' => ['responseMimeType' => 'application/json'],
        ];

        // API anahtarı header'da (URL query string'inde değil — sızıntı önlemi).
        $endpoint = self::BASE.'/'.$this->config['model'].':generateContent';

        $response = Http::withHeaders(['x-goog-api-key' => $this->config['api_key']])
            ->timeout(30)
            ->post($endpoint, $body);

        if (! $response->successful()) {
            Log::warning('AI: Gemini yanıtı başarısız', ['status' => $response->status()]);

            return null;
        }

        $candidate = $response->json('candidates.0') ?? [];

        // Güvenlik nedeniyle engellendiyse (SAFETY/PROHIBITED_CONTENT) içerik yok.
        if (in_array($candidate['finishReason'] ?? null, ['SAFETY', 'PROHIBITED_CONTENT', 'BLOCKLIST'], true)) {
            return null;
        }

        return AiJson::decode($candidate['content']['parts'][0]['text'] ?? null);
    }
}
