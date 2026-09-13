<?php

namespace App\Filament\Resources\DiasporaReels\Pages;

use App\Filament\Resources\DiasporaReels\DiasporaReelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDiasporaReel extends EditRecord
{
    protected static string $resource = DiasporaReelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
