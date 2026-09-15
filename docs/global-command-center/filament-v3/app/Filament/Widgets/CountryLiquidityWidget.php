<?php

namespace App\Filament\Widgets;

use App\Enums\UserStatus;
use App\Support\GlobalCommand\CountryLiquidity;
use App\Support\GlobalCommand\GeoContext;
use Filament\Widgets\Widget;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\WithPagination;

class CountryLiquidityWidget extends Widget
{
    use WithPagination;

    protected static string $view = 'filament.widgets.country-liquidity';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public string $search = '';

    public static function canView(): bool
    {
        return (bool) config('global-command.enabled') && (auth()->user()?->isAdmin() ?? false)
            && auth()->user()->status === UserStatus::Aktif;
    }

    public function updatedSearch(): void
    {
        $this->resetPage('liquidityPage');
    }

    protected function getViewData(): array
    {
        abort_unless(static::canView(), 403);
        $context = app(GeoContext::class);
        $snapshot = app(CountryLiquidity::class)->snapshot($context);
        $all = collect($snapshot['rows']);
        $search = mb_strtolower(mb_substr(trim($this->search), 0, 80));
        $filtered = $all->filter(fn ($row) => $search === '' || str_contains(mb_strtolower($row['name'].' '.$row['code']), $search))
            ->sortBy('score')->values();
        $page = max(1, min((int) $this->getPage('liquidityPage'), max(1, (int) ceil($filtered->count() / 12))));
        $rows = new LengthAwarePaginator($filtered->forPage($page, 12), $filtered->count(), 12, $page,
            ['pageName' => 'liquidityPage', 'path' => Paginator::resolveCurrentPath()]);

        return ['context' => $context, 'rows' => $rows, 'measuredAt' => $snapshot['measured_at'],
            'totals' => ['countries' => $all->count(), 'listings' => $all->sum('listings'),
                'jobs' => $all->sum('jobs'), 'users' => $all->sum('users'), 'reels' => $all->sum('reels'),
                'cold' => $all->where('stage', 'cold_start')->count()]];
    }
}
