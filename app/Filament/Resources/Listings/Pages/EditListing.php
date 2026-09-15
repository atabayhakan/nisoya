<?php

namespace App\Filament\Resources\Listings\Pages;

use App\Filament\Concerns\GuardsAdminGeoContext;
use App\Filament\Resources\Listings\ListingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditListing extends EditRecord
{
    use GuardsAdminGeoContext;

    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
