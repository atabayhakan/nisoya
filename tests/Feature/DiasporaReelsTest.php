<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\DiasporaReels\Pages\ListDiasporaReels;
use App\Models\Country;
use App\Models\DiasporaReel;
use App\Models\User;
use App\Services\Ai\CmsAiAssistant;
use App\Support\InstagramMedia;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\DiasporaReelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DiasporaReelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_instagram_media_extracts_shortcodes_and_formats_embed_urls(): void
    {
        $this->assertSame('C8xABC12345', InstagramMedia::extractShortcode('https://www.instagram.com/reel/C8xABC12345/'));
        $this->assertSame('C9yDEF67890', InstagramMedia::extractShortcode('https://www.instagram.com/p/C9yDEF67890?igsh=123456'));
        $this->assertSame('C5wJKL33445', InstagramMedia::extractShortcode('https://instagr.am/reel/C5wJKL33445/'));
        $this->assertSame('C3uPQR77889', InstagramMedia::extractShortcode('C3uPQR77889'));
        $this->assertNull(InstagramMedia::extractShortcode('https://example.com/not-instagram'));

        $this->assertSame('https://www.instagram.com/reel/C8xABC12345/embed', InstagramMedia::embedUrl('https://www.instagram.com/reel/C8xABC12345/'));
        $this->assertSame('@berlinturkleri', InstagramMedia::normalizeUsername('berlinturkleri'));
        $this->assertSame('@berlinturkleri', InstagramMedia::normalizeUsername('@berlinturkleri'));
        $this->assertSame('@berlinturkleri', InstagramMedia::normalizeUsername('https://www.instagram.com/berlinturkleri/'));
    }

    public function test_diaspora_reel_creation_auto_populates_shortcode(): void
    {
        $reel = DiasporaReel::create([
            'title' => 'Frankfurt Buluşması',
            'caption' => 'Açıklama notu',
            'instagram_url' => 'https://www.instagram.com/reel/CxY98765432/',
            'country_code' => 'DE',
            'city' => 'Frankfurt',
            'instagram_username' => 'frankfurt_turkleri',
        ]);

        $this->assertSame('CxY98765432', $reel->shortcode);
        $this->assertSame('@frankfurt_turkleri', $reel->instagram_username);
        $this->assertSame('https://www.instagram.com/reel/CxY98765432/embed', $reel->embed_url);
        $this->assertInstanceOf(Country::class, $reel->country);
        $this->assertSame('DE', $reel->country->code);
    }

    public function test_homepage_renders_diaspora_reels_showcase(): void
    {
        $this->seed(DiasporaReelSeeder::class);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('DİASPORADAN CANLI');
        $response->assertSee('Sınırların Ötesinde, Kendi İnsanımızla');
        $response->assertSee('Berlin Kreuzberg Türk Kültür Festivali');
        $response->assertSee('@berlinturkleri');
        $response->assertSee('Bişkek Türk Girişimciler &amp; Esnaf Buluşması', false);
        $response->assertSee('Amsterdam Türk Gençlik &amp; Kültür Buluşması', false);
    }

    public function test_homepage_falls_back_when_no_reels_exist(): void
    {
        DiasporaReel::query()->delete();

        $response = $this->get('/');
        $response->assertOk();
        $response->assertDontSee('DİASPORADAN CANLI');
        // Fallback displays value proposition badges
        $response->assertSee('Tamamen Türkçe');
    }

    public function test_admin_can_access_diaspora_reels_filament_management(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $reel = DiasporaReel::create([
            'title' => 'Münih Türk Topluluğu',
            'instagram_url' => 'https://www.instagram.com/reel/C1234567890/',
            'country_code' => 'DE',
            'city' => 'Münih',
        ]);

        $this->actingAs($admin)
            ->get('/yonetim/diaspora-reels')
            ->assertOk()
            ->assertSee('Diaspora Reels &amp; Hikayeleri', false)
            ->assertSee('Münih Türk Topluluğu');

        $this->actingAs($admin)
            ->get('/yonetim/diaspora-reels/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get("/yonetim/diaspora-reels/{$reel->id}/edit")
            ->assertOk();
    }

    public function test_non_admin_cannot_access_diaspora_reels_management(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
            'email_verified_at' => now(),
        ]);

        $regularUser = User::factory()->create([
            'role' => UserRole::Uye,
            'email_verified_at' => now(),
        ]);

        // Moderator can access panel, but RestrictsToAdmins gives 403 on this CMS resource
        $this->actingAs($moderator)
            ->get('/yonetim/diaspora-reels')
            ->assertForbidden();

        // Regular user cannot enter panel
        $this->actingAs($regularUser)
            ->get('/yonetim/diaspora-reels')
            ->assertRedirect();

        $this->get('/yonetim/diaspora-reels')
            ->assertRedirect();
    }

    public function test_admin_can_use_claude_ai_to_create_diaspora_reel(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $mockAssistant = $this->createMock(CmsAiAssistant::class);
        $mockAssistant->method('generateDiasporaStory')->willReturn([
            'title' => 'Hamburg Türk Kültür Akşamı',
            'caption' => 'Almanya’daki Türk diasporası bir arada.',
            'suggested_city' => 'Hamburg',
            'country_code' => 'DE',
            'suggested_username' => '@hamburg_turkleri',
        ]);
        $this->app->instance(CmsAiAssistant::class, $mockAssistant);

        Livewire::actingAs($admin)
            ->test(ListDiasporaReels::class)
            ->callAction('aiReelsTaslagi', [
                'odak' => 'Hamburg Türk Kültür Akşamı',
                'instagram_url' => 'https://www.instagram.com/reel/C8xABC12345/',
                'country_code' => 'DE',
                'city' => 'Hamburg',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('diaspora_reels', [
            'title' => 'Hamburg Türk Kültür Akşamı',
            'city' => 'Hamburg',
            'country_code' => 'DE',
            'instagram_username' => '@hamburg_turkleri',
        ]);
    }

    public function test_admin_cannot_load_fabricated_sample_reels_from_list_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $this->assertDatabaseCount('diaspora_reels', 0);

        Livewire::actingAs($admin)
            ->test(ListDiasporaReels::class)
            ->assertActionDoesNotExist('ornekleriYukle');

        $this->assertDatabaseCount('diaspora_reels', 0);
    }

    public function test_tabs_and_stats_widget_work_properly(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        DiasporaReel::create([
            'title' => 'Berlin Etkinlik',
            'status' => DiasporaReel::STATUS_PUBLISHED,
            'instagram_url' => 'https://www.instagram.com/reel/C1111111111/',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'is_active' => true,
            'is_featured' => true,
        ]);

        DiasporaReel::create([
            'title' => 'Bişkek Buluşma',
            'status' => DiasporaReel::STATUS_DRAFT,
            'instagram_url' => 'https://www.instagram.com/reel/C2222222222/',
            'country_code' => 'KG',
            'city' => 'Bişkek',
            'is_active' => false,
            'is_featured' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(ListDiasporaReels::class)
            ->assertSee('Berlin Etkinlik')
            ->set('activeTab', 'yayinda')
            ->assertSee('Berlin Etkinlik')
            ->assertDontSee('Bişkek Buluşma')
            ->set('activeTab', 'onay_bekleyen')
            ->assertSee('Bişkek Buluşma')
            ->assertDontSee('Berlin Etkinlik');
    }
}
