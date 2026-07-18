<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Services\Ai\Concerns\TracksLastError;
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
    use TracksLastError;

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

    public function analyzeImage(string $base64Image, string $mediaType, string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
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
            ->timeout($timeoutSeconds ?? 30)
            ->post($endpoint, $body);

        if (! $response->successful()) {
            $detail = $response->json('error.message') ?? mb_substr($response->body(), 0, 200);
            $this->lastError = 'HTTP '.$response->status().': '.$detail;
            Log::error('AI: Gemini yanıtı başarısız', ['status' => $response->status(), 'detail' => $detail]);

            return null;
        }

        $candidate = $response->json('candidates.0') ?? [];

        // Güvenlik nedeniyle engellendiyse (SAFETY/PROHIBITED_CONTENT) içerik yok.
        if (in_array($candidate['finishReason'] ?? null, ['SAFETY', 'PROHIBITED_CONTENT', 'BLOCKLIST'], true)) {
            $this->lastError = 'İçerik güvenlik filtresine takıldı.';

            return null;
        }

        $decoded = AiJson::decode($candidate['content']['parts'][0]['text'] ?? null);
        if ($decoded === null) {
            $this->lastError = 'Yanıt geçerli JSON değil.';
        }

        return $decoded;
    }
}
