<?php

namespace App\Filament\Resources\SmallHighlights\Pages;

use App\Filament\Resources\SmallHighlights\SmallHighlightResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmallHighlights extends ListRecords
{
    protected static string $resource = SmallHighlightResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
