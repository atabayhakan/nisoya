<?php

namespace App\Filament\Resources\DiasporaAccounts\Pages;

use App\Filament\Concerns\GuardsAdminGeoContext;
use App\Filament\Resources\DiasporaAccounts\DiasporaAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiasporaAccount extends CreateRecord
{
    use GuardsAdminGeoContext;

    protected static string $resource = DiasporaAccountResource::class;
}
