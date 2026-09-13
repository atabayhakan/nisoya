<?php

declare(strict_types=1);

namespace App\Filament\Resources\Stories\Pages;

use App\Filament\Resources\Stories\StoryResource;
use App\Filament\Resources\Stories\Widgets\StoryStatsWidget;
use App\Models\Story;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStories extends ListRecords
{
    protected static string $resource = StoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StoryStatsWidget::class,
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'hepsi' => Tab::make('Tüm Hikayeler'),

            'published' => Tab::make('Yayında')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'published'))
                ->badge(Story::query()->where('status', 'published')->count() ?: null)
                ->badgeColor('success'),

            'pending' => Tab::make('İnceleme Bekleyen')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'pending'))
                ->badge(Story::query()->where('status', 'pending')->count() ?: null)
                ->badgeColor('warning'),

            'draft' => Tab::make('Taslak')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'draft')),
        ];
    }
}
