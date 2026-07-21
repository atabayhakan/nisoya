<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Faz 4 · İşlem günlüğü zenginleştirme — panelden yapılan ayar değişiklikleri
 * denetim izine (activity_log) yazılır.
 */
class SettingsAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_setting_change_is_logged_when_authenticated(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Settings::setMany(['mail.host' => 'smtp.test.com']);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'ayar',
            'causer_id' => $admin->id,
            'description' => 'E-posta (SMTP) ayarları güncellendi',
        ]);
    }

    public function test_description_derives_multiple_areas(): void
    {
        $this->actingAs($this->admin());

        Settings::setMany(['modul.emlak' => '1', 'seo.default_title' => 'X']);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'ayar',
            'description' => 'Modüller, SEO ayarları güncellendi',
        ]);
    }

    public function test_change_is_not_logged_without_authenticated_user(): void
    {
        // Seeder/console/test bağlamı (giriş yok) → denetim kaydı yok.
        Settings::setMany(['modul.emlak' => '0']);

        $this->assertDatabaseMissing('activity_log', ['log_name' => 'ayar']);
    }

    public function test_activity_log_page_shows_settings_change(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Settings::setMany(['seo.default_title' => 'Yeni']);

        $this->get('/yonetim/activity-log')
            ->assertOk()
            ->assertSee('SEO ayarları güncellendi');
    }
}
