<?php

namespace App\Services\Ai;

/**
 * Mistral AI sağlayıcısı — api.mistral.ai. OpenAI-uyumlu Chat Completions API.
 *
 * Pixtral (görüntü/vision destekli) ve Mistral Large modellerini sunar.
 */
class MistralProvider extends OpenAiProvider
{
    public function name(): string
    {
        return 'Mistral AI';
    }

    /**
     * Mistral json_object modunu destekler.
     */
    protected function responseFormat(?array $jsonSchema): array
    {
        return ['type' => 'json_object'];
    }
}
