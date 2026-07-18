<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_conservative_csp_present_on_html_pages(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);

        $csp = $this->get('/')->assertOk()->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        // script-src/style-src KISITLANMAMALI (uygulama inline script/Alpine
        // kullanıyor — kısıtlamak siteyi kırardı).
        $this->assertStringNotContainsString('script-src', $csp);
        $this->assertStringNotContainsString('default-src', $csp);
    }

    public function test_csp_present_on_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        $csp = $this->actingAs($admin)->get('/yonetim')->assertOk()
            ->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'self'", (string) $csp);
    }
}
