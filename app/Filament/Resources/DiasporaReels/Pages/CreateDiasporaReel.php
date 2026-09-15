<?php

namespace App\Filament\Resources\DiasporaReels\Pages;

use App\Filament\Concerns\GuardsAdminGeoContext;
use App\Filament\Resources\DiasporaReels\DiasporaReelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiasporaReel extends CreateRecord
{
    use GuardsAdminGeoContext;

    protected static string $resource = DiasporaReelResource::class;
}
