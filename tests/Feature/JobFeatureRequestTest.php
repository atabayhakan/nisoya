<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\JobFeatureRequest;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobFeatureRequestTest extends TestCase
{
    use RefreshDatabase;

    /** İşveren + şirket + aktif iş ilanı üretir. */
    protected function jobListingFixture(): object
    {
        $unique = uniqid();
        $employer = User::factory()->create();
        $company = Company::create(['user_id' => $employer->id, 'name' => 'Acme GmbH', 'slug' => "acme-gmbh-{$unique}"]);
        $job = $company->jobListings()->create([
            'title' => 'Öne çıkarma ilanı', 'slug' => "one-cikarma-ilani-{$unique}", 'description' => 'Açıklama.',
            'employment_type' => 'tam_zamanli', 'status' => JobStatus::Aktif->value, 'positions' => 1,
        ]);

        return (object) ['employer' => $employer, 'company' => $company, 'job' => $job];
    }

    public function test_owner_can_request_featuring(): void
    {
        $fixture = $this->jobListingFixture();

        $this->actingAs($fixture->employer)
            ->post("/panel/is-ilani/{$fixture->job->id}/one-cikar", ['days' => 7])
            ->assertRedirect();

        $this->assertDatabaseHas('job_feature_requests', [
            'job_listing_id' => $fixture->job->id,
            'user_id' => $fixture->employer->id,
            'days' => 7,
            'status' => 'beklemede',
        ]);
    }

    public function test_non_owner_cannot_request_featuring(): void
    {
        $fixture = $this->jobListingFixture();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->post("/panel/is-ilani/{$fixture->job->id}/one-cikar", ['days' => 7])
            ->assertForbidden();
    }

    public function test_duplicate_pending_request_is_blocked(): void
    {
        $fixture = $this->jobListingFixture();

        $this->actingAs($fixture->employer)->post("/panel/is-ilani/{$fixture->job->id}/one-cikar", ['days' => 7]);
        $this->actingAs($fixture->employer)->post("/panel/is-ilani/{$fixture->job->id}/one-cikar", ['days' => 30]);

        $this->assertSame(1, JobFeatureRequest::where('job_listing_id', $fixture->job->id)->count());
    }

    public function test_admin_approval_features_the_job_listing(): void
    {
        $fixture = $this->jobListingFixture();
        $request = JobFeatureRequest::create([
            'job_listing_id' => $fixture->job->id, 'user_id' => $fixture->employer->id, 'days' => 7, 'status' => 'beklemede',
        ]);

        // Admin onayı (Filament'te status değişimi)
        $request->update(['status' => 'onaylandi']);

        $fixture->job->refresh();
        $this->assertTrue($fixture->job->is_featured);
        $this->assertTrue($fixture->job->featured_until->isFuture());
        $this->assertNotNull($request->fresh()->processed_at);
    }

    public function test_approving_shorter_request_does_not_shorten_existing_window(): void
    {
        $fixture = $this->jobListingFixture();

        $longRequest = JobFeatureRequest::create([
            'job_listing_id' => $fixture->job->id, 'user_id' => $fixture->employer->id, 'days' => 30, 'status' => 'beklemede',
        ]);
        $longRequest->update(['status' => 'onaylandi']);
        $originalUntil = $fixture->job->fresh()->featured_until;

        $shortRequest = JobFeatureRequest::create([
            'job_listing_id' => $fixture->job->id, 'user_id' => $fixture->employer->id, 'days' => 7, 'status' => 'beklemede',
        ]);
        $shortRequest->update(['status' => 'onaylandi']);

        $this->assertTrue($fixture->job->fresh()->featured_until->equalTo($originalUntil));
    }

    public function test_expire_command_unfeatures_expired_job_listings(): void
    {
        $expiredFixture = $this->jobListingFixture();
        $expiredFixture->job->update(['is_featured' => true, 'featured_until' => now()->subDay()]);

        $activeFixture = $this->jobListingFixture();
        $activeFixture->job->update(['is_featured' => true, 'featured_until' => now()->addDays(5)]);

        $this->artisan('job-listings:expire-featured')->assertSuccessful();

        $this->assertFalse($expiredFixture->job->fresh()->is_featured);
        $this->assertTrue($activeFixture->job->fresh()->is_featured);
    }

    public function test_is_currently_featured_respects_expiry(): void
    {
        $future = new JobListing(['is_featured' => true, 'featured_until' => now()->addDay()]);
        $past = new JobListing(['is_featured' => true, 'featured_until' => now()->subDay()]);

        $this->assertTrue($future->isCurrentlyFeatured());
        $this->assertFalse($past->isCurrentlyFeatured());
    }
}
