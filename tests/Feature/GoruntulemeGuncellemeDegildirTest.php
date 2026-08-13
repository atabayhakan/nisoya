<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GÖRÜNTÜLEME, GÜNCELLEME DEĞİLDİR.
 *
 * ---------------------------------------------------------------------------
 * CANLIDA BULUNDU (2026-08-13)
 *
 * Üç ilanın üçü de "3 dakika önce güncellendi" gösteriyordu. Satıcı hiçbir
 * şey değiştirmemişti; tek sebep sayfaların AÇILMASIYDI. Düz `increment()`
 * zaman damgasını da güncelliyor.
 *
 * İki yerde birden yalan söylüyordu:
 *   1. Vitrin ilan detayı: "X önce güncellendi" — aslında "X önce biri baktı".
 *      Pazaryerinde tazelik bir güven sinyali; yanlış olması sinyali bozar.
 *   2. Site haritası `lastmod`: her ziyaret arama motoruna "içerik değişti"
 *      diyordu. İçerik değişmeden sürekli tazelenen lastmod, tarayıcı
 *      güvenini aşındırır.
 *
 * Sayaç ARTMAYA devam etmeli — bu test onu da ölçüyor, aksi hâlde
 * "artırmayı tamamen kaldır" gibi bir düzeltme de testi geçerdi.
 */
class GoruntulemeGuncellemeDegildirTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CountrySeeder::class, CurrencySeeder::class, CategorySeeder::class]);
        config(['ai.features.text_moderation' => false]);
    }

    public function test_ilan_goruntulemesi_updated_at_i_degistirmiyor(): void
    {
        $kategori = Category::query()->whereNotNull('parent_id')->where('type', 'hizmet')->firstOrFail();

        $ilan = Listing::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => $kategori->id,
            'type' => 'hizmet',
            'status' => ListingStatus::Aktif,
            'is_demo' => false,
        ]);

        $ilan->forceFill(['updated_at' => now()->subDays(30)])->saveQuietly();
        $once = $ilan->fresh()->updated_at;

        $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk();

        $taze = $ilan->fresh();

        $this->assertEquals($once, $taze->updated_at,
            'Sayfayı açmak ilanı "güncellendi" göstermiş — ziyaretçiye ve arama motoruna yanlış bilgi.');
        $this->assertSame(1, $taze->views_count,
            'Sayaç artmamış — düzeltme, sayacı da kapatmış olur.');
    }

    public function test_is_ilani_goruntulemesi_updated_at_i_degistirmiyor(): void
    {
        $isveren = User::factory()->create();
        $sirket = Company::create(['user_id' => $isveren->id, 'name' => 'Acme GmbH', 'slug' => 'acme-gmbh']);

        $ilan = $sirket->jobListings()->create([
            'title' => 'Aşçı aranıyor',
            'slug' => 'asci-araniyor',
            'description' => 'Deneyimli aşçı arıyoruz.',
            'employment_type' => 'tam_zamanli',
            'status' => JobStatus::Aktif->value,
            'positions' => 1,
        ]);

        $ilan->forceFill(['updated_at' => now()->subDays(30)])->saveQuietly();
        $once = $ilan->fresh()->updated_at;

        $this->get(route('jobs.show', [$ilan, $ilan->slug]))->assertOk();

        $taze = $ilan->fresh();

        $this->assertEquals($once, $taze->updated_at);
        $this->assertSame(1, $taze->views_count);
    }
}
