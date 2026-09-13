<?php

namespace Tests\Feature;

use App\Models\DiasporaReel;
use App\Services\Ai\CmsAiAssistant;
use App\Services\Diaspora\DiasporaIntelligenceService;
use App\Services\Diaspora\DiasporaRankingEngine;
use App\Services\Diaspora\InstagramMetadataExtractor;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiasporaIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_instagram_metadata_extractor_parses_shortcode_and_generates_structure(): void
    {
        $extractor = app(InstagramMetadataExtractor::class);

        $result = $extractor->extract('https://www.instagram.com/reel/C8xABC12345/');

        $this->assertTrue($result['success']);
        $this->assertSame('C8xABC12345', $result['shortcode']);
        $this->assertSame('https://www.instagram.com/reel/C8xABC12345/embed', $result['embed_url']);
    }

    public function test_intelligence_service_detects_city_and_country(): void
    {
        $intelligence = app(DiasporaIntelligenceService::class);

        $de = $intelligence->detectLocation('Berlin Kreuzberg sokak festivali coşkusu');
        $this->assertSame('DE', $de['country_code']);
        $this->assertSame('Berlin', $de['city']);

        $kg = $intelligence->detectLocation('Bişkek Çüy caddesinde Türk girişimciler buluştu');
        $this->assertSame('KG', $kg['country_code']);
        $this->assertSame('Bişkek', $kg['city']);

        $nl = $intelligence->detectLocation('Amsterdam kanal kenarında çay sohbeti');
        $this->assertSame('NL', $nl['country_code']);
        $this->assertSame('Amsterdam', $nl['city']);

        $gb = $intelligence->detectLocation('Londra Türk toplumu dernek etkinliği');
        $this->assertSame('GB', $gb['country_code']);
        $this->assertSame('Londra', $gb['city']);
    }

    public function test_intelligence_service_detects_categories(): void
    {
        $intelligence = app(DiasporaIntelligenceService::class);

        $this->assertSame(
            DiasporaReel::CATEGORY_GASTRONOMI,
            $intelligence->detectCategory('Kreuzberg’in en meşhur yaprak döneri ve çıtır baklavası')
        );

        $this->assertSame(
            DiasporaReel::CATEGORY_SPOR,
            $intelligence->detectCategory('Berlin Türk Masterler halı saha turnuvası final maçı')
        );

        $this->assertSame(
            DiasporaReel::CATEGORY_ETKINLIK,
            $intelligence->detectCategory('Köln Türk Kültür Festivali ve açık hava konseri')
        );

        $this->assertSame(
            DiasporaReel::CATEGORY_REHBER,
            $intelligence->detectCategory('Almanya konsolosluk pasaport yenileme ve vize randevu rehberi')
        );

        $this->assertSame(
            DiasporaReel::CATEGORY_TOPLULUK,
            $intelligence->detectCategory('Gurbetçi aileler derneği dayanışma ve gençlik iftarı')
        );

        $this->assertSame(
            DiasporaReel::CATEGORY_GENEL,
            $intelligence->detectCategory('Güzel bir gün batımı ve manzara karesi')
        );
    }

    public function test_intelligence_service_evaluates_safety(): void
    {
        $intelligence = app(DiasporaIntelligenceService::class);

        $clean = $intelligence->evaluateSafety('Harika bir Türk kültürü akşamı ve geleneksel tatlar');
        $this->assertSame(100, $clean['score']);
        $this->assertSame('safe', $clean['status']);

        $spam = $intelligence->evaluateSafety('Günde 5000 euro garanti kazan canlı bahis casino bonus al');
        $this->assertLessThan(60, $spam['score']);
        $this->assertSame('rejected', $spam['status']);
    }

    public function test_intelligence_service_enriches_payload(): void
    {
        $intelligence = app(DiasporaIntelligenceService::class);

        $enriched = $intelligence->enrich([
            'title' => 'Frankfurt Türk Dönerciler Festivali',
            'caption' => 'En leziz tatlar ve geleneksel sokak döneri Frankfurt meydanında.',
            'instagram_url' => 'https://www.instagram.com/reel/C8xABC99999/',
            'instagram_username' => 'frankfurt_lezzet',
        ]);

        $this->assertSame('DE', $enriched['country_code']);
        $this->assertSame('Frankfurt', $enriched['city']);
        $this->assertSame(DiasporaReel::CATEGORY_GASTRONOMI, $enriched['category']);
        $this->assertSame(100, $enriched['safety_score']);
        $this->assertSame('safe', $enriched['safety_status']);
    }

    public function test_ranking_engine_calculates_scores_and_promotes_featured(): void
    {
        $reel1 = DiasporaReel::create([
            'title' => 'Düşük Etkileşimli İçerik',
            'instagram_url' => 'https://www.instagram.com/reel/C1111111111/',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'status' => DiasporaReel::STATUS_PUBLISHED,
            'is_active' => true,
            'views_count' => 500,
            'likes_count' => 10,
            'is_featured' => false,
        ]);

        $reel2 = DiasporaReel::create([
            'title' => 'Viral Çok İzlenen İçerik',
            'instagram_url' => 'https://www.instagram.com/reel/C2222222222/',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'status' => DiasporaReel::STATUS_PUBLISHED,
            'is_active' => true,
            'views_count' => 50000,
            'likes_count' => 4500,
            'is_featured' => false,
        ]);

        $rankingEngine = app(DiasporaRankingEngine::class);
        $result = $rankingEngine->recalculateAndRank();

        $this->assertSame(2, $result['recalculated_count']);
        $this->assertGreaterThanOrEqual(1, $result['promoted_featured']);

        $reel2->refresh();
        $this->assertTrue($reel2->is_featured);
        $this->assertGreaterThan(1000, $reel2->engagement_score);
    }
}
