<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use InvalidArgumentException;

/**
 * Yapılandırmaya göre AI sağlayıcısını çözer. Sisteme yeni AI özelliği
 * eklendiğinde sağlayıcıyı değiştirmek için tek gereken config/ai.php'deki
 * 'default' değerini (AI_PROVIDER env'i) değiştirmek.
 *
 * Yeni sağlayıcı kaydı: AiProvider'ı uygula + aşağıdaki $providers eşlemesine
 * bir satır ekle + config/ai.php'ye bir 'providers' bloğu ekle.
 */
class AiManager
{
    /** @var array<string, class-string<AiProvider>> */
    private array $providers = [
        'anthropic' => AnthropicProvider::class,
        'openai' => OpenAiProvider::class,
        'openrouter' => OpenRouterProvider::class,
        'gemini' => GeminiProvider::class,
    ];

    /** @var array<string, AiProvider> Çözülmüş sağlayıcı önbelleği */
    private array $resolved = [];

    /** Yapılandırılmış (veya adı verilen) sağlayıcıyı döndür. */
    public function provider(?string $name = null): AiProvider
    {
        $name ??= config('ai.default', 'anthropic');

        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        $class = $this->providers[$name]
            ?? throw new InvalidArgumentException("Bilinmeyen AI sağlayıcısı: [{$name}]. Kayıtlı: ".implode(', ', array_keys($this->providers)));

        return $this->resolved[$name] = new $class(config("ai.providers.{$name}", []));
    }

    /**
     * Verilen config ile bir sağlayıcı örneği kur (config'ten okumadan).
     * Admin panelinde "test et" gibi, henüz kaydedilmemiş değerleri denemek için.
     *
     * @param  array<string, mixed>  $config
     */
    public function make(string $name, array $config): AiProvider
    {
        $class = $this->providers[$name]
            ?? throw new InvalidArgumentException("Bilinmeyen AI sağlayıcısı: [{$name}].");

        return new $class($config);
    }

    /**
     * Çalışma zamanında yeni bir sağlayıcı kaydet (ör. paket/test).
     *
     * @param  class-string<AiProvider>  $class
     */
    public function register(string $name, string $class): void
    {
        $this->providers[$name] = $class;
        unset($this->resolved[$name]);
    }
}
