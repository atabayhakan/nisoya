<?php

namespace App\Filament\Pages;

use App\Enums\UserStatus;
use App\Filament\Concerns\GuardsAdminGeoContext;
use App\Filament\Resources\DiasporaReels\DiasporaReelResource;
use App\Filament\Resources\JobListings\JobListingResource;
use App\Filament\Resources\Listings\ListingResource;
use App\Filament\Widgets\CountryLiquidityWidget;
use App\Models\ContentAssessment;
use App\Models\DiasporaReel;
use App\Models\GlobalGrowthTask;
use App\Models\JobListing;
use App\Models\Listing;
use App\Models\User;
use App\Support\GlobalCommand\ContentQuality;
use App\Support\GlobalCommand\GeoContext;
use App\Support\GlobalCommand\GrowthTaskWorkflow;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;
use UnitEnum;

class GlobalCommandCenter extends Page
{
    use GuardsAdminGeoContext;
    use WithPagination;

    public string $assessmentSearch = '';

    public string $assessmentStatus = 'all';

    public string $growthStatus = 'all';

    public bool $onlyMyGrowthTasks = false;

    #[Locked]
    public ?int $editingGrowthTaskId = null;

    #[Locked]
    public ?int $editingGrowthTaskRevision = null;

    public function updatedGrowthStatus(): void
    {
        $this->resetPage('growthPage');
    }

    public function updatedOnlyMyGrowthTasks(): void
    {
        $this->resetPage('growthPage');
    }

    public function manageGrowthTaskAction(): Action
    {
        return Action::make('manageGrowthTask')->label('Görevi yönet')->modalHeading('Büyüme görevini güncelle')
            ->modalDescription('Sorumluyu ve çalışma durumunu kaydedin. Tamamladığınız işi kısa bir sonuç notuyla belirtin.')
            ->schema([
                Select::make('status')->label('Durum')->options(GrowthTaskWorkflow::LABELS)->required()->live(),
                Select::make('assignee_id')->label('Sorumlu yönetici')->searchable()
                    ->required(fn (Get $get) => in_array($get('status'), ['in_progress', 'completed'], true))
                    ->getSearchResultsUsing(fn (string $search) => User::where('role', 'admin')->where('status', UserStatus::Aktif)
                        ->where('name', 'like', '%'.mb_substr($search, 0, 80).'%')->orderBy('name')->limit(30)->pluck('name', 'id')->all())
                    ->getOptionLabelUsing(fn ($value) => User::find($value)?->name),
                Textarea::make('completion_note')->label('Tamamlanma notu')->rows(3)->maxLength(2000)
                    ->required(fn (Get $get) => $get('status') === 'completed')
                    ->helperText('Tamamlandı durumunda zorunludur. Yeniden açılan görevlerin önceki notu işlem kaydında korunur.'),
            ])
            ->fillForm(function (array $arguments): array {
                abort_unless(static::canAccess(), 403);
                $task = GlobalGrowthTask::whereIn('country_code', app(GeoContext::class)->countries()->select('code'))
                    ->findOrFail($arguments['task'] ?? null);
                $this->editingGrowthTaskId = $task->id;
                $this->editingGrowthTaskRevision = $task->revision;

                return $task->only(['status', 'assignee_id', 'completion_note']);
            })
            ->action(function (array $data): void {
                abort_unless(static::canAccess() && $this->editingGrowthTaskId && $this->editingGrowthTaskRevision, 403);
                app(GrowthTaskWorkflow::class)->update($this->editingGrowthTaskId, $this->editingGrowthTaskRevision,
                    $data, auth()->user(), app(GeoContext::class));
                Notification::make()->title('Görev güncellendi')->success()->send();
            });
    }

