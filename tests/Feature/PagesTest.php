<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_static_pages_load(): void
    {
        $pages = ['/nasil-calisir', '/hakkimizda', '/iletisim', '/kosullar', '/gizlilik', '/sss'];

        foreach ($pages as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_sitemap_renders_xml(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
        Listing::factory()->for(User::factory())->create(['status' => 'aktif']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<urlset', false)
            ->assertSee('/ilanlar', false);
    }

    public function test_robots_txt_file_exists(): void
    {
        // robots.txt artık statik dosya (nginx tarafından sunulur), Laravel route'u değil.
        $path = public_path('robots.txt');
        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('Sitemap:', $content);
        $this->assertStringContainsString('Disallow: /panel/', $content);
    }
}
