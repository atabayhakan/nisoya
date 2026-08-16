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
            '/yonetim/outreach-targets',
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
        // TL uzun süre BİLİNÇLİ OLARAK yoktu (platform yurtdışı odaklı).
        // 2026-08-16'da KKTC eklenmesiyle TEK istisna: TRY artık var, ama
        // yalnız KKTC (XN) için — bkz. CurrencySeeder üstündeki not.
        $this->assertDatabaseHas('currencies', ['code' => 'TRY']);
        $this->assertDatabaseHas('countries', ['code' => 'DE', 'name_tr' => 'Almanya']);
        $this->assertDatabaseHas('categories', ['slug' => 'egitim-ders', 'parent_id' => null]);
    }

    /**
     * Denetim #7: her Resource/Page yalnızca 8 kanonik gruptan birini
     * kullanmalı; eski/yazım-hatalı grup adları sidebar'da yinelenen kopuk
     * gruplar yaratır ve panel provider'ın sabitlediği sırayı bozar.
     *
     * 2026-07-30: "Pazarlama & Büyüme" ve "Kâhya & Yapay Zekâ" grupları
     * açıldı, "Topluluk & Etkinlikler" → "Topluluk & İletişim" oldu.
     * Liste AdminPanelProvider::navigationGroups() ile birebir aynı olmalı.
     */
    public function test_every_admin_navigation_group_is_canonical(): void
    {
        $canonical = [
            'Pazaryeri & Ticaret',
            'İş & Kariyer Portalı',
            // Ülke-Adaptif Rehber F1 ile açıldı (2026-08-01, tasarım belgesi).
            'Ülke Rehberi',
            'Topluluk & İletişim',
            'Kullanıcılar & Güvenlik',
            'İçerik & Tasarım (CMS)',
            'Pazarlama & Büyüme',
            'Kâhya & Yapay Zekâ',
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
