<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\UserRole;
use App\Filament\Pages\GlobalCommandCenter;
use App\Jobs\GlobalCommand\AssessContent;
use App\Models\Country;
use App\Models\Listing;
use App\Models\User;
use App\Support\GlobalCommand\ContentQuality;
use App\Support\GlobalCommand\GeoContext;
use App\Support\GlobalCommand\QualityScanner;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalQualityWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        config(['global-command.enabled' => true, 'global-command.ai_assessment_enabled' => false]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        foreach (['DE' => 'Almanya', 'KG' => 'Kırgızistan'] as $code => $name) {
            Country::create(['code' => $code, 'name_tr' => $name, 'is_active' => true]);
        }
    }

    private function listing(array $attributes = []): Listing
    {
        return Listing::withoutEvents(fn () => Listing::factory()->create($attributes));
    }

    public function test_scan_resumes_across_batches_and_catches_old_edits_next_cycle(): void
    {
        $first = $this->listing(['updated_at' => now()->subMonth()]);
        $second = $this->listing(['updated_at' => now()->subMonth()]);
        $third = $this->listing(['updated_at' => now()->subMonth()]);
        $scanner = app(QualityScanner::class);
        $quality = app(ContentQuality::class);
        $this->assertSame(2, $scanner->scan($quality, 2));
        $this->assertDatabaseHas('quality_scan_cursors', ['kind' => 'listing', 'last_id' => $second->id, 'through_id' => $third->id]);
        $first->updateQuietly(['description' => 'Daha eski kaydın yeni açıklaması']);
        $fourth = $this->listing();
        $this->assertSame(1, $scanner->scan($quality, 2));
        $this->assertDatabaseHas('quality_scan_cursors', ['kind' => 'listing', 'last_id' => 0, 'through_id' => null]);
        $this->assertDatabaseMissing('content_assessments', ['source_id' => $fourth->id, 'kind' => 'listing']);
        $scanner->scan($quality, 2);
        $scanner->scan($quality, 2);
        $this->assertDatabaseCount('content_assessments', 5);
        Queue::assertPushed(AssessContent::class, 5);
        $scanner->scan($quality, 2);
        Queue::assertPushed(AssessContent::class, 5);
    }

    public function test_scan_failure_rolls_back_progress_and_requests(): void
    {
        $this->listing();
        $this->listing();
        $real = app(ContentQuality::class);
        $quality = \Mockery::mock(ContentQuality::class);
        $calls = 0;
        $quality->shouldReceive('request')->andReturnUsing(function ($kind, $source, $actor) use (&$calls, $real) {
            if (++$calls === 2) {
                throw new \RuntimeException('Simulated failure');
            }

            return $real->request($kind, $source, $actor);
        });
        try {
            app(QualityScanner::class)->scan($quality, 2);
            $this->fail('Tarama hatası bekleniyor.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('content_assessments', 0);
        $this->assertDatabaseCount('quality_scan_cursors', 0);
    }

    public function test_retry_is_idempotent_and_old_job_cannot_fail_new_attempt(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $listing = $this->listing();
        $quality = app(ContentQuality::class);
        $assessment = $quality->request('listing', $listing, $actor->id);
        $old = new AssessContent($assessment->id);
        $old->failed(new \RuntimeException);
        $retry = $quality->request('listing', $listing, $actor->id, retry: true);
        $quality->request('listing', $listing, $actor->id, retry: true);
        $this->assertSame(2, $retry->attempt);
        Queue::assertPushed(AssessContent::class, 2);
        $old->failed(new \RuntimeException);
        $old->handle($quality, \Mockery::mock(AiProvider::class));
        $this->assertSame('pending', $retry->fresh()->status);
        (new AssessContent($retry->id, 2))->handle($quality, \Mockery::mock(AiProvider::class));
        $this->assertSame('rules_only', $retry->fresh()->status);
    }

    public function test_retry_from_page_uses_current_source_version(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $listing = $this->listing();
        $original = app(ContentQuality::class)->request('listing', $listing, $actor->id);
        $original->update(['status' => 'completed', 'risk_score' => 99]);
        $listing->updateQuietly(['description' => 'İlan sahibi eksik açıklamayı tamamladı.']);
        Livewire::actingAs($actor)->test(GlobalCommandCenter::class)
            ->assertSee('Yeniden incele')->assertDontSee('99 /100')
            ->call('retryAssessment', $original->id)->assertHasNoErrors();
        $this->assertDatabaseCount('content_assessments', 2);
        $this->assertSame('completed', $original->fresh()->status);
        $this->assertDatabaseHas('content_assessments', ['source_hash' => app(ContentQuality::class)->hash($listing), 'status' => 'pending']);
    }

    public function test_retry_rejects_source_that_moved_outside_lens(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $listing = $this->listing(['country_code' => 'DE']);
        $assessment = app(ContentQuality::class)->request('listing', $listing, $actor->id);
        $assessment->update(['status' => 'failed']);
        $listing->updateQuietly(['country_code' => 'KG']);
        app()->instance(GeoContext::class, GeoContext::fromSelection(['mode' => 'country', 'country_code' => 'DE']));
        Livewire::actingAs($actor)->test(GlobalCommandCenter::class)
            ->assertDontSee('Yeniden incele')->call('retryAssessment', $assessment->id)->assertNotFound();
        $this->assertSame('failed', $assessment->fresh()->status);
    }

    public function test_history_is_paginated_searchable_and_scoped(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        for ($i = 1; $i <= 14; $i++) {
            $listing = $this->listing(['title' => sprintf('İnceleme kayıt %02d', $i), 'country_code' => 'DE']);
            $record = app(ContentQuality::class)->request('listing', $listing, $actor->id);
            if ($i === 1) {
                $record->update(['status' => 'failed']);
            }
        }
        app(ContentQuality::class)->request('listing', $this->listing(['title' => 'Başka ülkenin incelemesi', 'country_code' => 'KG']), $actor->id);
        app()->instance(GeoContext::class, GeoContext::fromSelection(['mode' => 'country', 'country_code' => 'DE']));
        Livewire::actingAs($actor)->test(GlobalCommandCenter::class)
            ->assertSee('İnceleme kayıt 14')->assertDontSee('İnceleme kayıt 01')->assertDontSee('Başka ülkenin incelemesi')
            ->call('nextPage', 'assessmentsPage')->assertSee('İnceleme kayıt 01')
            ->set('assessmentSearch', 'kayıt 14')->assertSee('İnceleme kayıt 14')->assertSet('paginators.assessmentsPage', 1)
            ->set('assessmentSearch', '')->set('assessmentStatus', 'failed')->assertSee('İnceleme kayıt 01')->assertDontSee('İnceleme kayıt 14');
    }

    public function test_scan_command_respects_disabled_feature(): void
    {
        $this->listing();
        config(['global-command.enabled' => false]);
        $this->artisan('global-command:assess-content', ['--limit' => 1])->assertSuccessful();
        $this->assertDatabaseCount('quality_scan_cursors', 0);
        Queue::assertNothingPushed();
    }

    public function test_authority_is_rechecked_after_provider_returns(): void
    {
        config(['global-command.ai_assessment_enabled' => true]);
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $quality = app(ContentQuality::class);
        $assessment = $quality->request('listing', $this->listing(), $actor->id);
        $provider = \Mockery::mock(AiProvider::class);
        $provider->shouldReceive('isConfigured')->andReturnTrue();
        $provider->shouldReceive('analyzeText')->once()->andReturnUsing(function () use ($actor) {
            $actor->update(['role' => UserRole::Uye]);

            return ['risk_score' => 10, 'confidence' => 0.5, 'evidence' => [], 'suggestions' => []];
        });
        (new AssessContent($assessment->id))->handle($quality, $provider);
        $this->assertSame('cancelled', $assessment->fresh()->status);
        $this->assertNull($assessment->fresh()->risk_score);
    }

    public function test_late_provider_result_cannot_overwrite_retried_work(): void
    {
        config(['global-command.ai_assessment_enabled' => true]);
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $quality = app(ContentQuality::class);
        $listing = $this->listing();
        $assessment = $quality->request('listing', $listing, $actor->id);
        $provider = \Mockery::mock(AiProvider::class);
        $provider->shouldReceive('isConfigured')->andReturnTrue();
        $provider->shouldReceive('analyzeText')->once()->andReturnUsing(function () use ($assessment, $quality, $listing, $actor) {
            $assessment->update(['status' => 'failed']);
            $quality->request('listing', $listing, $actor->id, retry: true);

            return ['risk_score' => 99, 'confidence' => 0.9, 'evidence' => [], 'suggestions' => []];
        });
        (new AssessContent($assessment->id))->handle($quality, $provider);
        $this->assertSame('pending', $assessment->fresh()->status);
        $this->assertSame(2, $assessment->fresh()->attempt);
        $this->assertNull($assessment->fresh()->risk_score);
    }
}
