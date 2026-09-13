<?php

declare(strict_types=1);

namespace App\Filament\Resources\FootballMatches\Pages;

use App\Filament\Resources\FootballMatches\FootballMatchResource;
use App\Filament\Resources\FootballMatches\Widgets\FootballMatchStatsWidget;
use App\Models\FootballMatch;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFootballMatches extends ListRecords
{
    protected static string $resource = FootballMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FootballMatchStatsWidget::class,
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'hepsi' => Tab::make('Tüm Maçlar'),

            'onay_bekleyen' => Tab::make('Skor Onayı Bekleyen')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('result_status', 'kaptan_onayi_bekliyor'))
                ->badge(FootballMatch::query()->where('result_status', 'kaptan_onayi_bekliyor')->count() ?: null)
                ->badgeColor('warning'),

            'itirazli' => Tab::make('İtirazlı Maçlar')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('result_status', 'itiraz_edildi'))
                ->badge(FootballMatch::query()->where('result_status', 'itiraz_edildi')->count() ?: null)
                ->badgeColor('danger'),

            'tamamlanan' => Tab::make('Onaylanan / Biten')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('result_status', 'onaylandi')),

            'planlanan' => Tab::make('Planlanan Maçlar')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'planlandi')),
        ];
    }
}
