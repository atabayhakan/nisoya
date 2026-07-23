<?php

namespace Tests\Unit\Growth;

use App\Contracts\AiProvider;
use App\Services\Growth\BusinessSignal;
use App\Services\Growth\DetectionResult;
use App\Services\Growth\TurkishBusinessDetector;
use PHPUnit\Framework\TestCase;

class TurkishBusinessDetectorTest extends TestCase
{
    private function detector(?AiProvider $ai = null): TurkishBusinessDetector
    {
        return new TurkishBusinessDetector($ai ?? new FakeTextAi);
    }

    public function test_strong_cuisine_marker_is_turkish(): void
    {
        $r = $this->detector()->screen(new BusinessSignal('Anadolu Kebap House', 'Restaurant', country: 'US'));

        $this->assertTrue($r->isTurkish);
        $this->assertSame(DetectionResult::BAND_TURKISH, $r->band);
        $this->assertSame('deterministic', $r->method);
    }

    public function test_turkish_owner_name_is_turkish(): void
    {
        $r = $this->detector()->screen(new BusinessSignal('City Auto Repair', 'Auto repair', 'Mehmet Yılmaz', 'US'));

        $this->assertTrue($r->isTurkish);
        $this->assertSame(DetectionResult::BAND_TURKISH, $r->band);
    }

    public function test_self_identification_is_turkish(): void
    {
        $r = $this->detector()->screen(new BusinessSignal('Delight Restaurant', 'Turkish restaurant', country: 'TH'));

        $this->assertTrue($r->isTurkish);
    }

    public function test_unrelated_business_is_not_turkish(): void
    {
        $r = $this->detector()->screen(new BusinessSignal('Almaty Electric Solutions', 'Electrician', country: 'KZ'));

        $this->assertFalse($r->isTurkish);
        $this->assertSame(DetectionResult::BAND_NOT, $r->band);
        $this->assertFalse($r->needsHumanReview());
    }

    public function test_lone_surname_is_ambiguous_and_needs_review(): void
    {
        // "Kaya" hem Türk soyadı hem Japon kelimesi — tek sinyal, sınırda.
        $r = $this->detector()->screen(new BusinessSignal('Kaya Sushi Bar', 'Sushi restaurant', country: 'US'));

        $this->assertSame(DetectionResult::BAND_AMBIGUOUS, $r->band);
        $this->assertTrue($r->needsHumanReview());
    }

    public function test_ambiguous_routes_to_llm_when_configured(): void
    {
        $ai = new FakeTextAi(configured: true, textResult: [
            'turk_mu' => true,
            'guven' => 0.9,
            'sinyaller' => ['sahibi Türk'],
            'gerekce' => 'Sahip adı ve menü Türk.',
        ]);

        $r = $this->detector($ai)->detect(new BusinessSignal('Kaya Sushi Bar', 'Sushi restaurant', country: 'US'));

        $this->assertSame('llm', $r->method);
        $this->assertTrue($r->isTurkish);
        $this->assertSame(DetectionResult::BAND_TURKISH, $r->band);
        $this->assertContains('sahibi Türk', $r->signals);
    }

    public function test_ambiguous_stays_when_llm_unconfigured(): void
    {
        // AI yapılandırılmamışsa sınırda karar körü körüne değişmez — insan onayına.
        $r = $this->detector()->detect(new BusinessSignal('Kaya Sushi Bar', 'Sushi restaurant', country: 'US'));

        $this->assertSame('deterministic', $r->method);
        $this->assertSame(DetectionResult::BAND_AMBIGUOUS, $r->band);
        $this->assertTrue($r->needsHumanReview());
    }

    public function test_low_llm_confidence_falls_back_to_ambiguous(): void
    {
        $ai = new FakeTextAi(configured: true, textResult: [
            'turk_mu' => true,
            'guven' => 0.4,
            'gerekce' => 'Emin değilim.',
        ]);

        $r = $this->detector($ai)->detect(new BusinessSignal('Istanbul Cafe', 'Cafe', country: 'TH'));

        $this->assertSame(DetectionResult::BAND_AMBIGUOUS, $r->band);
        $this->assertTrue($r->needsHumanReview());
    }
}

/** Testler için yapılandırılabilir sahte AI sağlayıcısı. */
final class FakeTextAi implements AiProvider
{
    /** @param  array<string, mixed>|null  $textResult */
    public function __construct(
        private bool $configured = false,
        private ?array $textResult = null,
    ) {}

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function name(): string
    {
        return 'Sahte';
    }

    public function lastError(): ?string
    {
        return null;
    }

    public function analyzeImage(string $base64Image, string $mediaType, string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
    {
        return null;
    }

    public function analyzeText(string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
    {
        return $this->textResult;
    }
}
