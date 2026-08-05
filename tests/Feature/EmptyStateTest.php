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
        // 2026-08-05: metin değişti çünkü ESKİSİ YANLIŞ TAVSİYE VERİYORDU.
        // Havuz gerçekten boşken "filtreleri değiştirmeyi dene" diyordu; oysa
        // filtre yoktu, kimse profilini doldurmamıştı. Bileşen sözleşmesi
        // (SVG illüstrasyon) aynen korunuyor.
        $response = $this->get('/adaylar')->assertOk();

        $response->assertSee('Yetenek havuzu henüz boş')
            ->assertSee('viewBox="0 0 120 90"', false);
    }

    public function test_listings_empty_state_renders_with_cta_link(): void
    {
        // Filtre UYGULANMIŞ durum: "temizle" gerçek bir ilerleme sağlar.
        // Eski "Tüm ilanları gör" düğmesi filtre YOKKEN kendi sayfasına
        // dönüyordu; artık iki durum ayrı ele alınıyor.
        $response = $this->get('/ilanlar?q=hicbirsonucyokolmali123')->assertOk();

        $response->assertSee('Bu filtrelerle sonuç yok')
            ->assertSee('Filtreleri temizle')
            ->assertSee('viewBox="0 0 120 90"', false);
    }

    public function test_listings_empty_state_without_filters_calls_for_supply(): void
    {
        // Filtre YOKKEN çağrı arz tarafına olmalı — kullanıcıyı olduğu
        // sayfaya geri gönderen bir düğme hiçbir ilerleme sağlamıyordu.
        $this->get('/ilanlar')->assertOk()
            ->assertSee('Henüz ilan yok')
            ->assertSee('İlan ver')
            ->assertDontSee('Tüm ilanları gör');
    }
}
