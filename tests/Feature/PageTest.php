<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\UserRole;
use App\Models\Page;
use App\Models\SssSorusu;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\SssSorulariSeeder;
use Database\Seeders\StaticPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_page_is_visible(): void
    {
        Page::create([
            'title' => 'Test Sayfa',
            'slug' => 'test-sayfa',
            'status' => PageStatus::Yayin->value,
            'blocks' => [
                ['type' => 'baslik', 'data' => ['text' => 'Bölüm Başlığı', 'level' => 'h2']],
                ['type' => 'metin', 'data' => ['content' => '<p>Merhaba dünya içeriği.</p>']],
            ],
        ]);

        $this->get('/test-sayfa')
            ->assertOk()
            ->assertSee('Test Sayfa')
            ->assertSee('Bölüm Başlığı')
            ->assertSee('Merhaba dünya içeriği.');
    }

    public function test_draft_page_returns_404(): void
    {
        Page::create(['title' => 'Gizli', 'slug' => 'gizli', 'status' => PageStatus::Taslak->value]);

        $this->get('/gizli')->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/boyle-bir-sayfa-yok')->assertNotFound();
    }

    public function test_catch_all_does_not_shadow_existing_routes(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);

        $this->get('/')->assertOk();
        $this->get('/ilanlar')->assertOk();
        $this->get('/giris')->assertOk();
    }

    public function test_cta_and_divider_blocks_render(): void
    {
        Page::create([
            'title' => 'Blok Testi',
            'slug' => 'blok-testi',
            'status' => PageStatus::Yayin->value,
            'blocks' => [
                ['type' => 'ayrac', 'data' => []],
                ['type' => 'cta', 'data' => ['title' => 'Hemen katıl', 'button_text' => 'Kayıt ol', 'button_url' => '/kayit']],
            ],
        ]);

        $this->get('/blok-testi')
            ->assertOk()
            ->assertSee('Hemen katıl')
            ->assertSee('Kayıt ol');
    }

    public function test_migrated_corporate_pages_are_seeded_and_visible(): void
    {
        // SSS 2026-08-25: içerik artık SssSorusu'da (bkz. PageController::show()),
        // StaticPagesSeeder yalnız footer/meta kaydını taşır.
        $this->seed([StaticPagesSeeder::class, SssSorulariSeeder::class]);

        $this->get('/hakkimizda')->assertOk()->assertSee('Hakkımızda')->assertSee('Ne İş Olursa Yaparız');
        $this->get('/sss')->assertOk()->assertSee('Nisoya ücretli mi');
        $this->get('/kosullar')->assertOk()->assertSee('Platformun niteliği');

        $this->assertSame(4, Page::navPages()->count());
    }

    public function test_sss_sayfasi_bosken_de_hatasiz_acilir(): void
    {
        // SssSorusu hiç seed edilmedi — Page(slug=sss) kaydı yine de var
        // olmalı (footer/meta) ama içerik listesi boş.
        $this->seed(StaticPagesSeeder::class);

        $this->get('/sss')->assertOk()->assertSee('Şu an yayında bir soru yok');
    }

    public function test_sss_sayfasi_pasif_soruyu_gostermez(): void
    {
        $this->seed([StaticPagesSeeder::class, SssSorulariSeeder::class]);

        SssSorusu::query()->update(['is_active' => false]);
        SssSorusu::query()->create([
            'soru' => 'Aktif tek soru',
            'cevap' => 'Aktif tek cevap',
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $this->get('/sss')
            ->assertOk()
            ->assertSee('Aktif tek soru')
            ->assertDontSee('Nisoya ücretli mi');
    }

    public function test_reserved_slugs_are_defined(): void
    {
        $this->assertContains('ilanlar', Page::RESERVED_SLUGS);
        $this->assertContains('panel', Page::RESERVED_SLUGS);
    }

    public function test_navpages_cache_refreshes_on_save(): void
    {
        Page::create(['title' => 'A', 'slug' => 'sayfa-a', 'status' => PageStatus::Yayin->value, 'show_in_footer' => true]);
        $this->assertCount(1, Page::navPages());

        Page::create(['title' => 'B', 'slug' => 'sayfa-b', 'status' => PageStatus::Yayin->value, 'show_in_footer' => true]);
        $this->assertCount(2, Page::navPages());
    }

    public function test_admin_can_open_pages_resource(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        $this->actingAs($admin)->get('/yonetim/pages')->assertOk();
        $this->actingAs($admin)->get('/yonetim/pages/create')->assertOk();
    }

    public function test_member_cannot_open_pages_resource(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)->get('/yonetim/pages')->assertRedirect(route('dashboard'));
    }
}
