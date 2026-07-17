<?php

namespace App\Filament\Resources\BigHighlights\Pages;

use App\Filament\Resources\BigHighlights\BigHighlightResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBigHighlights extends ListRecords
{
    protected static string $resource = BigHighlightResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
