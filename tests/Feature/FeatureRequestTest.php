<?php

namespace Tests\Feature;

use App\Models\FeatureRequest;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_owner_can_request_featuring(): void
    {
        $owner = User::factory()->create();
        $listing = Listing::factory()->for($owner)->create(['status' => 'aktif']);

        $this->actingAs($owner)
            ->post("/panel/ilan/{$listing->id}/one-cikar", ['days' => 7])
            ->assertRedirect();

        $this->assertDatabaseHas('feature_requests', [
            'listing_id' => $listing->id,
            'user_id' => $owner->id,
            'days' => 7,
            'status' => 'beklemede',
        ]);
    }

    public function test_non_owner_cannot_request_featuring(): void
    {
        $listing = Listing::factory()->for(User::factory())->create(['status' => 'aktif']);

        $this->actingAs(User::factory()->create())
            ->post("/panel/ilan/{$listing->id}/one-cikar", ['days' => 7])
            ->assertForbidden();
    }

    public function test_duplicate_pending_request_is_blocked(): void
    {
        $owner = User::factory()->create();
        $listing = Listing::factory()->for($owner)->create(['status' => 'aktif']);

        $this->actingAs($owner)->post("/panel/ilan/{$listing->id}/one-cikar", ['days' => 7]);
        $this->actingAs($owner)->post("/panel/ilan/{$listing->id}/one-cikar", ['days' => 30]);

        $this->assertSame(1, FeatureRequest::where('listing_id', $listing->id)->count());
    }

    public function test_admin_approval_features_the_listing(): void
    {
        $listing = Listing::factory()->for(User::factory())->create(['status' => 'aktif', 'is_featured' => false]);
        $request = FeatureRequest::create([
            'listing_id' => $listing->id, 'user_id' => $listing->user_id, 'days' => 7, 'status' => 'beklemede',
        ]);

        // Admin onayı (Filament'te status değişimi)
        $request->update(['status' => 'onaylandi']);

        $listing->refresh();
        $this->assertTrue($listing->is_featured);
        $this->assertTrue($listing->featured_until->isFuture());
        $this->assertNotNull($request->fresh()->processed_at);
    }

    public function test_approving_shorter_request_does_not_shorten_existing_window(): void
    {
        $listing = Listing::factory()->for(User::factory())->create(['status' => 'aktif', 'is_featured' => false]);

        $longRequest = FeatureRequest::create([
            'listing_id' => $listing->id, 'user_id' => $listing->user_id, 'days' => 30, 'status' => 'beklemede',
        ]);
        $longRequest->update(['status' => 'onaylandi']);
        $originalUntil = $listing->fresh()->featured_until;

        $shortRequest = FeatureRequest::create([
            'listing_id' => $listing->id, 'user_id' => $listing->user_id, 'days' => 7, 'status' => 'beklemede',
        ]);
        $shortRequest->update(['status' => 'onaylandi']);

        $this->assertTrue($listing->fresh()->featured_until->equalTo($originalUntil));
    }

    public function test_expire_command_unfeatures_expired_listings(): void
    {
        $expired = Listing::factory()->for(User::factory())->create([
            'is_featured' => true, 'featured_until' => now()->subDay(),
        ]);
        $active = Listing::factory()->for(User::factory())->create([
            'is_featured' => true, 'featured_until' => now()->addDays(5),
        ]);

        $this->artisan('listings:expire-featured')->assertSuccessful();

        $this->assertFalse($expired->fresh()->is_featured);
        $this->assertTrue($active->fresh()->is_featured);
    }

    public function test_is_currently_featured_respects_expiry(): void
    {
        $future = Listing::factory()->make(['is_featured' => true, 'featured_until' => now()->addDay()]);
        $past = Listing::factory()->make(['is_featured' => true, 'featured_until' => now()->subDay()]);

        $this->assertTrue($future->isCurrentlyFeatured());
        $this->assertFalse($past->isCurrentlyFeatured());
    }
}
