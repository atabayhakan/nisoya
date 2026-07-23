<?php

namespace App\Filament\Resources\OutreachTargets\Pages;

use App\Filament\Resources\OutreachTargets\OutreachTargetResource;
use Filament\Resources\Pages\ListRecords;

class ListOutreachTargets extends ListRecords
{
    protected static string $resource = OutreachTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
