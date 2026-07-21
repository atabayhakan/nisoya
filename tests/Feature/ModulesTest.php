<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Moduller;
use App\Models\User;
use App\Support\Modules;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Faz 2 · G4 — dikey modülleri (emlak/vasıta/davetiye/iş ilanları) aç/kapa.
 * Kapalı modül: public rota 404, yeni içerik yok, footer/sitemap gizli.
 */
class ModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function verifiedUser(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    // ------------------------------------------------------------ Yardımcı

    public function test_modules_enabled_by_default(): void
    {
        foreach (Modules::KEYS as $key) {
            $this->assertTrue(Modules::enabled($key), "{$key} varsayılan açık olmalı");
        }
    }

    public function test_setting_disables_module(): void
    {
        Settings::setMany(['modul.emlak' => '0']);

        $this->assertFalse(Modules::enabled('emlak'));
        $this->assertTrue(Modules::enabled('vasita'));
    }

    // -------------------------------------------------- Public rota gating

    public function test_vertical_routes_accessible_when_enabled(): void
    {
        $this->get('/emlak')->assertOk();
        $this->get('/vasita')->assertOk();
        $this->get('/isler')->assertOk();
    }

    public function test_emlak_route_404_when_disabled(): void
    {
        Settings::setMany(['modul.emlak' => '0']);

        $this->get('/emlak')->assertNotFound();
        // Diğer modüller etkilenmez
        $this->get('/vasita')->assertOk();
    }

    public function test_vasita_route_404_when_disabled(): void
    {
        Settings::setMany(['modul.vasita' => '0']);

        $this->get('/vasita')->assertNotFound();
    }

    public function test_jobs_route_404_when_disabled(): void
    {
        Settings::setMany(['modul.is_ilanlari' => '0']);

        $this->get('/isler')->assertNotFound();
        $this->get('/adaylar')->assertNotFound();
    }

    // ---------------------------------------------------- Creation gating

    public function test_job_create_404_when_module_disabled(): void
    {
        Settings::setMany(['modul.is_ilanlari' => '0']);

        $this->actingAs($this->verifiedUser())
            ->get('/panel/is-ilani/yeni')
            ->assertNotFound();
    }

    public function test_event_create_404_when_module_disabled(): void
    {
        Settings::setMany(['modul.davetiye' => '0']);

        $this->actingAs($this->verifiedUser())
            ->get('/panel/etkinlik/yeni')
            ->assertNotFound();
    }

    public function test_emlak_listing_create_404_when_module_disabled(): void
    {
        Settings::setMany(['modul.emlak' => '0']);

        $this->actingAs($this->verifiedUser())
            ->get('/panel/ilan/yeni?tip=emlak')
            ->assertNotFound();

        // Hizmet ilanı hâlâ oluşturulabilir
        $this->actingAs($this->verifiedUser())
            ->get('/panel/ilan/yeni?tip=hizmet')
            ->assertOk();
    }

    // --------------------------------------------------------- Admin sayfa

    public function test_admin_can_toggle_modules(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        Livewire::actingAs($admin)
            ->test(Moduller::class)
            ->fillForm([
                'emlak' => false,
                'vasita' => true,
                'davetiye' => true,
                'is_ilanlari' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'modul.emlak', 'value' => '0']);
        $this->assertDatabaseHas('site_settings', ['key' => 'modul.is_ilanlari', 'value' => '0']);
        $this->assertDatabaseHas('site_settings', ['key' => 'modul.vasita', 'value' => '1']);
    }

    public function test_member_redirected_from_modules_page(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)
            ->get('/yonetim/moduller')
            ->assertRedirect(route('dashboard'));
    }

    // ----------------------------------------------------- Sitemap + footer

    public function test_sitemap_excludes_disabled_module(): void
    {
        $emlakUrl = route('properties.index');

        $this->get('/sitemap.xml')->assertOk()->assertSee($emlakUrl);

        Settings::setMany(['modul.emlak' => '0']);

        $this->get('/sitemap.xml')->assertOk()->assertDontSee($emlakUrl);
    }

    public function test_footer_hides_jobs_link_when_disabled(): void
    {
        $jobsUrl = route('jobs.index');

        $this->get('/')->assertOk()->assertSee($jobsUrl);

        Settings::setMany(['modul.is_ilanlari' => '0']);

        $this->get('/')->assertOk()->assertDontSee($jobsUrl);
    }
}
