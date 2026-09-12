<?php

namespace App\Services\Ai;

/**
 * NVIDIA NIM (Inference Microservices) sağlayıcısı — build.nvidia.com /
 * integrate.api.nvidia.com. OpenAI-uyumlu Chat Completions API'sini kullanır.
 *
 * Meta Llama 3.2 Vision, Nemotron, Mistral vb. modelleri çok yüksek hızda
 * ve kurumsal düzeyde sunar.
 */
class NvidiaProvider extends OpenAiProvider
{
    public function name(): string
    {
        return 'NVIDIA NIM';
    }

    /**
     * NVIDIA NIM modelleri standart json_object modunu kullanır.
     */
    protected function responseFormat(?array $jsonSchema): array
    {
        return ['type' => 'json_object'];
    }
}
