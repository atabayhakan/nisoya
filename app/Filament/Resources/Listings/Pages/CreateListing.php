<?php

namespace App\Filament\Resources\Listings\Pages;

use App\Filament\Concerns\GuardsAdminGeoContext;
use App\Filament\Resources\Listings\ListingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateListing extends CreateRecord
{
    use GuardsAdminGeoContext;

    protected static string $resource = ListingResource::class;
}
