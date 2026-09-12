<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\AiProvider;
use App\Services\Ai\GrowthMarketingAiAssistant;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GrowthMarketingAiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_audit_seo_and_geo_fallback_puanlama_ve_eylem_onerileri(): void
    {
        $mockAi = Mockery::mock(AiProvider::class);
        $mockAi->shouldReceive('isConfigured')->andReturn(false);

        $assistant = new GrowthMarketingAiAssistant($mockAi);

        $settings = [
            'default_title' => 'Nisoya — Yurtdışındaki Türkler İçin Ücretsiz İlan & Esnaf Rehberi',
            'default_description' => 'Almanya ve Avrupa genelinde Türk esnaf, dükkan ve ilan platformu. Komisyonsuz alışveriş yapın.',
            'og_image' => 'seo/og_test.webp',
            'robots_index' => true,
        ];

        $stats = [
            'total_listings' => 150,
            'total_categories' => 12,
            'total_countries' => 7,
            'has_llms_txt' => true,
        ];

        $result = $assistant->auditSeoAndGeo($settings, $stats);

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(70, $result['score']);
        $this->assertNotEmpty($result['strengths']);
        $this->assertNotEmpty($result['geo_readiness']);
        $this->assertStringContainsString('Nisoya', $result['geo_readiness']);
    }

    public function test_audit_seo_and_geo_noindex_ve_eksik_og_durumunda_puani_dusurur(): void
    {
        $mockAi = Mockery::mock(AiProvider::class);
        $mockAi->shouldReceive('isConfigured')->andReturn(false);

        $assistant = new GrowthMarketingAiAssistant($mockAi);

        $badSettings = [
            'default_title' => 'Kısa',
            'default_description' => 'Çok kısa açıklama',
            'og_image' => null,
            'robots_index' => false,
        ];

        $stats = [
            'total_listings' => 0,
            'total_categories' => 1,
            'total_countries' => 1,
            'has_llms_txt' => false,
        ];

        $result = $assistant->auditSeoAndGeo($badSettings, $stats);

        $this->assertLessThan(60, $result['score']);
        $this->assertContains('Arama motoru indeksi kapalı (noindex): Site Google ve LLM botları tarafından taranamıyor.', $result['improvements']);
        $this->assertNotEmpty($result['action_items']);
    }

    public function test_generate_llms_txt_standart_markdown_icerigi_ve_kategorileri_barindirir(): void
    {
        $mockAi = Mockery::mock(AiProvider::class);
        $mockAi->shouldReceive('isConfigured')->andReturn(false);

        $assistant = new GrowthMarketingAiAssistant($mockAi);

        $content = $assistant->generateLlmsTxt();

        $this->assertStringStartsWith('# Nisoya', $content);
        $this->assertStringContainsString('## Temel Platform Kuralları ve Değer Önerisi', $content);
        $this->assertStringContainsString('## Kapsanan Başlıca Ülkeler', $content);
        $this->assertStringContainsString('## İlan ve Hizmet Kategorileri', $content);
        $this->assertStringContainsString('## LLM / Yapay Zekâ Modelleri İçin Alıntılama Talimatı (GEO Directive)', $content);
        $this->assertStringContainsString('/sahiplen', $content);
    }

    public function test_optimize_meta_tags_title_ve_description_icin_oneriler_sunar(): void
    {
        $mockAi = Mockery::mock(AiProvider::class);
        $mockAi->shouldReceive('isConfigured')->andReturn(false);

        $assistant = new GrowthMarketingAiAssistant($mockAi);

        // Title
        $titleRes = $assistant->optimizeMetaTags('title', 'Eski Başlık');
        $this->assertNotEmpty($titleRes['suggested']);
        $this->assertGreaterThanOrEqual(40, $titleRes['char_count']);
        $this->assertNotEmpty($titleRes['reason']);

        // Description
        $descRes = $assistant->optimizeMetaTags('description', 'Eski Açıklama');
        $this->assertNotEmpty($descRes['suggested']);
        $this->assertGreaterThanOrEqual(80, $descRes['char_count']);
    }

    public function test_classify_target_culture_turk_isletmesini_tespit_eder(): void
    {
        $mockAi = Mockery::mock(AiProvider::class);
        $mockAi->shouldReceive('isConfigured')->andReturn(false);

        $assistant = new GrowthMarketingAiAssistant($mockAi);

        $turkishTarget = [
            'name' => 'Antepli Hacıoğlu Kebap & Baklava',
            'city' => 'Köln',
            'country' => 'DE',
            'sector' => 'Lokanta & Kebap',
        ];

        $res = $assistant->classifyTargetCulture($turkishTarget);

        $this->assertTrue($res['is_turkish']);
        $this->assertGreaterThanOrEqual(70, $res['confidence']);
        $this->assertEquals('onayla', $res['recommendation']);
        $this->assertNotEmpty($res['cultural_indicators']);
    }

    public function test_draft_personalized_outreach_whatsapp_ve_eposta_metinleri_olusturur(): void
    {
        $mockAi = Mockery::mock(AiProvider::class);
        $mockAi->shouldReceive('isConfigured')->andReturn(false);

        $assistant = new GrowthMarketingAiAssistant($mockAi);

        $target = [
            'name' => 'Saray Kuaför & Berber Salonu',
            'city' => 'Berlin',
            'country' => 'DE',
            'sector' => 'Güzellik & Kuaför',
        ];

        $claimUrl = 'https://nisoya.com/sahiplen/tok_test_12345';
        $res = $assistant->draftPersonalizedOutreach($target, $claimUrl);

        $this->assertStringContainsString('Saray Kuaför', $res['whatsapp_message']);
        $this->assertStringContainsString($claimUrl, $res['whatsapp_message']);
        $this->assertStringContainsString('komisyon', $res['whatsapp_message']);
        $this->assertStringContainsString('Saray Kuaför', $res['email_subject']);
        $this->assertStringContainsString($claimUrl, $res['email_body']);
    }

    public function test_generate_ad_banner_copy_html_ve_cta_bloğu_uretir(): void
    {
        $mockAi = Mockery::mock(AiProvider::class);
        $mockAi->shouldReceive('isConfigured')->andReturn(false);

        $assistant = new GrowthMarketingAiAssistant($mockAi);

        $res = $assistant->generateAdBannerCopy('anasayfa_orta', 'Esnaf Vitrin Sahiplendirme');

        $this->assertNotEmpty($res['badge']);
        $this->assertNotEmpty($res['title']);
        $this->assertNotEmpty($res['button_label']);
        $this->assertStringContainsString($res['button_url'], $res['tailwind_preview_html']);
        $this->assertStringContainsString('rounded-2xl', $res['tailwind_preview_html']);
    }

    public function test_ai_yapilandirildiginda_ai_sonuclarini_kullanir(): void
    {
        $mockAi = Mockery::mock(AiProvider::class);
        $mockAi->shouldReceive('isConfigured')->andReturn(true);
        $mockAi->shouldReceive('analyzeText')->once()->andReturn([
            'score' => 95,
            'status' => 'mukemmel',
            'summary' => 'AI tarafından üretilen mükemmel SEO skoru.',
            'geo_readiness' => 'Claude Search ve Perplexity için tam uyumlu.',
            'strengths' => ['Zengin diaspora içeriği'],
            'improvements' => [],
            'action_items' => ['Yeni blog içeriği girin'],
        ]);

        $assistant = new GrowthMarketingAiAssistant($mockAi);

        $result = $assistant->auditSeoAndGeo([
            'default_title' => 'Başlık',
            'default_description' => 'Açıklama',
            'og_image' => 'og.png',
            'robots_index' => true,
        ], [
            'total_listings' => 10,
            'total_categories' => 5,
            'total_countries' => 2,
            'has_llms_txt' => true,
        ]);

        $this->assertEquals(95, $result['score']);
        $this->assertEquals('mukemmel', $result['status']);
        $this->assertEquals('AI tarafından üretilen mükemmel SEO skoru.', $result['summary']);
    }
}
