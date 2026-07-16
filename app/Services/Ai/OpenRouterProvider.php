<?php

namespace App\Services\Ai;

/**
 * OpenRouter sağlayıcısı — OpenAI-uyumlu tek uçtan yüzlerce modele erişim
 * (openai/*, google/*, anthropic/*, meta-llama/* ...). OpenAiProvider'ı
 * yeniden kullanır; yalnızca uç, başlıklar ve yanıt biçimi farklıdır.
 *
 * Model adları sağlayıcı önekli yazılır (ör. "openai/gpt-4o-mini",
 * "google/gemini-2.0-flash-001"). config/ai.php → providers.openrouter.
 */
class OpenRouterProvider extends OpenAiProvider
{
    public function name(): string
    {
        return 'OpenRouter';
    }

    /**
     * OpenRouter birçok farklı modele yönlendirir; hepsi strict json_schema
     * desteklemez. Evrensel json_object modu kullanılır — çıktı zaten sunucu
     * tarafında doğrulanıp eşlendiği için (bkz. ListingVisionService) şema
     * zorlaması şart değil.
     */
    protected function responseFormat(?array $jsonSchema): array
    {
        return ['type' => 'json_object'];
    }

    /**
     * OpenRouter'ın sıralama/istatistik için önerdiği (zorunlu olmayan)
     * başlıklar — uygulama URL'i ve adı.
     *
     * @return array<string, string>
     */
    protected function extraHeaders(): array
    {
        return array_filter([
            'HTTP-Referer' => $this->config['referer'] ?? null,
            'X-Title' => $this->config['title'] ?? null,
        ]);
    }
}
