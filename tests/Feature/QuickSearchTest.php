<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Event;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_returns_empty_results_for_short_query(): void
    {
        $this->getJson('/arama/hizli?q=a')
            ->assertOk()
            ->assertExactJson(['results' => []]);
    }

    public function test_finds_matching_service_listing(): void
    {
        Listing::factory()->create(['title' => 'İngilizce özel ders', 'status' => 'aktif']);

        $this->getJson('/arama/hizli?q=özel ders')
            ->assertOk()
            ->assertJsonFragment(['category' => 'Hizmet', 'title' => 'İngilizce özel ders']);
    }

    public function test_finds_matching_property_listing_with_emlak_label(): void
    {
        Listing::factory()->create(['title' => 'Berlin kiralık daire', 'type' => 'emlak', 'status' => 'aktif']);

        $this->getJson('/arama/hizli?q=kiralık daire')
            ->assertOk()
            ->assertJsonFragment(['category' => 'Emlak', 'title' => 'Berlin kiralık daire']);
    }

    public function test_excludes_inactive_listings(): void
    {
        Listing::factory()->create(['title' => 'Bekleyen ilan başlığı', 'status' => 'beklemede']);

        $this->getJson('/arama/hizli?q=Bekleyen ilan')
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    public function test_finds_matching_job_listing_with_company_subtitle(): void
    {
        $employer = User::factory()->create(['account_type' => AccountType::Kurumsal]);
        $company = Company::create(['user_id' => $employer->id, 'name' => 'Acme GmbH', 'slug' => 'acme-gmbh']);
        $company->jobListings()->create([
            'title' => 'Aşçı aranıyor', 'slug' => 'asci-araniyor', 'description' => 'Deneyimli aşçı.',
            'employment_type' => 'tam_zamanli', 'status' => JobStatus::Aktif->value, 'positions' => 1,
        ]);

        $this->getJson('/arama/hizli?q=aşçı')
            ->assertOk()
            ->assertJsonFragment(['category' => 'İş İlanı', 'title' => 'Aşçı aranıyor', 'subtitle' => 'Acme GmbH']);
    }

    public function test_finds_searchable_candidate_and_excludes_hidden(): void
    {
        User::factory()->create(['name' => 'Ayşe Aşçı', 'is_searchable' => true]);
        User::factory()->create(['name' => 'Ayşe Gizli', 'is_searchable' => false]);

        $response = $this->getJson('/arama/hizli?q=Ayşe')->assertOk();

        $response->assertJsonFragment(['title' => 'Ayşe Aşçı', 'category' => 'Yetenek']);
        $response->assertJsonMissing(['title' => 'Ayşe Gizli']);
    }

    public function test_does_not_include_events(): void
    {
        $host = User::factory()->create();
        Event::create([
            'user_id' => $host->id,
            'type' => 'dugun',
            'title' => 'Çok Özel Kesfedilmez Etkinlik',
            'starts_at' => now()->addMonth(),
            'venue_name' => 'Grand Salon',
            'venue_address' => 'Hauptstraße 12, Berlin',
            'description' => 'gizli',
            'theme' => '',
            'is_active' => true,
        ]);

        $this->getJson('/arama/hizli?q=Kesfedilmez')
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }
}
