<?php

namespace App\Filament\Resources\JobFeatureRequests\Pages;

use App\Filament\Resources\JobFeatureRequests\JobFeatureRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJobFeatureRequests extends ListRecords
{
    protected static string $resource = JobFeatureRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