    public function boot(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function updatedAssessmentSearch(): void
    {
        $this->resetPage('assessmentsPage');
    }

    public function updatedAssessmentStatus(): void
    {
        $this->resetPage('assessmentsPage');
    }

    public function retryAssessment(int $assessmentId): void
    {
        abort_unless(static::canAccess(), 403);
        $context = app(GeoContext::class);
        $assessment = $context->apply(ContentAssessment::query())->findOrFail($assessmentId);
        $quality = app(ContentQuality::class);
        $source = $quality->source($assessment->kind, $assessment->source_id);
        abort_unless($source && $context->apply($source->newQuery())->whereKey($source->getKey())->exists(), 404);
        $result = $quality->request($assessment->kind, $source, auth()->id(), retry: true);
        Notification::make()->title($result->status === 'pending' ? 'İnceleme kuyruğa alındı' : 'Bu içerik için güncel sonuç zaten var')
            ->success()->send();
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|UnitEnum|null $navigationGroup = 'Pazarlama & Büyüme';

    protected static ?string $navigationLabel = 'Küresel Operasyon Merkezi';

    protected static ?string $title = 'Küresel Operasyon Merkezi';

    protected static ?string $slug = 'global-command-center';

    protected string $view = 'filament.pages.global-command-center';

    public static function canAccess(): bool
    {
        return (bool) config('global-command.enabled') && (auth()->user()?->isAdmin() ?? false)
            && auth()->user()->status === UserStatus::Aktif;
    }

    protected function getHeaderWidgets(): array
    {
        return [CountryLiquidityWidget::class];
    }

    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        abort_unless(in_array($this->assessmentStatus, ['all', 'pending', 'completed', 'rules_only', 'failed', 'cancelled', 'stale'], true), 422);
        $query = app(GeoContext::class)->apply(ContentAssessment::query());
        $counts = (clone $query)->select('status')->selectRaw('COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $search = mb_substr(trim($this->assessmentSearch), 0, 100);
        $query->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'));
        $query->when($this->assessmentStatus !== 'all', fn ($q) => $q->where('status', $this->assessmentStatus));
        $assessments = $query->latest('id')->paginate(12, ['*'], 'assessmentsPage');
        foreach (['listing', 'job', 'reel'] as $kind) {
            $group = $assessments->getCollection()->where('kind', $kind);
            $ids = $group->pluck('source_id');
            $sources = (match ($kind) {
                'listing' => app(GeoContext::class)->apply(Listing::whereKey($ids))->withCount('images')->get(),
                'job' => app(GeoContext::class)->apply(JobListing::whereKey($ids))->get(),
                default => app(GeoContext::class)->apply(DiasporaReel::whereKey($ids))->get(),
            })->keyBy('id');
            foreach ($group as $assessment) {
                $source = $sources[$assessment->source_id] ?? null;
                $assessment->setAttribute('source_url', $source ? match ($kind) {
                    'listing' => ListingResource::getUrl('edit', ['record' => $source], panel: 'admin'),
                    'job' => JobListingResource::getUrl('edit', ['record' => $source], panel: 'admin'),
                    'reel' => DiasporaReelResource::getUrl('edit', ['record' => $source], panel: 'admin'),
                } : null);
                if (! $source || app(ContentQuality::class)->hash($source) !== $assessment->source_hash) {
                    $assessment->status = 'stale';
                    $assessment->quality_score = null;
                    $assessment->risk_score = null;
                    $assessment->findings = [];
                    $assessment->ai_result = null;
                }
            }
        }

        abort_unless($this->growthStatus === 'all' || array_key_exists($this->growthStatus, GrowthTaskWorkflow::LABELS), 422);
        $growthQuery = GlobalGrowthTask::whereIn('country_code', app(GeoContext::class)->countries()->select('code'));
        $growthCounts = (clone $growthQuery)->select('status')->selectRaw('COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $growthQuery->when($this->growthStatus !== 'all', fn ($q) => $q->where('status', $this->growthStatus))
            ->when($this->onlyMyGrowthTasks, fn ($q) => $q->where('assignee_id', auth()->id()));

        return ['assessments' => $assessments, 'assessmentCounts' => $counts, 'growthCounts' => $growthCounts,
            'growthTasks' => $growthQuery->with(['assignee:id,name,status', 'country:code,name_tr'])->latest('id')->paginate(12, ['*'], 'growthPage')];
    }
}
