<?php

declare(strict_types=1);

namespace App\Filament\Resources\FootballTeams\Pages;

use App\Filament\Resources\FootballTeams\FootballTeamResource;
use App\Filament\Resources\FootballTeams\Widgets\FootballTeamStatsWidget;
use App\Models\FootballTeam;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFootballTeams extends ListRecords
{
    protected static string $resource = FootballTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FootballTeamStatsWidget::class,
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'hepsi' => Tab::make('Tüm Takımlar'),

            'dogrulanmis' => Tab::make('Doğrulanmış Rozetli')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_verified', true))
                ->badge(FootballTeam::query()->where('is_verified', true)->count() ?: null)
                ->badgeColor('success'),

            'liderler' => Tab::make('Lig Liderleri (15+ Puan)')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('points', '>=', 15))
                ->badge(FootballTeam::query()->where('points', '>=', 15)->count() ?: null)
                ->badgeColor('warning'),

            'inaktif' => Tab::make('İnaktif')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_active', false)),
        ];
    }
}
