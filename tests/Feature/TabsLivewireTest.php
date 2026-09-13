<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\CompanyReviews\Pages\ListCompanyReviews;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\Deals\Pages\ListDeals;
use App\Filament\Resources\FeatureRequests\Pages\ListFeatureRequests;
use App\Filament\Resources\JobCategories\Pages\ListJobCategories;
use App\Filament\Resources\JobFeatureRequests\Pages\ListJobFeatureRequests;
use App\Filament\Resources\JobListings\Pages\ListJobListings;
use App\Filament\Resources\ListingImages\Pages\ListListingImages;
use App\Filament\Resources\Listings\Pages\ListListings;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TabsLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($this->admin);
    }

    public function test_companies_tabs_switch_without_error(): void
    {
        $test = Livewire::test(ListCompanies::class);
        $test->assertSuccessful();

        foreach (['hepsi', 'dogrulanmis', 'bekleyen', 'ilanli', 'yorumlu'] as $tab) {
            $test->set('activeTab', $tab);
            $test->assertSuccessful();
            $this->assertSame($tab, $test->get('activeTab'));
        }
    }

    public function test_job_listings_tabs_switch_without_error(): void
    {
        $test = Livewire::test(ListJobListings::class);
        $test->assertSuccessful();

        foreach (['hepsi', 'aktif', 'beklemede', 'one_cikan', 'basvurulu', 'kapali'] as $tab) {
            $test->set('activeTab', $tab);
            $test->assertSuccessful();
            $this->assertSame($tab, $test->get('activeTab'));
        }
    }

    public function test_job_categories_tabs_switch_without_error(): void
    {
        $test = Livewire::test(ListJobCategories::class);
        $test->assertSuccessful();

        foreach (['hepsi', 'aktif', 'pasif', 'ilanli', 'bos'] as $tab) {
            $test->set('activeTab', $tab);
            $test->assertSuccessful();
            $this->assertSame($tab, $test->get('activeTab'));
        }
    }

    public function test_company_reviews_tabs_switch_without_error(): void
    {
        $test = Livewire::test(ListCompanyReviews::class);
        $test->assertSuccessful();

        foreach (['hepsi', 'yayinda', 'gizli', 'yuksek_puan', 'dusuk_puan'] as $tab) {
            $test->set('activeTab', $tab);
            $test->assertSuccessful();
            $this->assertSame($tab, $test->get('activeTab'));
        }
    }

    public function test_job_feature_requests_tabs_switch_without_error(): void
    {
        $test = Livewire::test(ListJobFeatureRequests::class);
        $test->assertSuccessful();

        foreach (['hepsi', 'beklemede', 'onaylandi', 'reddedildi'] as $tab) {
            $test->set('activeTab', $tab);
            $test->assertSuccessful();
            $this->assertSame($tab, $test->get('activeTab'));
        }
    }

    public function test_deals_tabs_switch_without_error(): void
    {
        $test = Livewire::test(ListDeals::class);
        $test->assertSuccessful();

        foreach (['hepsi', 'acik', 'tamamlandi', 'sorunlu', 'iptal'] as $tab) {
            $test->set('activeTab', $tab);
            $test->assertSuccessful();
            $this->assertSame($tab, $test->get('activeTab'));
        }
    }

    public function test_categories_tabs_switch_without_error(): void
    {
        $test = Livewire::test(ListCategories::class);
        $test->assertSuccessful();

        foreach (['hepsi', 'ana_kategoriler', 'alt_kategoriler', 'hizmet', 'urun', 'bos'] as $tab) {
            $test->set('activeTab', $tab);
            $test->assertSuccessful();
            $this->assertSame($tab, $test->get('activeTab'));
        }
    }

    public function test_listing_images_tabs_switch_without_error(): void
    {
        $test = Livewire::test(ListListingImages::class);
        $test->assertSuccessful();

        foreach (['hepsi', 'kapak', 'gps', 'isaretli', 'hassas_exif'] as $tab) {
            $test->set('activeTab', $tab);
            $test->assertSuccessful();
            $this->assertSame($tab, $test->get('activeTab'));
        }
    }

    public function test_listings_tabs_switch_without_error(): void
    {
        $test = Livewire::test(ListListings::class);
        $test->assertSuccessful();

        foreach (['hepsi', 'aktif', 'beklemede', 'one_cikanlar', 'guvenlik', 'pasif'] as $tab) {
            $test->set('activeTab', $tab);
            $test->assertSuccessful();
            $this->assertSame($tab, $test->get('activeTab'));
        }
    }

    public function test_feature_requests_tabs_switch_without_error(): void
    {
        $test = Livewire::test(ListFeatureRequests::class);
        $test->assertSuccessful();

        foreach (['hepsi', 'beklemede', 'onaylandi', 'reddedildi'] as $tab) {
            $test->set('activeTab', $tab);
            $test->assertSuccessful();
            $this->assertSame($tab, $test->get('activeTab'));
        }
    }

    public function test_tags_tabs_switch_without_error(): void
    {
        $test = Livewire::test(ListTags::class);
        $test->assertSuccessful();

        foreach (['hepsi', 'ilanli', 'bosta', 'populer'] as $tab) {
            $test->set('activeTab', $tab);
            $test->assertSuccessful();
            $this->assertSame($tab, $test->get('activeTab'));
        }
    }

    public function test_contact_messages_tabs_switch_without_error(): void
    {
        $test = Livewire::test(ListContactMessages::class);
        $test->assertSuccessful();

        foreach (['acik', 'bana_atanan', 'yanitlandi', 'kapandi', 'hepsi'] as $tab) {
            $test->set('activeTab', $tab);
            $test->assertSuccessful();
            $this->assertSame($tab, $test->get('activeTab'));
        }
    }
}
