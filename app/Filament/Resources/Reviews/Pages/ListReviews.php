<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\Reviews\Widgets\ReviewStatsWidget;
use App\Models\Review;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ReviewStatsWidget::class,
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'hepsi' => Tab::make('Tüm Yorumlar'),

            'beklemede' => Tab::make('Onay Bekleyen')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'beklemede'))
                ->badge(Review::query()->where('status', 'beklemede')->count() ?: null)
                ->badgeColor('warning'),

            'yayinda' => Tab::make('Yayında')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'yayinda'))
                ->badge(Review::query()->where('status', 'yayinda')->count() ?: null)
                ->badgeColor('success'),

            'reddedildi' => Tab::make('Reddedilen')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'reddedildi')),
        ];
    }
}
