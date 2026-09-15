<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\UserRole;
use App\Filament\Pages\KahyaSohbet;
use App\Filament\Resources\DiasporaReels\Pages\ListDiasporaReels;
use App\Jobs\GlobalCommand\AssessContent;
use App\Models\Category;
use App\Models\Country;
use App\Models\KahyaMesaji;
use App\Models\Listing;
use App\Models\User;
use App\Services\Diaspora\InstagramMetadataExtractor;
use App\Support\GlobalCommand\ContentQuality;
use App\Support\GlobalCommand\GeoContext;
use App\Support\GlobalCommand\GlobalOperations;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalCommandIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_catalog_import_is_idempotent_and_preserves_local_codes(): void
    {
        Country::create(['code' => 'XN', 'name_tr' => 'Yerel bölge', 'is_active' => true]);
        Country::create(['code' => 'DE', 'name_tr' => 'Editoryal ülke adı', 'is_active' => false]);
        $this->artisan('global-command:import-geo')->assertSuccessful();
        $this->artisan('global-command:import-geo')->assertSuccessful();
        $this->assertSame(250, Country::count());
        $this->assertSame(249, DB::table('country_code_metadata')->where('code_system', 'iso-3166-1')->count());
        $this->assertDatabaseHas('countries', ['code' => 'TR', 'is_active' => true]);
        $this->assertDatabaseHas('countries', ['code' => 'DE', 'name_tr' => 'Editoryal ülke adı', 'is_active' => false]);
        $this->assertDatabaseHas('country_code_metadata', ['country_code' => 'XN', 'code_system' => 'local-extension']);
        $this->assertDatabaseCount('geo_regions', 30);
        $this->assertGreaterThan(200, DB::table('geo_country_language')->distinct()->count('country_code'));
    }

    public function test_metadata_rejects_non_instagram_hosts_without_network(): void
    {
        Http::preventStrayRequests();
        foreach (['http://127.0.0.1', 'https://instagram.com.evil.test/reel/C8xABC12345/', 'file:///etc/passwd'] as $url) {
            $this->assertFalse(app(InstagramMetadataExtractor::class)->extract($url)['success']);
        }
        Http::assertNothingSent();
    }

    public function test_quality_is_versioned_idempotent_and_discards_stale_source(): void
    {
        Queue::fake();
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $listing = Listing::withoutEvents(fn () => Listing::factory()->create(['user_id' => $actor->id]));
        $quality = app(ContentQuality::class);
        $first = $quality->request('listing', $listing, $actor->id);
        $second = $quality->request('listing', $listing, $actor->id);
        $this->assertSame($first->id, $second->id);
        Queue::assertPushed(AssessContent::class, 1);
        $listing->forceFill(['description' => 'Kaynak değişti'])->saveQuietly();
        $provider = \Mockery::mock(AiProvider::class);
        $provider->shouldNotReceive('analyzeText');
        (new AssessContent($first->id))->handle($quality, $provider);
        $this->assertSame('stale', $first->fresh()->status);
    }

    public function test_rules_only_assessment_does_not_change_listing_status(): void
    {
        Queue::fake();
        config(['global-command.ai_assessment_enabled' => false]);
        $listing = Listing::withoutEvents(fn () => Listing::factory()->create(['status' => 'beklemede', 'description' => 'Kısa']));
        $quality = app(ContentQuality::class);
        $assessment = $quality->request('listing', $listing, null);
        (new AssessContent($assessment->id))->handle($quality, \Mockery::mock(AiProvider::class));
        $this->assertSame('rules_only', $assessment->fresh()->status);
        $this->assertLessThan(100, $assessment->fresh()->quality_score);
        $this->assertNull($assessment->fresh()->risk_score);
        $this->assertSame('beklemede', $listing->fresh()->status->value);
    }

    public function test_rental_comparison_preserves_empty_country_and_currency_groups(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(2));
        foreach (['DE' => 'Almanya', 'KG' => 'Kırgızistan'] as $code => $name) {
            Country::create(['code' => $code, 'name_tr' => $name, 'is_active' => true]);
        }
        $category = Category::create(['name' => 'Kiralık Konut', 'slug' => 'kiralik-konut', 'type' => 'emlak']);
        Listing::withoutEvents(fn () => Listing::factory()->create(['type' => 'emlak', 'category_id' => $category->id, 'country_code' => 'DE', 'currency' => 'EUR', 'price_unit' => 'aylik', 'is_demo' => false]));
        $result = app(GlobalOperations::class)->compareRentals(['DE', 'KG']);
        $this->assertSame(1, $result['rows'][0]['listings']);
        $this->assertSame(0, $result['rows'][1]['listings']);
        $this->assertSame('EUR', $result['rows'][0]['price_groups'][0]['currency']);
        $this->assertNull($result['rows'][0]['price_groups'][0]['mean_price']);
        $this->assertFalse($result['fx_conversion']);
    }

    public function test_ai_assessment_accepts_empty_evidence_without_publishing(): void
    {
        Queue::fake();
        config(['global-command.ai_assessment_enabled' => true]);
        $listing = Listing::withoutEvents(fn () => Listing::factory()->create(['status' => 'beklemede']));
        $quality = app(ContentQuality::class);
        $assessment = $quality->request('listing', $listing, null);
        $provider = \Mockery::mock(AiProvider::class);
        $provider->shouldReceive('isConfigured')->once()->andReturnTrue();
        $provider->shouldReceive('analyzeText')->once()->andReturn(['risk_score' => 10, 'confidence' => 0.4, 'evidence' => [], 'suggestions' => []]);
        $provider->shouldReceive('name')->once()->andReturn('test');
        (new AssessContent($assessment->id))->handle($quality, $provider);
        $this->assertSame('completed', $assessment->fresh()->status);
        $this->assertSame(10, $assessment->fresh()->risk_score);
        $this->assertSame('beklemede', $listing->fresh()->status->value);
    }

    public function test_ai_cannot_save_invented_evidence(): void
    {
        Queue::fake();
        config(['global-command.ai_assessment_enabled' => true]);
        $listing = Listing::withoutEvents(fn () => Listing::factory()->create());
        $quality = app(ContentQuality::class);
        $assessment = $quality->request('listing', $listing, null);
        $provider = \Mockery::mock(AiProvider::class);
        $provider->shouldReceive('isConfigured')->once()->andReturnTrue();
        $provider->shouldReceive('analyzeText')->once()->andReturn(['risk_score' => 99, 'confidence' => 1, 'evidence' => ['Kaynakta olmayan suçlama'], 'suggestions' => []]);
        try {
            (new AssessContent($assessment->id))->handle($quality, $provider);
            $this->fail('Uydurma kanıt kabul edilmemeli.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('kanıtı', $exception->getMessage());
        }
        $this->assertSame('pending', $assessment->fresh()->status);
        $this->assertNull($assessment->fresh()->risk_score);
    }

    public function test_revoked_admin_cannot_run_queued_assessment(): void
    {
        Queue::fake();
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $listing = Listing::withoutEvents(fn () => Listing::factory()->create());
        $quality = app(ContentQuality::class);
        $assessment = $quality->request('listing', $listing, $actor->id);
        $actor->update(['role' => UserRole::Uye]);
        (new AssessContent($assessment->id))->handle($quality, \Mockery::mock(AiProvider::class));
        $this->assertSame('cancelled', $assessment->fresh()->status);
    }

    public function test_resource_rejects_action_from_stale_geographic_tab(): void
    {
        Country::create(['code' => 'DE', 'name_tr' => 'Almanya', 'is_active' => true]);
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $page = Livewire::actingAs($actor)->test(ListDiasporaReels::class);
        app()->instance(GeoContext::class, GeoContext::fromSelection(['mode' => 'country', 'country_code' => 'DE']));
        $page->call('$refresh')->assertStatus(409);
    }

    public function test_chat_history_is_private_to_each_admin(): void
    {
        $first = User::factory()->create(['role' => UserRole::Admin]);
        $second = User::factory()->create(['role' => UserRole::Admin]);
        $own = KahyaMesaji::create(['rol' => 'sahip', 'metin' => 'Kendi mesajım', 'user_id' => $first->id]);
        KahyaMesaji::create(['rol' => 'sahip', 'metin' => 'Diğer yöneticinin mesajı', 'user_id' => $second->id]);
        $this->actingAs($first);
        $page = app(KahyaSohbet::class);
        $this->assertSame([$own->id], $page->getMesajlar()->modelKeys());
    }
}
