<?php

namespace Tests\Unit;

use App\Contracts\AiProvider;
use App\Services\Ai\CmsAiAssistant;
use PHPUnit\Framework\TestCase;

class CmsAiAssistantTest extends TestCase
{
    public function test_hero_content_generation(): void
    {
        $mockAi = $this->createMock(AiProvider::class);
        $mockAi->method('isConfigured')->willReturn(true);
        $mockAi->method('analyzeText')->willReturn([
            'rozet' => '🌍 Avrupa Türkleri',
            'baslik' => 'Türkçe Konuşan Esnaf',
            'vurgu' => 'Tek Tıkla Yanında.',
            'alt_baslik' => 'Binlerce ilan.',
            'cta1_etiket' => 'İlan Ver',
            'cta2_etiket' => 'Keşfet',
        ]);

        $assistant = new CmsAiAssistant($mockAi);
        $result = $assistant->generateHeroContent('Ramazan Kampanyası');

        $this->assertNotNull($result);
        $this->assertSame('Türkçe Konuşan Esnaf', $result['baslik']);
        $this->assertSame('Tek Tıkla Yanında.', $result['vurgu']);
    }

    public function test_announcement_generation(): void
    {
        $mockAi = $this->createMock(AiProvider::class);
        $mockAi->method('isConfigured')->willReturn(true);
        $mockAi->method('analyzeText')->willReturn([
            'metin' => 'Kısa süreli bakım duyurusu',
            'link_metni' => 'Detay',
            'renk' => 'uyari',
        ]);

        $assistant = new CmsAiAssistant($mockAi);
        $result = $assistant->generateAnnouncement('Bakım');

        $this->assertNotNull($result);
        $this->assertSame('Kısa süreli bakım duyurusu', $result['metin']);
        $this->assertSame('uyari', $result['renk']);
    }

    public function test_faq_answer_generation(): void
    {
        $mockAi = $this->createMock(AiProvider::class);
        $mockAi->method('isConfigured')->willReturn(true);
        $mockAi->method('analyzeText')->willReturn([
            'cevap' => 'Nisoya tamamen ücretsizdir.',
        ]);

        $assistant = new CmsAiAssistant($mockAi);
        $result = $assistant->generateFaqAnswer('İlan vermek ücretli mi?');

        $this->assertNotNull($result);
        $this->assertSame('Nisoya tamamen ücretsizdir.', $result['cevap']);
    }

    public function test_unconfigured_provider_returns_null(): void
    {
        $mockAi = $this->createMock(AiProvider::class);
        $mockAi->method('isConfigured')->willReturn(false);

        $assistant = new CmsAiAssistant($mockAi);
        $this->assertNull($assistant->generateHeroContent('test'));
        $this->assertNull($assistant->generateAnnouncement('test'));
        $this->assertNull($assistant->generateFaqAnswer('test'));
        $this->assertNull($assistant->generatePageSeo('test'));
        $this->assertNull($assistant->generateHighlight('test'));
    }
}
