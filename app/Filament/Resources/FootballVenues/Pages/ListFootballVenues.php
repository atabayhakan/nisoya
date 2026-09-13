<?php

declare(strict_types=1);

namespace App\Filament\Resources\FootballVenues\Pages;

use App\Filament\Resources\FootballVenues\FootballVenueResource;
use App\Filament\Resources\FootballVenues\Widgets\FootballVenueStatsWidget;
use App\Models\FootballVenue;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFootballVenues extends ListRecords
{
    protected static string $resource = FootballVenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FootballVenueStatsWidget::class,
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'hepsi' => Tab::make('Tüm Tesisler'),

            'kapali' => Tab::make('Kapalı Sahalar')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('pitch_type', 'kapali'))
                ->badge(FootballVenue::query()->where('pitch_type', 'kapali')->count() ?: null)
                ->badgeColor('info'),

            'suni_cim' => Tab::make('Suni Çim')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('surface_type', 'suni_cim')),

            'dogrulanmis' => Tab::make('Doğrulanmış Tesisler')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_verified', true))
                ->badge(FootballVenue::query()->where('is_verified', true)->count() ?: null)
                ->badgeColor('success'),
        ];
    }
}
