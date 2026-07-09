<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;
use App\Notifications\NewApplicationNotification;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * İş ilanları modülü: şirket profili, iş ilanı CRUD, keşif, başvuru sistemi.
 */
class JobModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    /** İşveren + şirket + aktif iş ilanı üretir. */
    protected function employerWithJob(): array
    {
        $employer = User::factory()->create();
        $company = Company::create([
            'user_id' => $employer->id, 'name' => 'Acme GmbH', 'slug' => 'acme-gmbh',
        ]);
        $employer->update(['account_type' => AccountType::Kurumsal]);
        $job = $company->jobListings()->create([
            'title' => 'Aşçı aranıyor', 'slug' => 'asci-araniyor', 'description' => 'Deneyimli aşçı.',
            'employment_type' => 'tam_zamanli', 'status' => JobStatus::Aktif->value, 'positions' => 1,
        ]);

        return [$employer, $company, $job];
    }

    // --- Şirket profili ---

    public function test_creating_company_makes_account_corporate(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/panel/sirket', [
            'name' => 'Test Şirket',
        ])->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->isEmployer());
        $this->assertDatabaseHas('companies', ['user_id' => $user->id, 'name' => 'Test Şirket']);
        $this->assertNotNull($user->company->slug);
    }

    public function test_public_company_page_lists_active_jobs(): void
    {
        [, $company, $job] = $this->employerWithJob();

        $this->get("/sirket/{$company->slug}")
            ->assertOk()
            ->assertSee('Acme GmbH')
            ->assertSee('Aşçı aranıyor');
    }

    // --- İş ilanı CRUD ---

    public function test_user_without_company_cannot_reach_create_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/panel/is-ilani/yeni')
            ->assertRedirect(route('panel.company.edit'));
    }

    public function test_employer_can_create_job(): void
    {
        [$employer, $company] = $this->employerWithJob();

        $this->actingAs($employer)->post('/panel/is-ilani', [
            'title' => 'Garson', 'description' => 'Hafta sonu garson.', 'employment_type' => 'yari_zamanli', 'positions' => 2,
        ])->assertRedirect(route('panel.jobs.index'));

        $this->assertDatabaseHas('job_listings', ['company_id' => $company->id, 'title' => 'Garson']);
    }

    public function test_employer_cannot_edit_another_companys_job(): void
    {
        [, , $job] = $this->employerWithJob();
        $intruder = User::factory()->create();
        Company::create(['user_id' => $intruder->id, 'name' => 'Başka', 'slug' => 'baska']);
        $intruder->update(['account_type' => AccountType::Kurumsal]);

        $this->actingAs($intruder)->get("/panel/is-ilani/{$job->id}/duzenle")->assertForbidden();
    }

    // --- Keşif ---

    public function test_browse_shows_active_jobs_only(): void
    {
        [, $company, $active] = $this->employerWithJob();
        $closed = $company->jobListings()->create([
            'title' => 'Kapalı ilan', 'slug' => 'kapali', 'description' => 'x',
            'employment_type' => 'tam_zamanli', 'status' => JobStatus::Kapali->value,
        ]);

        $this->get('/isler')
            ->assertOk()
            ->assertSee('Aşçı aranıyor')
            ->assertDontSee('Kapalı ilan');
    }

    public function test_job_detail_hidden_when_not_active_for_guest(): void
    {
        [, $company] = $this->employerWithJob();
        $closed = $company->jobListings()->create([
            'title' => 'Gizli', 'slug' => 'gizli', 'description' => 'x',
            'employment_type' => 'tam_zamanli', 'status' => JobStatus::Kapali->value,
        ]);

        $this->get("/is/{$closed->id}/gizli")->assertNotFound();
    }

    public function test_wrong_or_missing_slug_redirects_to_canonical_url(): void
    {
        [, , $job] = $this->employerWithJob();

        $this->get("/is/{$job->id}")->assertRedirect("/is/{$job->id}/{$job->slug}");
        $this->get("/is/{$job->id}/yanlis-slug")->assertRedirect("/is/{$job->id}/{$job->slug}");
    }

    // --- Başvuru sistemi ---

    public function test_candidate_can_apply_and_employer_is_notified(): void
    {
        Notification::fake();
        [$employer, , $job] = $this->employerWithJob();
        $candidate = User::factory()->create();

        $this->actingAs($candidate)->post("/is/{$job->id}/basvur", [
            'cover_letter' => 'Bu iş için uygunum.',
        ])->assertRedirect();

        $this->assertDatabaseHas('job_applications', ['job_listing_id' => $job->id, 'user_id' => $candidate->id]);
        Notification::assertSentTo($employer, NewApplicationNotification::class);
    }

    public function test_candidate_cannot_apply_twice(): void
    {
        [, , $job] = $this->employerWithJob();
        $candidate = User::factory()->create();
        $job->applications()->create(['user_id' => $candidate->id, 'status' => ApplicationStatus::Gonderildi]);

        $this->actingAs($candidate)->post("/is/{$job->id}/basvur", ['cover_letter' => 'tekrar']);

        $this->assertSame(1, $job->applications()->where('user_id', $candidate->id)->count());
    }

    public function test_employer_cannot_apply_to_own_job(): void
    {
        [$employer, , $job] = $this->employerWithJob();

        $this->actingAs($employer)->post("/is/{$job->id}/basvur", ['cover_letter' => 'x']);

        $this->assertDatabaseCount('job_applications', 0);
    }

    public function test_only_owner_employer_sees_applicants(): void
    {
        [$employer, , $job] = $this->employerWithJob();
        $candidate = User::factory()->create();
        $job->applications()->create(['user_id' => $candidate->id, 'status' => ApplicationStatus::Gonderildi]);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get("/panel/is-ilani/{$job->id}/basvurular")->assertForbidden();
        $this->actingAs($employer)->get("/panel/is-ilani/{$job->id}/basvurular")->assertOk();
    }

    public function test_employer_updates_status_and_candidate_is_notified(): void
    {
        Notification::fake();
        [$employer, , $job] = $this->employerWithJob();
        $candidate = User::factory()->create();
        $app = $job->applications()->create(['user_id' => $candidate->id, 'status' => ApplicationStatus::Gonderildi]);

        $this->actingAs($employer)->patch("/panel/basvuru/{$app->id}/durum", ['status' => 'gorusme'])
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Gorusme, JobApplication::find($app->id)->status);
        Notification::assertSentTo($candidate, ApplicationStatusNotification::class);
    }

    public function test_stranger_cannot_update_application_status(): void
    {
        [, , $job] = $this->employerWithJob();
        $candidate = User::factory()->create();
        $app = $job->applications()->create(['user_id' => $candidate->id, 'status' => ApplicationStatus::Gonderildi]);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->patch("/panel/basvuru/{$app->id}/durum", ['status' => 'kabul'])
            ->assertForbidden();
    }

    // --- KVKK ---

    public function test_account_deletion_removes_company_and_applications(): void
    {
        [$employer, $company, $job] = $this->employerWithJob();
        // employer aynı zamanda başka bir ilana aday olsun
        $otherCompany = Company::create(['user_id' => User::factory()->create()->id, 'name' => 'X', 'slug' => 'x-co']);
        $otherJob = $otherCompany->jobListings()->create([
            'title' => 'y', 'slug' => 'y', 'description' => 'y', 'employment_type' => 'tam_zamanli', 'status' => 'aktif',
        ]);
        $otherJob->applications()->create(['user_id' => $employer->id, 'status' => ApplicationStatus::Gonderildi]);

        $this->actingAs($employer)->delete('/panel/profil', [
            'current_password' => 'password',
            'confirm_text' => 'HESABIMI SİL',
        ]);

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseMissing('job_listings', ['id' => $job->id]); // cascade
        $this->assertDatabaseCount('job_applications', 0);
    }
}
