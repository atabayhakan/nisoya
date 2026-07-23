<?php

namespace App\Filament\Resources\OutreachTargets\Pages;

use App\Filament\Resources\OutreachTargets\OutreachTargetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOutreachTarget extends EditRecord
{
    protected static string $resource = OutreachTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
