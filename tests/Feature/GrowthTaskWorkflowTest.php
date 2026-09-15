<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\GlobalCommandCenter;
use App\Models\Country;
use App\Models\GlobalGrowthTask;
use App\Models\User;
use App\Support\GlobalCommand\GeoContext;
use App\Support\GlobalCommand\GrowthTaskWorkflow;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GrowthTaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['global-command.enabled' => true]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        foreach (['DE' => 'Almanya', 'KG' => 'Kırgızistan'] as $code => $name) {
            Country::create(['code' => $code, 'name_tr' => $name, 'is_active' => true]);
        }
    }

    private function task(array $data = []): GlobalGrowthTask
    {
        return GlobalGrowthTask::create([...['country_code' => 'DE', 'action_key' => 'discover_community',
            'recommendation' => 'Yerel topluluğu araştır', 'status' => 'suggested', 'period_start' => now()->startOfWeek(),
            'metric_version' => 'supply-v1'], ...$data])->refresh();
    }

    public function test_task_can_be_assigned_completed_and_reopened_with_audit_without_notifications(): void
    {
        Notification::fake();
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $assignee = User::factory()->create(['role' => UserRole::Admin]);
        $task = $this->task();
        $workflow = app(GrowthTaskWorkflow::class);
        $task = $workflow->update($task->id, $task->revision, ['status' => 'in_progress', 'assignee_id' => $assignee->id], $actor, GeoContext::global());
        $task = $workflow->update($task->id, $task->revision, ['status' => 'completed', 'assignee_id' => $assignee->id, 'completion_note' => 'İki topluluk kaynağı incelendi.'], $actor, GeoContext::global());
        $this->assertNotNull($task->completed_at);
        $this->assertSame($actor->id, $task->completed_by);
        $this->assertSame(3, $task->revision);
        $task = $workflow->update($task->id, $task->revision, ['status' => 'in_progress', 'assignee_id' => $assignee->id], $actor, GeoContext::global());
        $this->assertNull($task->completed_at);
        $this->assertNull($task->completion_note);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'global-command', 'description' => 'growth.task.updated']);
        Notification::assertNothingSent();
    }

    public function test_completion_requires_result_note(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $task = $this->task();
        $this->expectException(ValidationException::class);
        app(GrowthTaskWorkflow::class)->update($task->id, 1, ['status' => 'completed', 'assignee_id' => $actor->id], $actor, GeoContext::global());
    }

    public function test_stale_revision_does_not_overwrite_another_manager(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $task = $this->task();
        app(GrowthTaskWorkflow::class)->update($task->id, 1, ['status' => 'in_progress', 'assignee_id' => $actor->id], $actor, GeoContext::global());
        try {
            app(GrowthTaskWorkflow::class)->update($task->id, 1, ['status' => 'dismissed'], $actor, GeoContext::global());
            $this->fail('Eski sürüm reddedilmeli.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        $this->assertSame('in_progress', $task->fresh()->status);
    }

    public function test_member_cannot_be_assigned(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $member = User::factory()->create();
        $task = $this->task();
        $this->expectException(HttpException::class);
        app(GrowthTaskWorkflow::class)->update($task->id, 1, ['status' => 'in_progress', 'assignee_id' => $member->id], $actor, GeoContext::global());
    }

    public function test_page_modal_saves_task_and_my_tasks_filter_works(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $task = $this->task();
        $this->task(['country_code' => 'KG', 'recommendation' => 'Başka yöneticinin işi']);
        Livewire::actingAs($actor)->test(GlobalCommandCenter::class)
            ->callAction('manageGrowthTask', ['status' => 'in_progress', 'assignee_id' => $actor->id], ['task' => $task->id])
            ->assertHasNoActionErrors()->set('onlyMyGrowthTasks', true)
            ->assertSee('Yerel topluluğu araştır')->assertDontSee('Başka yöneticinin işi');
        $this->assertSame($actor->id, $task->fresh()->assignee_id);
    }

    public function test_task_outside_country_cannot_be_opened(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $task = $this->task(['country_code' => 'KG']);
        app()->instance(GeoContext::class, GeoContext::fromSelection(['mode' => 'country', 'country_code' => 'DE']));
        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($actor)->test(GlobalCommandCenter::class)->call('mountAction', 'manageGrowthTask', ['task' => $task->id]);
    }

    public function test_planner_does_not_duplicate_open_task_in_later_week(): void
    {
        $this->task(['period_start' => now()->subWeeks(2)->startOfWeek(), 'status' => 'in_progress']);
        $this->artisan('global-command:plan-cold-start')->assertSuccessful();
        $this->assertSame(1, GlobalGrowthTask::where('country_code', 'DE')->count());
    }
}
