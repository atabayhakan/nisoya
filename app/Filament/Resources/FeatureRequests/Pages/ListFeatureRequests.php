<?php

namespace App\Filament\Resources\FeatureRequests\Pages;

use App\Filament\Resources\FeatureRequests\FeatureRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeatureRequests extends ListRecords
{
    protected static string $resource = FeatureRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
