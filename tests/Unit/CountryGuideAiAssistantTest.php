<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\AiProvider;
use App\Services\Ai\CountryGuideAiAssistant;
use Mockery;
use Tests\TestCase;

class CountryGuideAiAssistantTest extends TestCase
{
    private function assistantOlustur(?AiProvider $mockAi = null): CountryGuideAiAssistant
    {
        if (! $mockAi) {
            $mockAi = Mockery::mock(AiProvider::class);
            $mockAi->shouldReceive('isConfigured')->andReturn(false);
        }

        return new CountryGuideAiAssistant($mockAi);
    }

    public function test_generate_consular_content_returns_fallback_structure(): void
    {
        $assistant = $this->assistantOlustur();
        $sonuc = $assistant->generateConsularContent('Berlin Başkonsolosluğu', 'Pasaport Yenileme', 'DE');

        $this->assertArrayHasKey('evraklar', $sonuc);
        $this->assertArrayHasKey('sure_metni', $sonuc);
        $this->assertArrayHasKey('ucret_metni', $sonuc);
        $this->assertArrayHasKey('notlar', $sonuc);
        $this->assertArrayHasKey('resmi_kaynak_url', $sonuc);

        $this->assertNotEmpty($sonuc['evraklar']);
        $this->assertArrayHasKey('ad', $sonuc['evraklar'][0]);
        $this->assertStringContainsString('konsolosluk', $sonuc['resmi_kaynak_url']);
    }

    public function test_evaluate_consular_feedback_detects_priority(): void
    {
        $assistant = $this->assistantOlustur();

        // 1. Ücret ile ilgili acil geri bildirim
        $resAcil = $assistant->evaluateConsularFeedback('Pasaport harç ücreti 2026 tarifesinde 48 euro değil 52 euro oldu.', 'Pasaport');
        $this->assertEquals('yuksek', $resAcil['oncelik']);
        $this->assertEquals('gecerli', $resAcil['gecerlilik']);

        // 2. Kısa/belirsiz geri bildirim
        $resKisa = $assistant->evaluateConsularFeedback('Hata', 'Nüfus');
        $this->assertEquals('bilgi_yok', $resKisa['gecerlilik']);
    }

    public function test_generate_life_guide_content_returns_structured_blocks(): void
    {
        $assistant = $this->assistantOlustur();
        $sonuc = $assistant->generateLifeGuideContent('Banka Hesabı Açma', 'Bankacılık & Finans', 'DE');

        $this->assertArrayHasKey('icerik', $sonuc);
        $this->assertArrayHasKey('kaynak_aciklama', $sonuc);
        $this->assertArrayHasKey('kaynak_url', $sonuc);

        $this->assertNotEmpty($sonuc['icerik']);
        $ilkBlok = $sonuc['icerik'][0];
        $this->assertArrayHasKey('tip', $ilkBlok);
        $this->assertArrayHasKey('metin', $ilkBlok);
        $this->assertContains($ilkBlok['tip'], ['baslik', 'paragraf', 'madde']);
    }

    public function test_evaluate_life_topic_suggestion_and_emoji(): void
    {
        $assistant = $this->assistantOlustur();

        $oneriSonuc = $assistant->evaluateLifeTopicSuggestion('Almanya\'da N26 veya Revolut ile oturum kartı olmadan da ilk hesap açılabiliyor.', 'Banka Hesabı');
        $this->assertContains($oneriSonuc['karar'], ['onayla', 'duzenle']);
        $this->assertNotEmpty($oneriSonuc['onerilen_bloklar']);

        $this->assertEquals('🏦', $assistant->suggestEmoji('Banka Hesabı Açma'));
        $this->assertEquals('🏠', $assistant->suggestEmoji('Kiralık Ev Bulma'));
        $this->assertEquals('🛂', $assistant->suggestEmoji('Vize ve Pasaport'));
    }
}
