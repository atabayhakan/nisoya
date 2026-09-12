<?php

namespace App\Services\Ai;

/**
 * DeepSeek sağlayıcısı — api.deepseek.com. OpenAI-uyumlu Chat Completions API.
 *
 * DeepSeek-V3 ve DeepSeek-R1 modellerini uygun maliyet ve üstün muhakeme ile sunar.
 */
class DeepSeekProvider extends OpenAiProvider
{
    public function name(): string
    {
        return 'DeepSeek';
    }

    /**
     * DeepSeek json_object modunu destekler.
     */
    protected function responseFormat(?array $jsonSchema): array
    {
        return ['type' => 'json_object'];
    }
}
