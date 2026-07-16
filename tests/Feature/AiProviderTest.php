<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Services\Ai\AiManager;
use App\Services\Ai\AnthropicProvider;
use App\Services\Ai\GeminiProvider;
use App\Services\Ai\OpenAiProvider;
use App\Services\Ai\OpenRouterProvider;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class AiProviderTest extends TestCase
{
    public function test_manager_resolves_configured_default_provider(): void
    {
        config(['ai.default' => 'openai']);

        $this->assertInstanceOf(OpenAiProvider::class, app(AiManager::class)->provider());
    }

    public function test_container_binds_aiprovider_to_configured_driver(): void
    {
        config(['ai.default' => 'gemini']);

        // Singleton önbelleğini temizle ki yeni config etkili olsun.
        $this->app->forgetInstance(AiManager::class);

        $this->assertInstanceOf(GeminiProvider::class, app(AiProvider::class));
    }

    public function test_manager_can_resolve_named_provider(): void
    {
        $manager = app(AiManager::class);

        $this->assertInstanceOf(AnthropicProvider::class, $manager->provider('anthropic'));
        $this->assertInstanceOf(OpenAiProvider::class, $manager->provider('openai'));
        $this->assertInstanceOf(OpenRouterProvider::class, $manager->provider('openrouter'));
        $this->assertInstanceOf(GeminiProvider::class, $manager->provider('gemini'));
    }

    public function test_manager_throws_on_unknown_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(AiManager::class)->provider('bilinmeyen-saglayici');
    }

    public function test_manager_allows_registering_new_provider(): void
    {
        $manager = app(AiManager::class);
        $manager->register('sahte', FakeAiProvider::class);

        $this->assertInstanceOf(FakeAiProvider::class, $manager->provider('sahte'));
    }

    public function test_provider_is_configured_reflects_api_key(): void
    {
        $this->assertFalse((new AnthropicProvider([]))->isConfigured());
        $this->assertTrue((new AnthropicProvider(['api_key' => 'k']))->isConfigured());
        $this->assertFalse((new OpenAiProvider(['api_key' => null]))->isConfigured());
        $this->assertTrue((new GeminiProvider(['api_key' => 'k']))->isConfigured());
    }

    public function test_anthropic_provider_calls_messages_api_and_parses(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response([
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => '{"x":1}']],
        ])]);

        $result = (new AnthropicProvider(['api_key' => 'k', 'model' => 'claude-haiku-4-5']))
            ->analyzeImage('BASE64', 'image/jpeg', 'prompt', ['type' => 'object']);

        $this->assertSame(['x' => 1], $result);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.anthropic.com')
            && $r->hasHeader('x-api-key', 'k'));
    }

    public function test_anthropic_provider_returns_null_on_refusal(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['stop_reason' => 'refusal', 'content' => []])]);

        $result = (new AnthropicProvider(['api_key' => 'k', 'model' => 'm']))
            ->analyzeImage('B', 'image/jpeg', 'p');

        $this->assertNull($result);
    }

    public function test_openai_provider_respects_base_url(): void
    {
        Http::fake(['proxy.example.com/*' => Http::response([
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"ok":true}']]],
        ])]);

        $result = (new OpenAiProvider([
            'api_key' => 'k',
            'model' => 'gpt-4o-mini',
            'base_url' => 'https://proxy.example.com/v1',
        ]))->analyzeImage('B', 'image/jpeg', 'p');

        $this->assertSame(['ok' => true], $result);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'proxy.example.com/v1/chat/completions'));
    }

    public function test_openrouter_provider_hits_openrouter_endpoint_with_headers(): void
    {
        Http::fake(['openrouter.ai/*' => Http::response([
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"r":42}']]],
        ])]);

        $result = (new OpenRouterProvider([
            'api_key' => 'or-key',
            'model' => 'openai/gpt-4o-mini',
            'base_url' => 'https://openrouter.ai/api/v1',
            'referer' => 'https://nisoya.com',
            'title' => 'Nisoya',
        ]))->analyzeImage('B', 'image/jpeg', 'p', ['type' => 'object']);

        $this->assertSame(['r' => 42], $result);
        Http::assertSent(function ($r) {
            // OpenRouter ucu + Bearer + referer/title başlıkları + json_object modu
            // (strict şema DEĞİL — çok-modelli uyumluluk).
            return str_contains($r->url(), 'openrouter.ai/api/v1/chat/completions')
                && $r->hasHeader('Authorization', 'Bearer or-key')
                && $r->hasHeader('HTTP-Referer', 'https://nisoya.com')
                && $r->hasHeader('X-Title', 'Nisoya')
                && data_get($r->data(), 'response_format.type') === 'json_object';
        });
    }

    public function test_gemini_provider_calls_generate_content(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => '{"g":9}']]]]],
        ])]);

        $result = (new GeminiProvider(['api_key' => 'k', 'model' => 'gemini-2.0-flash']))
            ->analyzeImage('B', 'image/jpeg', 'p');

        $this->assertSame(['g' => 9], $result);
        Http::assertSent(fn ($r) => str_contains($r->url(), ':generateContent')
            && $r->hasHeader('x-goog-api-key', 'k'));
    }

    public function test_gemini_provider_returns_null_when_blocked(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['finishReason' => 'SAFETY', 'content' => ['parts' => []]]],
        ])]);

        $result = (new GeminiProvider(['api_key' => 'k', 'model' => 'm']))
            ->analyzeImage('B', 'image/jpeg', 'p');

        $this->assertNull($result);
    }
}

/** Test için basit sahte sağlayıcı. */
class FakeAiProvider implements AiProvider
{
    public function __construct(array $config = []) {}

    public function isConfigured(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'Sahte';
    }

    public function analyzeImage(string $base64Image, string $mediaType, string $prompt, ?array $jsonSchema = null): ?array
    {
        return ['fake' => true];
    }
}
