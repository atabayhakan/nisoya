<?php

namespace Tests\Feature;

use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\JobCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Faz İ4: paylaşılan x-empty-state bileşeni — 13 sayfada tekrar eden
 * emoji/heroicon karışımı yerine tek, özel çizilmiş SVG illüstrasyon seti.
 */
class EmptyStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, JobCategorySeeder::class]);
    }

    public function test_candidates_empty_state_renders_search_illustration_without_cta(): void
    {
        $response = $this->get('/adaylar')->assertOk();

        $response->assertSee('Kimse bulunamadı')
            ->assertSee('viewBox="0 0 120 90"', false);
    }

    public function test_listings_empty_state_renders_with_cta_link(): void
    {
        $response = $this->get('/ilanlar?q=hicbirsonucyokolmali123')->assertOk();

        $response->assertSee('Sonuç bulunamadı')
            ->assertSee('Tüm ilanları gör')
            ->assertSee('viewBox="0 0 120 90"', false);
    }
}
