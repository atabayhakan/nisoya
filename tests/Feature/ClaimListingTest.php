<?php

namespace Tests\Feature;

use App\Ai\Kahya\Araclar\VitrinHazirla;
use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Models\User;
use App\Services\Growth\ClaimableListingCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request as ToolRequest;
use Tests\TestCase;

class ClaimListingTest extends TestCase
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
            ['slug' => 'restoran'],
            ['name' => 'Restoran & Kafe', 'type' => 'hizmet', 'is_active' => true, 'sort_order' => 1]
        );
    }

    public function test_unclaimed_listing_can_be_viewed_via_claim_url(): void
    {
        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromData([
            'name' => 'Rotterdam Boğaziçi Kebap',
            'country_code' => 'NL',
            'city' => 'Rotterdam',
            'category_name' => 'Restoran',
            'phone' => '+31 10 123 4567',
        ]);

        $response = $this->get('/sahiplen/'.$result['claim_token']);

        $response->assertOk();
        $response->assertSee('Rotterdam Boğaziçi Kebap');
        $response->assertSee('Rotterdam');
        $response->assertSee('Vitrininizi Sahiplenin');
    }

    public function test_invalid_token_returns_404(): void
    {
        $response = $this->get('/sahiplen/tamamen-uydurma-gecersiz-bir-token');

        $response->assertNotFound();
    }

    public function test_guest_can_claim_listing_with_instant_registration(): void
    {
        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromData([
            'name' => 'Amsterdam Lale Market',
            'country_code' => 'NL',
            'city' => 'Amsterdam',
            'category_name' => 'Market',
        ]);

        $token = $result['claim_token'];
        $listing = $result['listing'];

        $response = $this->post('/sahiplen/'.$token, [
            'name' => 'Ahmet Demir',
            'email' => 'ahmet@lalemarket.nl',
            'password' => 'GuvenliSifre123!',
            'password_confirmation' => 'GuvenliSifre123!',
        ]);

        $response->assertRedirect(route('listings.show', [$listing->id, $listing->slug]));

        $this->assertAuthenticated();

        $user = User::where('email', 'ahmet@lalemarket.nl')->first();
        $this->assertNotNull($user);
        $this->assertSame('Ahmet Demir', $user->name);

        $listing->refresh();
        $this->assertTrue($listing->is_claimed);
        $this->assertSame($user->id, $listing->user_id);
        $this->assertNull($listing->claim_token);
        $this->assertNotNull($listing->claimed_at);
        $this->assertSame(ListingStatus::Aktif, $listing->status);
    }

    public function test_authenticated_user_can_claim_listing(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'country_code' => 'NL',
        ]);

        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromData([
            'name' => 'Utrecht Berber Mehmet',
            'country_code' => 'NL',
            'city' => 'Utrecht',
        ]);

        $token = $result['claim_token'];
        $listing = $result['listing'];

        $response = $this->actingAs($user)->post('/sahiplen/'.$token);

        $response->assertRedirect(route('listings.show', [$listing->id, $listing->slug]));

        $listing->refresh();
        $this->assertTrue($listing->is_claimed);
        $this->assertSame($user->id, $listing->user_id);
        $this->assertNull($listing->claim_token);
    }

    public function test_already_claimed_listing_cannot_be_claimed_again(): void
    {
        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromData([
            'name' => 'Lahey Türk Fırını',
            'country_code' => 'NL',
            'city' => 'Lahey',
        ]);

        $token = $result['claim_token'];

        // İlk sahiplenme
        $this->post('/sahiplen/'.$token, [
            'name' => 'Fırıncı Hasan',
            'email' => 'hasan@firinci.nl',
            'password' => 'GuvenliSifre123!',
            'password_confirmation' => 'GuvenliSifre123!',
        ])->assertRedirect();

        // İkinci kez aynı tokenle erişim 404 dönmeli
        $this->get('/sahiplen/'.$token)->assertNotFound();
    }

    public function test_unclaimed_listing_shows_claim_banner_on_detail_page(): void
    {
        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromData([
            'name' => 'Eindhoven Oto Servis',
            'country_code' => 'NL',
            'city' => 'Eindhoven',
        ]);

        $listing = $result['listing'];

        $response = $this->get(route('listings.show', [$listing->id, $listing->slug]));

        $response->assertOk();
        $response->assertSee('Bu işletmenin sahibi veya yetkilisi misiniz?');
        $response->assertSee(route('claim.show', $result['claim_token']));
    }

    public function test_claimed_listing_does_not_show_claim_banner(): void
    {
        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromData([
            'name' => 'Delft Çiçekçi Ayşe',
            'country_code' => 'NL',
            'city' => 'Delft',
        ]);

        $token = $result['claim_token'];
        $listing = $result['listing'];

        // Sahiplen
        $this->post('/sahiplen/'.$token, [
            'name' => 'Ayşe Gül',
            'email' => 'ayse@delftcicek.nl',
            'password' => 'GuvenliSifre123!',
            'password_confirmation' => 'GuvenliSifre123!',
        ]);

        $listing->refresh();

        $response = $this->get(route('listings.show', [$listing->id, $listing->slug]));

        $response->assertOk();
        $response->assertDontSee('Bu işletmenin sahibi veya yetkilisi misiniz?');
    }

    public function test_vitrin_hazirla_tool_creates_claimable_listing(): void
    {
        $tool = app(VitrinHazirla::class);

        $request = new ToolRequest([
            'isletme_adi' => 'Antwerpen Halı Yıkama',
            'ulke_kodu' => 'NL',
            'sehir' => 'Antwerpen',
            'kategori' => 'Hizmet',
            'telefon' => '+32 3 999 8888',
        ]);

        $output = (string) $tool->handle($request);

        $this->assertStringContainsString('BAŞARILI: Sahiplenilebilir vitrin oluşturuldu', $output);
        $this->assertStringContainsString('/sahiplen/', $output);

        $listing = Listing::where('title', 'Antwerpen Halı Yıkama')->first();
        $this->assertNotNull($listing);
        $this->assertFalse($listing->is_claimed);
        $this->assertNotNull($listing->claim_token);
        $this->assertSame('+32 3 999 8888', $listing->claim_phone);
    }
}
