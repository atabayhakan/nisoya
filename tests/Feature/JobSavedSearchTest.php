<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\JobSavedSearch;
use App\Models\User;
use App\Notifications\JobSavedSearchAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JobSavedSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_job_search(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/panel/is-arama-kaydet', [
            'q' => 'Aşçı', 'ulke' => 'DE', 'tip' => 'tam_zamanli',
        ])->assertRedirect(route('panel.job-saved-searches.index'));

        $this->assertDatabaseHas('job_saved_searches', ['user_id' => $user->id, 'q' => 'Aşçı', 'ulke' => 'DE']);
    }

    public function test_empty_job_search_is_not_saved(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/panel/is-arama-kaydet', []);
        $this->assertDatabaseCount('job_saved_searches', 0);
    }

    public function test_duplicate_job_search_not_saved_twice(): void
    {
        $user = User::factory()->create();
        $payload = ['q' => 'tekrar', 'ulke' => 'NL'];
        $this->actingAs($user)->post('/panel/is-arama-kaydet', $payload);
        $this->actingAs($user)->post('/panel/is-arama-kaydet', $payload);
        $this->assertSame(1, JobSavedSearch::where('user_id', $user->id)->count());
    }

    public function test_user_can_delete_own_job_saved_search_only(): void
    {
        $owner = User::factory()->create();
        $search = JobSavedSearch::create(['user_id' => $owner->id, 'label' => 'Test', 'ulke' => 'DE']);

        $this->actingAs(User::factory()->create())->delete("/panel/is-aramalarim/{$search->id}")->assertForbidden();
        $this->actingAs($owner)->delete("/panel/is-aramalarim/{$search->id}")->assertRedirect();
        $this->assertDatabaseMissing('job_saved_searches', ['id' => $search->id]);
    }

    public function test_alert_command_emails_matching_new_job_listings(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $search = JobSavedSearch::create([
            'user_id' => $user->id, 'label' => 'Almanya', 'ulke' => 'DE',
            'last_notified_at' => now()->subDay(),
        ]);
        $employer = User::factory()->create();
        $company = Company::create(['user_id' => $employer->id, 'name' => 'Acme GmbH', 'slug' => 'acme-gmbh']);
        $company->jobListings()->create([
            'title' => 'Aşçı aranıyor', 'slug' => 'asci-araniyor', 'description' => 'Açıklama.',
            'employment_type' => 'tam_zamanli', 'status' => JobStatus::Aktif->value, 'positions' => 1,
            'country_code' => 'DE',
        ]);

        $this->artisan('job-alerts:saved-searches')->assertSuccessful();

        Notification::assertSentTo($user, JobSavedSearchAlert::class);
        $this->assertTrue($search->fresh()->last_notified_at->isToday());
    }
}
