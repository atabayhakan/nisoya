<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\AiManager;
use App\Services\Ai\AiModelRegistry;
use App\Services\Ai\DeepSeekProvider;
use App\Services\Ai\GroqProvider;
use App\Services\Ai\MistralProvider;
use App\Services\Ai\NvidiaProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderExpansionTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_resolves_all_expanded_providers(): void
    {
        $manager = app(AiManager::class);

        $nvidia = $manager->provider('nvidia');
        $this->assertInstanceOf(NvidiaProvider::class, $nvidia);
        $this->assertSame('NVIDIA NIM', $nvidia->name());

        $groq = $manager->provider('groq');
        $this->assertInstanceOf(GroqProvider::class, $groq);
        $this->assertSame('Groq', $groq->name());

        $deepseek = $manager->provider('deepseek');
        $this->assertInstanceOf(DeepSeekProvider::class, $deepseek);
        $this->assertSame('DeepSeek', $deepseek->name());

        $mistral = $manager->provider('mistral');
        $this->assertInstanceOf(MistralProvider::class, $mistral);
        $this->assertSame('Mistral AI', $mistral->name());
    }

    public function test_nvidia_provider_calls_endpoint_and_parses(): void
    {
        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response([
                'choices' => [
                    [
                        'finish_reason' => 'stop',
                        'message' => ['content' => '{"sonuc": "basarili"}'],
                    ],
                ],
            ]),
        ]);

        $provider = new NvidiaProvider([
            'api_key' => 'nvapi-test',
            'model' => 'meta/llama-3.2-11b-vision-instruct',
            'base_url' => 'https://integrate.api.nvidia.com/v1',
        ]);

        $result = $provider->analyzeText('Test prompt');

        $this->assertSame(['sonuc' => 'basarili'], $result);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'integrate.api.nvidia.com/v1/chat/completions')
            && $r->hasHeader('Authorization', 'Bearer nvapi-test'));
    }

    public function test_groq_provider_calls_endpoint_and_parses(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [
                    [
                        'finish_reason' => 'stop',
                        'message' => ['content' => '{"hiz": "cok_yuksek"}'],
                    ],
                ],
            ]),
        ]);

        $provider = new GroqProvider([
            'api_key' => 'gsk-test',
            'model' => 'llama-3.2-11b-vision-preview',
            'base_url' => 'https://api.groq.com/openai/v1',
        ]);

        $result = $provider->analyzeText('Groq test');

        $this->assertSame(['hiz' => 'cok_yuksek'], $result);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.groq.com/openai/v1/chat/completions')
            && $r->hasHeader('Authorization', 'Bearer gsk-test'));
    }

    public function test_deepseek_provider_calls_endpoint_and_parses(): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [
                    [
                        'finish_reason' => 'stop',
                        'message' => ['content' => '{"muhakeme": true}'],
                    ],
                ],
            ]),
        ]);

        $provider = new DeepSeekProvider([
            'api_key' => 'sk-deepseek',
            'model' => 'deepseek-chat',
            'base_url' => 'https://api.deepseek.com/v1',
        ]);

        $result = $provider->analyzeText('DeepSeek test');

        $this->assertSame(['muhakeme' => true], $result);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.deepseek.com/v1/chat/completions'));
    }

    public function test_mistral_provider_calls_endpoint_and_parses(): void
    {
        Http::fake([
            'api.mistral.ai/*' => Http::response([
                'choices' => [
                    [
                        'finish_reason' => 'stop',
                        'message' => ['content' => '{"dil": "tr"}'],
                    ],
                ],
            ]),
        ]);

        $provider = new MistralProvider([
            'api_key' => 'mistral-key',
            'model' => 'pixtral-12b-2409',
            'base_url' => 'https://api.mistral.ai/v1',
        ]);

        $result = $provider->analyzeText('Mistral test');

        $this->assertSame(['dil' => 'tr'], $result);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.mistral.ai/v1/chat/completions'));
    }

    public function test_ai_model_registry_caches_and_retrieves_models(): void
    {
        Cache::forget(AiModelRegistry::CACHE_PREFIX.'groq');

        $registry = app(AiModelRegistry::class);
        $models = $registry->getAvailableModels('groq');

        $this->assertNotEmpty($models);
        $this->assertArrayHasKey('llama-3.2-11b-vision-preview', $models);
        $this->assertTrue(Cache::has(AiModelRegistry::CACHE_PREFIX.'groq'));

        // Force refresh
        $refreshed = $registry->getAvailableModels('groq', forceRefresh: true);
        $this->assertSame($models, $refreshed);
    }

    public function test_ai_modelleri_denetle_command_runs_successfully(): void
    {
        $this->artisan('ai:modelleri-denetle')
            ->assertExitCode(0);
    }
}
