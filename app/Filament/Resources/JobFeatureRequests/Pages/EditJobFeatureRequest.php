<?php

namespace App\Filament\Resources\JobFeatureRequests\Pages;

use App\Filament\Resources\JobFeatureRequests\JobFeatureRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJobFeatureRequest extends EditRecord
{
    protected static string $resource = JobFeatureRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
