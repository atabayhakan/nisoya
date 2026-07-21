<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2026-07-22 denetimi son temizlik: averageRating ölü-kod doğruluğu +
 * öne çıkan sıralamasının süresi dolanları üstte tutmaması.
 */
class FinalCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_average_rating_excludes_hidden_reviews(): void
    {
        $company = Company::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Acme',
            'slug' => 'acme-rating',
        ]);

        CompanyReview::create(['company_id' => $company->id, 'reviewer_id' => User::factory()->create()->id, 'rating' => 5, 'status' => 'yayinda']);
        CompanyReview::create(['company_id' => $company->id, 'reviewer_id' => User::factory()->create()->id, 'rating' => 1, 'status' => 'gizli']);

        // Yalnızca yayında olan (5) sayılmalı; admin'in gizlediği (1) ortalamayı kirletmemeli.
        $this->assertSame(5.0, $company->averageRating());
    }

    public function test_order_by_featured_does_not_pin_expired_featured(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);

        $user = User::factory()->create();
        $categoryId = Category::query()->value('id');

        // created_at açıkça set edilir (aynı test-saniyesinde eşit olmasın ki
        // tier içi latest() sıralaması deterministik olsun).
        $make = function (array $attr, string $createdAt) use ($user, $categoryId): Listing {
            $listing = Listing::factory()->create(array_merge([
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'status' => ListingStatus::Aktif,
            ], $attr));
            $listing->forceFill(['created_at' => $createdAt])->saveQuietly();

            return $listing;
        };

        $expired = $make(['is_featured' => true, 'featured_until' => now()->subDay()], now()->subMinutes(3)->toDateTimeString());
        $fresh = $make(['is_featured' => false, 'featured_until' => null], now()->subMinute()->toDateTimeString());
        $current = $make(['is_featured' => true, 'featured_until' => now()->addDay()], now()->subMinutes(2)->toDateTimeString());

        $ordered = Listing::query()->orderByFeatured()->latest()->pluck('id')->all();

        // Süresi geçmemiş öne çıkan en üstte.
        $this->assertSame($current->id, $ordered[0]);
        // Süresi dolan öne çıkan artık üste sabitlenmiyor — daha yeni ama öne
        // çıkmayan "fresh" ilan onun ÜSTÜNDE sıralanır (eski davranışta expired
        // is_featured=1 olduğu için fresh'in üstüne sabitleniyordu).
        $this->assertLessThan(
            array_search($expired->id, $ordered),
            array_search($fresh->id, $ordered)
        );
    }
}
