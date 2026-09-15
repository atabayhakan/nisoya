<?php

namespace App\Filament\Resources\DiasporaAccounts\Pages;

use App\Filament\Concerns\GuardsAdminGeoContext;
use App\Filament\Resources\DiasporaAccounts\DiasporaAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDiasporaAccount extends EditRecord
{
    use GuardsAdminGeoContext;

    protected static string $resource = DiasporaAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
