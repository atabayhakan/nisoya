<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\AnasayfaBolumleri;
use App\Models\User;
use App\Support\HomeSections;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Faz 2 · G5 — anasayfa bölümlerini panelden göster/gizle.
 */
class HomeSectionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private const CTA_TEXT = 'Bir yeteneğin mutlaka vardır.';

    // ------------------------------------------------------------ Yardımcı

    public function test_sections_visible_by_default(): void
    {
        foreach (array_keys(HomeSections::SECTIONS) as $key) {
            $this->assertTrue(HomeSections::visible($key), "{$key} varsayılan görünür olmalı");
        }
    }

    public function test_setting_hides_section(): void
    {
        Settings::setMany(['home.section.cta' => '0']);

        $this->assertFalse(HomeSections::visible('cta'));
        $this->assertTrue(HomeSections::visible('kategoriler'));
    }

    // -------------------------------------------------- Anasayfa render

    public function test_homepage_shows_section_by_default(): void
    {
        $this->get('/')->assertOk()->assertSee(self::CTA_TEXT);
    }

    public function test_homepage_hides_disabled_section(): void
    {
        Settings::setMany(['home.section.cta' => '0']);

        $this->get('/')->assertOk()->assertDontSee(self::CTA_TEXT);
    }

    // --------------------------------------------------------- Admin sayfa

    public function test_admin_can_toggle_sections(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        Livewire::actingAs($admin)
            ->test(AnasayfaBolumleri::class)
            ->fillForm([
                'cta' => false,
                'kategoriler' => true,
                'yeni_ilanlar' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'home.section.cta', 'value' => '0']);
        $this->assertDatabaseHas('site_settings', ['key' => 'home.section.yeni_ilanlar', 'value' => '0']);
        $this->assertDatabaseHas('site_settings', ['key' => 'home.section.kategoriler', 'value' => '1']);
    }

    public function test_member_redirected_from_page(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)
            ->get('/yonetim/anasayfa-bolumleri')
            ->assertRedirect(route('dashboard'));
    }
}
