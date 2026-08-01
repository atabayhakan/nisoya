<?php

namespace App\Filament\Resources\Temsilcilikler\Pages;

use App\Filament\Resources\Temsilcilikler\TemsilcilikResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTemsilcilik extends EditRecord
{
    protected static string $resource = TemsilcilikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
