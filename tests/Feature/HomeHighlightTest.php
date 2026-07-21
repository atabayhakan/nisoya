<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\BigHighlights\BigHighlightResource;
use App\Filament\Resources\SmallHighlights\SmallHighlightResource;
use App\Models\HomeHighlight;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\HomeHighlightSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHighlightTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_seeder_creates_one_highlight_per_slot_from_existing_settings(): void
    {
        $this->seed(HomeHighlightSeeder::class);

        $this->assertDatabaseHas('home_highlights', ['slot' => HomeHighlight::SLOT_BIG, 'title' => 'Tamamen Türkçe']);
        $this->assertDatabaseHas('home_highlights', ['slot' => HomeHighlight::SLOT_SMALL, 'title' => 'Ücretsiz ilan']);
        $this->assertSame(2, HomeHighlight::count());
    }

    public function test_seeder_does_not_duplicate_or_overwrite_admin_edits(): void
    {
        $this->seed(HomeHighlightSeeder::class);

        HomeHighlight::where('slot', HomeHighlight::SLOT_BIG)->first()->update(['title' => 'Admin Değiştirdi']);

        $this->seed(HomeHighlightSeeder::class);

        $this->assertSame(2, HomeHighlight::count());
        $this->assertDatabaseHas('home_highlights', ['title' => 'Admin Değiştirdi']);
    }

    public function test_for_slot_excludes_inactive_and_respects_order(): void
    {
        HomeHighlight::create(['slot' => HomeHighlight::SLOT_BIG, 'title' => 'B', 'text' => '.', 'icon' => 'star', 'sort_order' => 2]);
        HomeHighlight::create(['slot' => HomeHighlight::SLOT_BIG, 'title' => 'A', 'text' => '.', 'icon' => 'star', 'sort_order' => 1]);
        HomeHighlight::create(['slot' => HomeHighlight::SLOT_BIG, 'title' => 'Gizli', 'text' => '.', 'icon' => 'star', 'sort_order' => 0, 'is_active' => false]);
        HomeHighlight::create(['slot' => HomeHighlight::SLOT_SMALL, 'title' => 'Başka Slot', 'text' => '.', 'icon' => 'star', 'sort_order' => 0]);

        $titles = HomeHighlight::forSlot(HomeHighlight::SLOT_BIG)->pluck('title')->all();

        $this->assertSame(['A', 'B'], $titles);
    }

    public function test_unknown_icon_falls_back_to_default_heroicon(): void
    {
        $highlight = HomeHighlight::create(['slot' => HomeHighlight::SLOT_BIG, 'title' => 'X', 'text' => '.', 'icon' => 'gecersiz-ikon']);

        $this->assertSame('sparkles', $highlight->heroicon());
    }

    public function test_homepage_renders_highlights_from_both_slots(): void
    {
        HomeHighlight::create(['slot' => HomeHighlight::SLOT_BIG, 'title' => 'Büyük Vurgu Metni', 'text' => '.', 'icon' => 'language']);
        HomeHighlight::create(['slot' => HomeHighlight::SLOT_SMALL, 'title' => 'Küçük Vurgu Metni', 'text' => '.', 'icon' => 'sparkles']);

        $this->get('/')->assertOk()->assertSee('Büyük Vurgu Metni')->assertSee('Küçük Vurgu Metni');
    }

    public function test_homepage_falls_back_to_static_copy_when_no_highlights_exist(): void
    {
        $this->get('/')->assertOk()->assertSee('Tamamen Türkçe')->assertSee('Ücretsiz ilan');
    }

    public function test_resources_are_scoped_to_their_own_slot(): void
    {
        HomeHighlight::create(['slot' => HomeHighlight::SLOT_BIG, 'title' => 'Büyük Kayıt', 'text' => '.', 'icon' => 'star']);
        HomeHighlight::create(['slot' => HomeHighlight::SLOT_SMALL, 'title' => 'Küçük Kayıt', 'text' => '.', 'icon' => 'star']);

        $this->assertSame(['Büyük Kayıt'], BigHighlightResource::getEloquentQuery()->pluck('title')->all());
        $this->assertSame(['Küçük Kayıt'], SmallHighlightResource::getEloquentQuery()->pluck('title')->all());
    }

    public function test_admin_can_manage_big_and_small_highlights(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        $big = HomeHighlight::create(['slot' => HomeHighlight::SLOT_BIG, 'title' => 'T', 'text' => '.', 'icon' => 'star']);
        $small = HomeHighlight::create(['slot' => HomeHighlight::SLOT_SMALL, 'title' => 'T', 'text' => '.', 'icon' => 'star']);

        $this->actingAs($admin)->get('/yonetim/big-highlights')->assertOk();
        $this->actingAs($admin)->get('/yonetim/big-highlights/create')->assertOk();
        $this->actingAs($admin)->get("/yonetim/big-highlights/{$big->id}/edit")->assertOk();
        $this->actingAs($admin)->get('/yonetim/small-highlights')->assertOk();
        $this->actingAs($admin)->get('/yonetim/small-highlights/create')->assertOk();
        $this->actingAs($admin)->get("/yonetim/small-highlights/{$small->id}/edit")->assertOk();
    }

    public function test_member_cannot_access_highlight_admin(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)->get('/yonetim/big-highlights')->assertRedirect(route('dashboard'));
        $this->actingAs($member)->get('/yonetim/small-highlights')->assertRedirect(route('dashboard'));
    }

    public function test_highlight_can_be_saved_with_null_title_text_and_icon(): void
    {
        $highlight = HomeHighlight::create([
            'slot' => HomeHighlight::SLOT_BIG,
            'title' => null,
            'text' => null,
            'icon' => null,
            'media' => [
                ['type' => 'video', 'data' => ['path' => 'highlights/sample.mp4']],
            ],
        ]);

        $this->assertDatabaseHas('home_highlights', [
            'id' => $highlight->id,
            'title' => null,
            'text' => null,
            'icon' => null,
        ]);
        $this->get('/')->assertOk();
    }
}
