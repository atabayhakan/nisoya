<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tranş C veri bütünlüğü düzeltmeleri (harici inceleme #M4).
 */
class TranchCDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_performance_indexes_exist(): void
    {
        $this->assertTrue(Schema::hasIndex('listings', 'idx_listings_coords'));
        $this->assertTrue(Schema::hasIndex('job_listings', 'idx_job_listings_coords'));
        $this->assertTrue(Schema::hasIndex('job_listings', 'idx_job_listings_slug'));
    }

    public function test_user_cannot_own_two_companies(): void
    {
        $user = User::factory()->create();

        Company::create(['user_id' => $user->id, 'name' => 'Şirket 1', 'slug' => 'sirket-1']);

        $this->expectException(QueryException::class);
        Company::create(['user_id' => $user->id, 'name' => 'Şirket 2', 'slug' => 'sirket-2']);
    }

    public function test_conversation_participants_are_normalized_and_deduplicated(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
        $a = User::factory()->create();
        $b = User::factory()->create();
        [$low, $high] = [min($a->id, $b->id), max($a->id, $b->id)];

        $owner = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $owner->id,
            'category_id' => Category::first()->id,
        ]);

        // Ters sırayla çağır — yine de normalize edilmeli (user_one=küçük).
        $c1 = Conversation::findOrCreateBetween($high, $low, $listing->id);
        $this->assertSame($low, $c1->user_one_id);
        $this->assertSame($high, $c1->user_two_id);

        // Diğer yön aynı konuşmayı döndürmeli (çift satır oluşmamalı).
        $c2 = Conversation::findOrCreateBetween($low, $high, $listing->id);
        $this->assertSame($c1->id, $c2->id);
        $this->assertSame(1, Conversation::where('listing_id', $listing->id)->count());
    }

    public function test_normalized_pair_unique_index_blocks_swapped_duplicate(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
        $a = User::factory()->create();
        $b = User::factory()->create();
        [$low, $high] = [min($a->id, $b->id), max($a->id, $b->id)];

        $owner = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $owner->id,
            'category_id' => Category::first()->id,
        ]);

        Conversation::create([
            'listing_id' => $listing->id,
            'user_one_id' => $low,
            'user_two_id' => $high,
            'last_message_at' => now(),
        ]);

        // Aynı normalize edilmiş çift (ters yazılsa bile aynı satıra denk gelir)
        // ikinci kez EKLENEMEZ — unique index reddeder.
        $this->expectException(QueryException::class);
        Conversation::create([
            'listing_id' => $listing->id,
            'user_one_id' => $low,
            'user_two_id' => $high,
            'last_message_at' => now(),
        ]);
    }
}
