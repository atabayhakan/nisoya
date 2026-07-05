<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\NavigationLink;
use App\Models\User;
use Database\Seeders\NavigationLinkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_expected_links_in_order(): void
    {
        $this->seed(NavigationLinkSeeder::class);

        $labels = NavigationLink::query()->orderBy('sort_order')->pluck('label')->all();

        $this->assertSame(['İlanlar', 'İş İlanları', 'Harita', 'Nasıl Çalışır?'], $labels);
    }

    public function test_seeder_does_not_duplicate_or_overwrite_admin_edits(): void
    {
        $this->seed(NavigationLinkSeeder::class);

        NavigationLink::where('label', 'İlanlar')->first()->update(['is_active' => false]);

        $this->seed(NavigationLinkSeeder::class);

        $this->assertSame(4, NavigationLink::count());
        $this->assertDatabaseHas('navigation_links', ['label' => 'İlanlar', 'is_active' => false]);
    }

    public function test_active_cached_excludes_inactive_and_respects_order(): void
    {
        NavigationLink::create(['label' => 'B', 'url' => '/b', 'sort_order' => 2, 'is_active' => true]);
        NavigationLink::create(['label' => 'A', 'url' => '/a', 'sort_order' => 1, 'is_active' => true]);
        NavigationLink::create(['label' => 'Gizli', 'url' => '/gizli', 'sort_order' => 0, 'is_active' => false]);

        $labels = NavigationLink::activeCached()->pluck('label')->all();

        $this->assertSame(['A', 'B'], $labels);
    }

    public function test_header_renders_links_from_database(): void
    {
        NavigationLink::create(['label' => 'ÖzelMenüLinki', 'url' => '/ozel', 'sort_order' => 1, 'is_active' => true]);

        $this->get('/')->assertOk()->assertSee('ÖzelMenüLinki');
    }

    public function test_admin_can_manage_navigation_links(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $link = NavigationLink::create(['label' => 'Test', 'url' => '/test', 'sort_order' => 1]);

        $this->actingAs($admin)->get('/yonetim/navigation-links')->assertOk();
        $this->actingAs($admin)->get('/yonetim/navigation-links/create')->assertOk();
        $this->actingAs($admin)->get("/yonetim/navigation-links/{$link->id}/edit")->assertOk();
    }

    public function test_member_cannot_access_navigation_link_admin(): void
    {
        $member = User::factory()->create([
            'role' => UserRole::Uye,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($member)->get('/yonetim/navigation-links')->assertForbidden();
    }
}
