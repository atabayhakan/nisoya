<?php

namespace App\Filament\Resources\BigHighlights\Pages;

use App\Filament\Resources\BigHighlights\BigHighlightResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBigHighlight extends EditRecord
{
    protected static string $resource = BigHighlightResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
