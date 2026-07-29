<?php

namespace Tests\Support;

use App\Contracts\AiProvider;
use App\Services\Ai\AiManager;

/**
 * SADECE TEST İÇİN: her isteğe aynı sahte sağlayıcıyı veren AiManager.
 *
 * `KahyaSohbeti` somut `AiManager` sınıfını enjekte ediyor; testte container'a
 * bu alt sınıf konur (`$this->app->instance(AiManager::class, ...)`) ve sohbet
 * motoru hiç ağa çıkmadan sınanır.
 */
class SahteAiYonetici extends AiManager
{
    public function __construct(private readonly SahteAiSaglayici $sahte) {}

    public function provider(?string $name = null): AiProvider
    {
        return $this->sahte;
    }

    public function make(string $name, array $config): AiProvider
    {
        return $this->sahte;
    }
}
