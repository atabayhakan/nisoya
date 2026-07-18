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
 * Moderatör yetki modeli: içerik moderasyonu yapabilir ama hesap yönetimi /
 * site konfigürasyonu / monetizasyon / hassas sayfalara ERİŞEMEZ.
 *
 * KRİTİK regresyon: Moderatör, Kullanıcılar kaynağından bir Admin'in kaydını
 * açıp parolasını değiştirip hesabı ele geçirebiliyordu (proaktif inceleme
 * bulgusu). Kaynak-seviyesi kilit (RestrictsToAdmins) bunu kapatır.
 */
class ModeratorAccessTest extends TestCase
{
    use RefreshDatabase;

    /** Admin-only kaynak/sayfa URL'leri (moderatör 403, admin 200). */
    private const ADMIN_ONLY = [
        '/yonetim/users',
        '/yonetim/zones',
        '/yonetim/navigation-links',
        '/yonetim/job-feature-requests',
        '/yonetim/currencies',
        '/yonetim/countries',
        '/yonetim/categories',
        '/yonetim/tasarim-ayarlari',
        '/yonetim/activity-log',
    ];

    /** İçerik moderasyonu kaynakları (moderatör 200). */
    private const CONTENT = [
        '/yonetim/listings',
        '/yonetim/reports',
        '/yonetim/reviews',
    ];

    private function moderator(): User
    {
        return User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_moderator_is_forbidden_on_all_admin_only_areas(): void
    {
        $moderator = $this->moderator();

        foreach (self::ADMIN_ONLY as $url) {
            $this->actingAs($moderator)->get($url)
                ->assertForbidden();
        }
    }

    public function test_admin_can_access_all_admin_only_areas(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
        $admin = $this->admin();

        foreach (self::ADMIN_ONLY as $url) {
            $this->actingAs($admin)->get($url)
                ->assertOk();
        }
    }

    public function test_moderator_can_access_content_moderation_areas(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
        $moderator = $this->moderator();

        foreach (self::CONTENT as $url) {
            $this->actingAs($moderator)->get($url)
                ->assertOk();
        }
    }

    public function test_moderator_cannot_reach_user_edit_or_create_takeover_vector(): void
    {
        // Hesap ele geçirme vektörü: moderatör bir Admin'in edit sayfasını açıp
        // (kilitli olmayan) parola alanını değiştirememeli → sayfa 403.
        $admin = $this->admin();
        $moderator = $this->moderator();

        $this->actingAs($moderator)->get("/yonetim/users/{$admin->id}/edit")->assertForbidden();
        $this->actingAs($moderator)->get('/yonetim/users/create')->assertForbidden();
    }
}
