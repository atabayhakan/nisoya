<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Models\OutreachTarget;
use App\Models\User;
use App\Support\QrKodu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimQrCodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

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

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_claim_page_renders_qr_code_and_kit(): void
    {
        $listing = Listing::create([
            'user_id' => $this->admin->id,
            'category_id' => 1,
            'type' => ListingType::Hizmet,
            'title' => 'Rotterdam Terzi Hasan',
            'slug' => 'rotterdam-terzi-hasan',
            'description' => 'Açıklama',
            'country_code' => 'NL',
            'city' => 'Rotterdam',
            'status' => ListingStatus::Aktif,
            'is_claimed' => false,
            'claim_token' => 'test_token_hasan_123',
        ]);

        $response = $this->get('/sahiplen/test_token_hasan_123');

        $response->assertOk();
        $response->assertSee('Dükkanınızın Dijital QR Kodu ve Tanıtım Kiti');
        $response->assertSee('<svg', false); // SVG QR kodun basıldığını doğrula
        $response->assertSee(route('listings.card', $listing));
        $response->assertSee('WhatsApp Durum Kartı (1080x1920)');
    }

    public function test_outreach_targets_modal_view_renders_qr_and_links(): void
    {
        $listing = Listing::create([
            'user_id' => $this->admin->id,
            'category_id' => 1,
            'type' => ListingType::Hizmet,
            'title' => 'Köşem Market',
            'slug' => 'kosem-market',
            'description' => 'Açıklama',
            'country_code' => 'NL',
            'city' => 'Amsterdam',
            'status' => ListingStatus::Aktif,
            'is_claimed' => false,
            'claim_token' => 'token_kosem',
        ]);

        $target = OutreachTarget::create([
            'name' => 'Köşem Market',
            'external_id' => 'place_kosem',
            'country' => 'NL',
            'city' => 'Amsterdam',
            'source' => 'test',
            'listing_id' => $listing->id,
        ]);

        $listingUrl = route('listings.show', [$listing->id, $listing->slug]);
        $claimUrl = url('/sahiplen/'.$listing->claim_token);
        $qrSvg = QrKodu::svg($listingUrl, 240);

        $view = $this->view('filament.outreach.qr-ve-kart', [
            'aday' => $target,
            'listing' => $listing,
            'listingUrl' => $listingUrl,
            'claimUrl' => $claimUrl,
            'qrSvg' => $qrSvg,
        ]);

        $view->assertSee('Köşem Market');
        $view->assertSee('<svg', false);
        $view->assertSee($listingUrl);
        $view->assertSee($claimUrl);
        $view->assertSee(route('listings.card', $listing));
    }
}
