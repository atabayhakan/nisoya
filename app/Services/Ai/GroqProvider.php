<?php

namespace App\Services\Ai;

/**
 * Groq LPU sağlayıcısı — api.groq.com. OpenAI-uyumlu Chat Completions API.
 *
 * Ultra yüksek çıkarım hızı (500-1000 tok/sn) ile Llama 3.2 Vision ve Llama 3.3
 * modellerini sunar.
 */
class GroqProvider extends OpenAiProvider
{
    public function name(): string
    {
        return 'Groq';
    }

    /**
     * Groq modelleri json_object modunu destekler.
     */
    protected function responseFormat(?array $jsonSchema): array
    {
        return ['type' => 'json_object'];
    }
}
