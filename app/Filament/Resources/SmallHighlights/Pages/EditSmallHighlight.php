<?php

namespace App\Filament\Resources\SmallHighlights\Pages;

use App\Filament\Resources\SmallHighlights\SmallHighlightResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSmallHighlight extends EditRecord
{
    protected static string $resource = SmallHighlightResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
