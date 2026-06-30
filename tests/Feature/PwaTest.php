<?php

namespace Tests\Feature;

use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_offline_page_loads(): void
    {
        $this->get('/offline')->assertOk()->assertSee('Çevrimdışısın');
    }

    public function test_manifest_is_valid(): void
    {
        $path = public_path('manifest.webmanifest');
        $this->assertFileExists($path);

        $manifest = json_decode(file_get_contents($path), true);
        $this->assertSame('Nisoya', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['icons']);
    }

    public function test_pwa_assets_exist(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('icons/icon-192.png'));
        $this->assertFileExists(public_path('icons/icon-512.png'));
        $this->assertFileExists(public_path('icons/icon-maskable.png'));
    }

    public function test_layout_links_manifest_and_service_worker(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('/sw.js', false)
            ->assertSee('theme-color', false);
    }
}
