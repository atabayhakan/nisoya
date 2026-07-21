<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_all_resource_pages(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $pages = [
            '/yonetim',
            '/yonetim/categories',
            '/yonetim/categories/create',
            '/yonetim/listings',
            '/yonetim/listings/create',
            '/yonetim/users',
            '/yonetim/currencies',
            '/yonetim/countries',
            '/yonetim/reports',
            '/yonetim/reviews',
            '/yonetim/tags',
            '/yonetim/zones',
            '/yonetim/navigation-links',
            '/yonetim/navigation-links/create',
            '/yonetim/company-reviews',
            '/yonetim/company-reviews/create',
            '/yonetim/job-feature-requests',
            '/yonetim/job-feature-requests/create',
        ];

        foreach ($pages as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
    }

    public function test_member_cannot_access_admin_panel(): void
    {
        $member = User::factory()->create([
            'role' => UserRole::Uye,
            'email_verified_at' => now(),
        ]);

        // Yetkisiz üye çıplak 403 yerine kendi paneline yönlendirilir (dostça UX;
        // çıkış yapılmaz). Bkz. bootstrap/app.php withExceptions.
        $this->actingAs($member)->get('/yonetim')->assertRedirect(route('dashboard'));
    }

    public function test_reference_data_is_seeded(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);

        $this->assertDatabaseHas('currencies', ['code' => 'EUR']);
        $this->assertDatabaseMissing('currencies', ['code' => 'TRY']); // TL olmamalı
        $this->assertDatabaseHas('countries', ['code' => 'DE', 'name_tr' => 'Almanya']);
        $this->assertDatabaseHas('categories', ['slug' => 'egitim-ders', 'parent_id' => null]);
    }

    /**
     * Denetim #7: her Resource/Page yalnızca 6 kanonik SaaS grubundan birini
     * kullanmalı; eski/yazım-hatalı grup adları sidebar'da yinelenen kopuk
     * gruplar yaratır ve panel provider'ın sabitlediği sırayı bozar.
     */
    public function test_every_admin_navigation_group_is_canonical(): void
    {
        $canonical = [
            'Pazaryeri & Ticaret',
            'İş & Kariyer Portalı',
            'Topluluk & Etkinlikler',
            'Kullanıcılar & Güvenlik',
            'İçerik & Tasarım (CMS)',
            'Sistem & Araçlar',
        ];

        $panel = Filament::getPanel('admin');
        $offenders = [];

        foreach ([...$panel->getResources(), ...$panel->getPages()] as $class) {
            $group = $class::getNavigationGroup();

            if ($group !== null && ! in_array((string) $group, $canonical, true)) {
                $offenders[$class] = (string) $group;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Kanonik olmayan navigation grupları: '.json_encode($offenders, JSON_UNESCAPED_UNICODE)
        );
    }
}
