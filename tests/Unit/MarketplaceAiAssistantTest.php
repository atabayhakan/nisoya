<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\AiProvider;
use App\Services\Ai\MarketplaceAiAssistant;
use App\Services\DolandiricilikTespiti;
use Mockery;
use Tests\TestCase;

class MarketplaceAiAssistantTest extends TestCase
{
    private function assistantOlustur(?AiProvider $mockAi = null): MarketplaceAiAssistant
    {
        if (! $mockAi) {
            $mockAi = Mockery::mock(AiProvider::class);
            $mockAi->shouldReceive('isConfigured')->andReturn(false);
        }

        $fraudDetector = app(DolandiricilikTespiti::class);

        return new MarketplaceAiAssistant($mockAi, $fraudDetector);
    }

    public function test_improve_listing_formats_and_enhances_content(): void
    {
        $assistant = $this->assistantOlustur();
        $sonuc = $assistant->improveListing('satilik bosch camasir makinesi', 'az kullanildi calisiyor', 'Beyaz Eşya', 'Berlin');

        $this->assertArrayHasKey('title', $sonuc);
        $this->assertArrayHasKey('description', $sonuc);
        $this->assertStringContainsString('Berlin', $sonuc['title']);
        $this->assertStringContainsString('📌 Öne Çıkan Detaylar', $sonuc['description']);
    }

    public function test_evaluate_listing_detects_quality_and_risks(): void
    {
        $assistant = $this->assistantOlustur();

        // 1. Temiz ilan
        $temiz = $assistant->evaluateListing(
            'Berlin Merkezde 2+1 Eşyalı Daire',
            'Dairemiz metroya 3 dakika mesafede olup tamamen yenilenmiştir. Düzenli geliri olan kiracılar tercih edilir.',
            1200.0,
            'Berlin'
        );

        $this->assertGreaterThanOrEqual(70, $temiz['score']);
        $this->assertEquals('onayla', $temiz['recommendation']);

        // 2. Riskli/dolandırıcılık içeren ilan
        $riskli = $assistant->evaluateListing(
            'Acil Ucuza iPhone 15 Pro',
            'Nisoya güvencesi ile kapora gönderin hemen kargolayalım.',
            300.0,
            'Köln'
        );

        $this->assertLessThan(60, $riskli['score']);
        $this->assertNotEmpty($riskli['risks']);
    }

    public function test_suggest_category_emoji_returns_appropriate_emoji(): void
    {
        $assistant = $this->assistantOlustur();

        $this->assertEquals('🚗', $assistant->suggestCategoryEmoji('Otomobil & Vasıta'));
        $this->assertEquals('🏠', $assistant->suggestCategoryEmoji('Emlak & Konut'));
        $this->assertEquals('📱', $assistant->suggestCategoryEmoji('Elektronik Eşya'));
        $this->assertEquals('💼', $assistant->suggestCategoryEmoji('Hizmet & Usta'));
        $this->assertEquals('🏷️', $assistant->suggestCategoryEmoji('Bilinmeyen Kategori'));
    }

    public function test_analyze_dispute_generates_mediation_summary(): void
    {
        $assistant = $this->assistantOlustur();
        $analiz = $assistant->analyzeDispute(
            'Ürün kargoda kırık geldi ve satıcı iadeyi kabul etmiyor.',
            'Ahmet Yılmaz',
            'Mehmet Kaya',
            'Vintage Porselen Çay Takımı',
            '85 EUR'
        );

        $this->assertArrayHasKey('summary', $analiz);
        $this->assertArrayHasKey('recommendation', $analiz);
        $this->assertStringContainsString('Ahmet Yılmaz', $analiz['summary']);
        $this->assertStringContainsString('Mehmet Kaya', $analiz['summary']);
    }
}
