<?php

declare(strict_types=1);

namespace App\Filament\Resources\FootballPlayerRequests\Pages;

use App\Filament\Resources\FootballPlayerRequests\FootballPlayerRequestResource;
use App\Filament\Resources\FootballPlayerRequests\Widgets\FootballPlayerRequestStatsWidget;
use App\Models\FootballPlayerRequest;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFootballPlayerRequests extends ListRecords
{
    protected static string $resource = FootballPlayerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FootballPlayerRequestStatsWidget::class,
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'hepsi' => Tab::make('Tüm İlanlar'),

            'oyuncu_arayan' => Tab::make('Oyuncu Arayan Takımlar')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('type', 'oyuncu_arayan_takim')->where('is_active', true))
                ->badge(FootballPlayerRequest::query()->where('type', 'oyuncu_arayan_takim')->where('is_active', true)->count() ?: null)
                ->badgeColor('warning'),

            'mac_arayan' => Tab::make('Takım / Maç Arayanlar')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('type', ['takim_arayan_oyuncu', 'rakip_arayan_takim'])->where('is_active', true))
                ->badge(FootballPlayerRequest::query()->whereIn('type', ['takim_arayan_oyuncu', 'rakip_arayan_takim'])->where('is_active', true)->count() ?: null)
                ->badgeColor('info'),

            'pasif' => Tab::make('Kapanan İlanlar')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_active', false)),
        ];
    }
}
