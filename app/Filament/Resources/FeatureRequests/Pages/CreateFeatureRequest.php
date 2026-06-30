<?php

namespace App\Filament\Resources\FeatureRequests\Pages;

use App\Filament\Resources\FeatureRequests\FeatureRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeatureRequest extends CreateRecord
{
    protected static string $resource = FeatureRequestResource::class;
}
