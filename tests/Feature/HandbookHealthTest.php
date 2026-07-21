<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Faz 1 · G10 — panel-içi El Kitabı (kendini anlatan rehber) ve genişletilmiş
 * sağlık paneli (son yedek / boş disk / e-posta durumu).
 */
class HandbookHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_admin_can_view_handbook(): void
    {
        $this->actingAs($this->admin())
            ->get('/yonetim/el-kitabi')
            ->assertOk()
            ->assertSee('El Kitabı')
            ->assertSee('Acil durum');
    }

    public function test_member_is_redirected_from_handbook(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)
            ->get('/yonetim/el-kitabi')
            ->assertRedirect(route('dashboard'));
    }

    public function test_moderator_is_forbidden_from_handbook(): void
    {
        $mod = User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);

        $this->actingAs($mod)
            ->get('/yonetim/el-kitabi')
            ->assertForbidden();
    }

    public function test_dashboard_health_widget_shows_self_sufficiency_stats(): void
    {
        $this->actingAs($this->admin())
            ->get('/yonetim')
            ->assertOk()
            ->assertSee('Son Yedek')
            ->assertSee('Boş Disk')
            ->assertSee('E-posta');
    }
}
