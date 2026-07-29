<?php

namespace Tests\Support;

use App\Contracts\AiProvider;

/**
 * SADECE TEST İÇİN: kararı önceden yazılmış bir yapay zekâ sağlayıcısı.
 *
 * Sohbet testleri GERÇEK modele bağlanamaz — testler ağa çıkmaz, çıksa da
 * modelin cevabı deterministik değildir. Burada test, modelin NE diyeceğini
 * kendisi yazar ve sistemin o karara NE YAPTIĞINI sınar. Sınanan şey modelin
 * zekâsı değil, kararın etrafındaki boru hattı: katalog sınırı, doğrulama,
 * risk kapısı, defter.
 */
class SahteAiSaglayici implements AiProvider
{
    /** @param array<string, mixed>|null $yanit null = "sağlayıcı çöktü" benzetimi */
    public function __construct(
        public ?array $yanit = null,
        public ?string $sonHata = null,
    ) {}

    /** Testin okuyabilmesi için: modele giden son yönerge. */
    public ?string $sonYonerge = null;

    public function isConfigured(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'sahte';
    }

    public function lastError(): ?string
    {
        return $this->sonHata;
    }

    public function analyzeImage(string $base64Image, string $mediaType, string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
    {
        return $this->yanit;
    }

    public function analyzeText(string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
    {
        $this->sonYonerge = $prompt;

        return $this->yanit;
    }
}
