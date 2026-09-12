<?php

namespace Tests\Feature;

use App\Models\BekleyenHamle;
use App\Models\Category;
use App\Models\Country;
use App\Models\OutreachTarget;
use App\Services\Growth\ClaimableListingCreator;
use App\Services\Growth\DetectionResult;
use App\Services\Growth\ErisimMesajiYazari;
use App\Support\Growth\RegionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrowthOutreachIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Country::firstOrCreate(
            ['code' => 'NL'],
            ['name_tr' => 'Hollanda', 'name_en' => 'Netherlands', 'is_active' => true, 'currency_code' => 'EUR', 'sort_order' => 1]
        );

        Country::firstOrCreate(
            ['code' => 'TR'],
            ['name_tr' => 'Türkiye', 'name_en' => 'Turkey', 'is_active' => true, 'currency_code' => 'TRY', 'sort_order' => 2]
        );

        Category::firstOrCreate(
            ['slug' => 'hizmet'],
            ['name' => 'Hizmetler', 'type' => 'hizmet', 'is_active' => true, 'sort_order' => 1]
        );
    }

    protected function createTurkishTarget(array $overrides = []): OutreachTarget
    {
        return OutreachTarget::create(array_merge([
            'name' => 'Rotterdam Kuaför Kenan',
            'external_id' => 'place_'.uniqid(),
            'country' => 'NL',
            'city' => 'Rotterdam',
            'sector' => 'Güzellik & Bakım',
            'category' => 'Kuaför',
            'contact_email' => 'kenan@kuaforrotterdam.nl',
            'detection_band' => DetectionResult::BAND_TURKISH,
            'detection_confidence' => 95,
            'marketing_status' => RegionPolicy::ALLOWED,
            'source' => 'test',
        ], $overrides));
    }

    public function test_erisim_mesaji_yazari_generates_claim_link_when_listing_exists(): void
    {
        $target = $this->createTurkishTarget();

        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromTarget($target);
        $target->refresh();

        $this->assertNotNull($target->listing_id);

        $yazar = app(ErisimMesajiYazari::class);
        $taslak = $yazar->taslak($target);

        $this->assertTrue($taslak['has_claimable']);
        $this->assertNotNull($taslak['claim_url']);
        $this->assertStringContainsString('/sahiplen/'.$result['claim_token'], $taslak['claim_url']);
        $this->assertSame('Rotterdam Kuaför Kenan için Nisoya vitrininiz hazır', $taslak['konu']);

        $mesaj = $taslak['mesaj'];
        $this->assertStringContainsString('Rotterdam Kuaför Kenan için Nisoya\'da önceden bir tanıtım vitrini hazırladık', $mesaj);
        $this->assertStringContainsString($taslak['listing_url'], $mesaj);
        $this->assertStringContainsString($taslak['claim_url'], $mesaj);
        $this->assertStringContainsString('15 saniyede ücretsiz olarak sahiplenebilirsiniz', $mesaj);
    }

    public function test_erisim_mesaji_yazari_falls_back_when_no_listing(): void
    {
        $target = $this->createTurkishTarget();

        $yazar = app(ErisimMesajiYazari::class);
        $taslak = $yazar->taslak($target);

        $this->assertFalse($taslak['has_claimable']);
        $this->assertNull($taslak['claim_url']);
        $this->assertSame('Yurtdışındaki Türkler için ücretsiz ilan alanı — Nisoya', $taslak['konu']);
        $this->assertStringContainsString('beş dakikada açılıyor: nisoya.com', $taslak['mesaj']);
        $this->assertStringNotContainsString('/sahiplen/', $taslak['mesaj']);
    }

    public function test_claimable_listing_creator_creates_from_outreach_target(): void
    {
        $target = $this->createTurkishTarget([
            'name' => 'Den Haag Nazar Market',
            'city' => 'Lahey',
            'country' => 'NL',
            'contact_email' => 'nazar@market.nl',
        ]);

        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromTarget($target);

        $this->assertArrayHasKey('listing', $result);
        $this->assertArrayHasKey('claim_url', $result);
        $this->assertArrayHasKey('claim_token', $result);

        $listing = $result['listing'];
        $this->assertSame('Den Haag Nazar Market', $listing->title);
        $this->assertSame('Lahey', $listing->city);
        $this->assertSame('NL', $listing->country_code);
        $this->assertFalse($listing->is_claimed);
        $this->assertSame($result['claim_token'], $listing->claim_token);

        $target->refresh();
        $this->assertSame($listing->id, $target->listing_id);
        $this->assertTrue($target->listing->is($listing));
    }

    public function test_bekleyen_hamle_stores_listing_id_and_links_to_listing(): void
    {
        $target = $this->createTurkishTarget();
        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromTarget($target);
        $listing = $result['listing'];

        $hamle = BekleyenHamle::create([
            'listing_id' => $listing->id,
            'baslik' => 'Nazar Market Vitrin Daveti',
            'gerekce' => 'Esnafa vitrinini sahiplenmesi için davet postası',
            'icerik' => 'Merhaba, vitrininiz hazır...',
            'tur' => 'eposta',
            'alici_eposta' => 'nazar@market.nl',
        ]);

        $this->assertNotNull($hamle->listing);
        $this->assertSame($listing->id, $hamle->listing->id);
        $this->assertTrue($listing->bekleyenHamleler->contains($hamle));
    }
}
