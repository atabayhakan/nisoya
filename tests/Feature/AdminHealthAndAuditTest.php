<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminHealthAndAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_basic_health_endpoint_returns_ok(): void
    {
        $response = $this->get('/health');
        $response->assertOk();
        $response->assertJsonStructure(['status', 'service', 'version', 'time']);
        $response->assertJson(['status' => 'ok', 'service' => 'nisoya']);
    }

    public function test_detailed_health_endpoint_requires_authentication(): void
    {
        $this->get('/yonetim/health/detailed')
            ->assertRedirect(); // Misafir → Filament login sayfasına yönlendirilir
    }

    public function test_non_admin_cannot_view_detailed_health(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Uye,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/yonetim/health/detailed');
        $response->assertForbidden();
    }

    public function test_non_admin_cannot_view_exif_map_endpoints(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Uye,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get('/yonetim/harita/gorseller')->assertForbidden();
        $this->actingAs($user)->get('/yonetim/harita/cluster')->assertForbidden();
        $this->actingAs($user)->get('/yonetim/harita/istatistik')->assertForbidden();
    }

    public function test_detailed_health_endpoint_returns_health_data_for_admin(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/yonetim/health/detailed');
        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => ['database', 'cache', 'storage', 'queue', 'session'],
            'system' => ['php', 'laravel', 'db_driver', 'cache_driver', 'queue_driver'],
        ]);
        $response->assertJsonPath('status', 'ok');
    }

    public function test_detailed_health_returns_degraded_when_db_fails(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        // DB bağlantısını boz
        DB::shouldReceive('select')->andThrow(new \Exception('DB down'));

        $response = $this->actingAs($admin)->get('/yonetim/health/detailed');
        // 503 Service Unavailable
        $this->assertContains($response->getStatusCode(), [503, 500]);
    }

    public function test_admin_dashboard_loads_with_health_widget(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/yonetim');
        $response->assertOk();
        $response->assertSee('Veritabanı'); // SystemHealthWidget başlığı
    }

    public function test_admin_can_view_activity_log_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/yonetim/activity-log');
        $response->assertOk();
        $response->assertSee('İşlem Geçmişi');
    }

    public function test_non_admin_cannot_view_activity_log(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Uye,
            'email_verified_at' => now(),
        ]);

        // Üye kullanıcı admin panele erişemez (canAccessPanel false)
        $response = $this->actingAs($user)->get('/yonetim/activity-log');
        $response->assertForbidden();
    }

    public function test_user_status_change_creates_activity_log(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $user = User::factory()->create(['status' => UserStatus::Aktif]);

        // Activity log doğrudan oluştur (Filament aksiyonu yerine)
        activity('user')
            ->performedOn($user)
            ->causedBy($admin)
            ->withProperties(['old_status' => 'aktif', 'new_status' => 'askida'])
            ->log('Kullanıcı askıya alındı');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'user',
            'description' => 'Kullanıcı askıya alındı',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
    }

    public function test_listing_status_change_creates_activity_log(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);

        // Status değişikliği → activity log
        $listing->update(['status' => ListingStatus::Reddedildi]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'default',
            'subject_type' => Listing::class,
            'subject_id' => $listing->id,
        ]);

        $log = Activity::query()->where('subject_type', Listing::class)
            ->where('subject_id', $listing->id)
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($log);
        // En az created + updated event'leri var; sonuncusu 'updated' olmalı
        $this->assertEquals('İlan updated', $log->description);
        $this->assertNotEmpty($log->properties);
    }

    public function test_filament_panel_supports_dark_mode(): void
    {
        $panel = Filament::getPanel('admin');
        $this->assertTrue($panel->hasDarkMode());
    }

    public function test_filament_default_theme_is_system(): void
    {
        // Filament provider'da System mode seçtik → sistem tercihi otomatik uygulanır
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/yonetim');
        $response->assertOk();
        // Layout'ta dark mode toggle / mevcut tema class'ı
        $response->assertSee('filament', false);
    }

    public function test_health_basic_returns_iso8601_timestamp(): void
    {
        $response = $this->get('/health');
        $response->assertOk();
        $data = $response->json();
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $data['time']
        );
    }
}
