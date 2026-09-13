<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FeatureRequestStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\JobFeatureRequests\JobFeatureRequestResource;
use App\Filament\Resources\JobListings\JobListingResource;
use App\Models\Company;
use App\Models\JobCategory;
use App\Models\JobFeatureRequest;
use App\Models\JobListing;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobFeatureRequestAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    protected function createAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
    }

    protected function createJobFixture(): array
    {
        $employer = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::create([
            'user_id' => $employer->id,
            'name' => 'Nisoya Tech GmbH',
            'slug' => 'nisoya-tech-gmbh-'.uniqid(),
        ]);
        $category = JobCategory::create([
            'name' => 'Yazılım',
            'slug' => 'yazilim-'.uniqid(),
            'is_active' => true,
        ]);
        $job = $company->jobListings()->create([
            'category_id' => $category->id,
            'title' => 'Kıdemli PHP / Laravel Geliştirici',
            'slug' => 'kidemli-php-laravel-gelistirici-'.uniqid(),
            'description' => 'Modern mimari ile çalışan kıdemli geliştirici aranıyor.',
            'employment_type' => 'tam_zamanli',
            'status' => JobStatus::Aktif->value,
            'positions' => 1,
            'is_featured' => false,
        ]);

        return [$employer, $company, $category, $job];
    }

    public function test_admin_can_access_job_feature_requests_pages(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/yonetim/job-feature-requests')
            ->assertOk()
            ->assertSee('İş İlanı Öne Çıkarma Talepleri')
            ->assertSee('Yeni Talep Ekle');

        $this->actingAs($admin)
            ->get('/yonetim/job-feature-requests/create')
            ->assertOk()
            ->assertSee('İş İlanı')
            ->assertSee('Talep Eden İşveren / Yetkili')
            ->assertSee('Öne Çıkarma Süresi');
    }

    public function test_header_and_related_module_links_resolve_valid_routes(): void
    {
        $admin = $this->createAdmin();

        // 1. Resource index URL
        $indexUrl = JobFeatureRequestResource::getUrl('index');
        $this->assertStringContainsString('/yonetim/job-feature-requests', $indexUrl);
        $this->actingAs($admin)->get($indexUrl)->assertOk();

        // 2. Resource create URL
        $createUrl = JobFeatureRequestResource::getUrl('create');
        $this->assertStringContainsString('/yonetim/job-feature-requests/create', $createUrl);
        $this->actingAs($admin)->get($createUrl)->assertOk();

        // 3. JobListing resource link
        $jobListingIndexUrl = JobListingResource::getUrl('index');
        $this->assertStringContainsString('/yonetim/job-listings', $jobListingIndexUrl);
        $this->actingAs($admin)->get($jobListingIndexUrl)->assertOk();

        // 4. Company resource link
        $companyIndexUrl = CompanyResource::getUrl('index');
        $this->assertStringContainsString('/yonetim/companies', $companyIndexUrl);
        $this->actingAs($admin)->get($companyIndexUrl)->assertOk();
    }

    public function test_public_view_job_listing_link_works(): void
    {
        [$employer, $company, , $job] = $this->createJobFixture();

        $publicUrl = route('jobs.show', [$job->id, $job->slug]);
        $response = $this->get($publicUrl);
        $response->assertOk();
        $response->assertSee($job->title);
        $response->assertSee($company->name);
    }

    public function test_approval_action_features_job_and_sets_dates(): void
    {
        [$employer, , , $job] = $this->createJobFixture();

        $request = JobFeatureRequest::create([
            'job_listing_id' => $job->id,
            'user_id' => $employer->id,
            'days' => 14,
            'status' => FeatureRequestStatus::Beklemede,
        ]);

        $this->assertFalse($job->is_featured);
        $this->assertNull($job->featured_until);
        $this->assertNull($request->processed_at);

        // Admin onay işlemi
        $request->update(['status' => FeatureRequestStatus::Onaylandi]);

        $job->refresh();
        $request->refresh();

        $this->assertTrue($job->is_featured);
        $this->assertNotNull($job->featured_until);
        $this->assertTrue($job->featured_until->isFuture());
        $this->assertNotNull($request->processed_at);
        $this->assertSame(FeatureRequestStatus::Onaylandi, $request->status);
    }

    public function test_rejection_action_updates_status_without_featuring(): void
    {
        [$employer, , , $job] = $this->createJobFixture();

        $request = JobFeatureRequest::create([
            'job_listing_id' => $job->id,
            'user_id' => $employer->id,
            'days' => 7,
            'status' => FeatureRequestStatus::Beklemede,
        ]);

        // Admin ret işlemi
        $request->update(['status' => FeatureRequestStatus::Reddedildi]);

        $job->refresh();
        $request->refresh();

        $this->assertFalse($job->is_featured);
        $this->assertNull($job->featured_until);
        $this->assertNotNull($request->processed_at);
        $this->assertSame(FeatureRequestStatus::Reddedildi, $request->status);
    }

    public function test_stats_widget_and_tabs_reflect_record_states(): void
    {
        $admin = $this->createAdmin();
        [$employer, , , $job] = $this->createJobFixture();

        // 1 beklemede
        JobFeatureRequest::create([
            'job_listing_id' => $job->id,
            'user_id' => $employer->id,
            'days' => 7,
            'status' => FeatureRequestStatus::Beklemede,
        ]);

        $response = $this->actingAs($admin)->get('/yonetim/job-feature-requests');
        $response->assertOk();
        $response->assertSee('Bekleyen Talepler');
        $response->assertSee('Onaylanan Talepler');
        $response->assertSee('Vitrinde Aktif İlanlar');
        $response->assertSee('Reddedilen Talepler');
        $response->assertSee('Tüm Talepler');
        $response->assertSee('Beklemede (İnceleme)');
        $response->assertSee('Onaylananlar (Vitrinde)');
        $response->assertSee('Kıdemli PHP / Laravel Geliştirici');
    }
}
