<?php

namespace Tests\Feature;

use App\Ai\Kahya\Araclar\VitrinHazirla;
use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Models\OutreachTarget;
use App\Services\Growth\ClaimableListingCreator;
use App\Services\Growth\WhatsAppDavetServisi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class WhatsAppOutreachTest extends TestCase
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

    public function test_temizle_telefon_cleans_various_formats(): void
    {
        $servis = app(WhatsAppDavetServisi::class);

        $this->assertSame('31612345678', $servis->temizleTelefon('+31 6 1234 5678'));
        $this->assertSame('491701234567', $servis->temizleTelefon('0049 170 1234567'));
        $this->assertSame('905321112233', $servis->temizleTelefon('+90 (532) 111-22-33'));
        $this->assertSame('3120123456', $servis->temizleTelefon('+31.20.123.456'));
        $this->assertNull($servis->temizleTelefon(''));
        $this->assertNull($servis->temizleTelefon(null));
        $this->assertNull($servis->temizleTelefon('---'));
    }

    public function test_mesaj_contains_all_required_elements(): void
    {
        $servis = app(WhatsAppDavetServisi::class);
        $mesaj = $servis->mesaj(
            isletmeAdi: 'Köşem Restoran',
            sehir: 'Rotterdam',
            listingUrl: 'https://nisoya.com/ilan/123/kosem-restoran',
            claimUrl: 'https://nisoya.com/sahiplen/abc123token'
        );

        $this->assertStringContainsString('Köşem Restoran', $mesaj);
        $this->assertStringContainsString('Rotterdam', $mesaj);
        $this->assertStringContainsString('https://nisoya.com/ilan/123/kosem-restoran', $mesaj);
        $this->assertStringContainsString('https://nisoya.com/sahiplen/abc123token', $mesaj);
        $this->assertStringContainsString('15 saniyede ücretsiz olarak sahiplenebilirsiniz', $mesaj);
        $this->assertStringContainsString('komisyon veya üyelik ücreti yoktur', $mesaj);
    }

    public function test_url_generates_valid_wa_me_link(): void
    {
        $servis = app(WhatsAppDavetServisi::class);
        $url = $servis->url(
            phone: '+31 6 1234 5678',
            isletmeAdi: 'Köşem Restoran',
            sehir: 'Rotterdam',
            listingUrl: 'https://nisoya.com/ilan/123',
            claimUrl: 'https://nisoya.com/sahiplen/token123'
        );

        $this->assertStringStartsWith('https://wa.me/31612345678?text=', $url);
        $this->assertStringContainsString(rawurlencode('Köşem Restoran'), $url);
        $this->assertStringContainsString(rawurlencode('https://nisoya.com/sahiplen/token123'), $url);
    }

    public function test_listing_icin_url_generates_link_with_claim_token(): void
    {
        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromData([
            'name' => 'Akdeniz Market',
            'city' => 'Amsterdam',
            'country_code' => 'NL',
            'phone' => '+31 20 987 6543',
        ]);

        /** @var Listing $listing */
        $listing = $result['listing'];

        $servis = app(WhatsAppDavetServisi::class);
        $url = $servis->listingIcinUrl($listing);

        $this->assertStringStartsWith('https://wa.me/31209876543?text=', $url);
        $this->assertStringContainsString($result['claim_token'], $url);
        $this->assertStringContainsString(rawurlencode('Akdeniz Market'), $url);
    }

    public function test_aday_icin_url_works_with_linked_listing_and_detection_signals(): void
    {
        $servis = app(WhatsAppDavetServisi::class);

        // Durum 1: İlanı ve telefonu olan aday
        $creator = app(ClaimableListingCreator::class);
        $targetWithListing = OutreachTarget::create([
            'name' => 'Huzur Fırını',
            'external_id' => 'place_huzur',
            'country' => 'NL',
            'city' => 'Utrecht',
            'source' => 'test',
        ]);

        $result = $creator->createFromData([
            'name' => 'Huzur Fırını',
            'city' => 'Utrecht',
            'country_code' => 'NL',
            'phone' => '+31 30 111 2233',
        ], $targetWithListing);

        $url1 = $servis->adayIcinUrl($targetWithListing->refresh());
        $this->assertStringStartsWith('https://wa.me/31301112233?text=', $url1);
        $this->assertStringContainsString($result['claim_token'], $url1);

        // Durum 2: Henüz ilanı olmayan ama detection_signals içinde telefon olan aday
        $targetSignals = OutreachTarget::create([
            'name' => 'Anadolu Kasap',
            'external_id' => 'place_anadolu',
            'country' => 'NL',
            'city' => 'Rotterdam',
            'source' => 'test',
            'detection_signals' => ['phone' => '+31 10 444 5566'],
        ]);

        $url2 = $servis->adayIcinUrl($targetSignals);
        $this->assertStringStartsWith('https://wa.me/31104445566?text=', $url2);
        $this->assertStringContainsString(rawurlencode('Anadolu Kasap'), $url2);
    }

    public function test_vitrin_hazirla_tool_returns_whatsapp_invite_url(): void
    {
        $tool = app(VitrinHazirla::class);
        $response = (string) $tool->handle(new Request([
            'isletme_adi' => 'Diyarbakır Sofrası',
            'ulke_kodu' => 'NL',
            'sehir' => 'Rotterdam',
            'telefon' => '+31 10 555 6677',
        ]));

        $this->assertStringContainsString('BAŞARILI: Sahiplenilebilir vitrin oluşturuldu.', $response);
        $this->assertStringContainsString('Diyarbakır Sofrası', $response);
        $this->assertStringContainsString('WhatsApp Doğrudan Davet: https://wa.me/31105556677?text=', $response);
        $this->assertStringContainsString('/sahiplen/', $response);
    }
}
