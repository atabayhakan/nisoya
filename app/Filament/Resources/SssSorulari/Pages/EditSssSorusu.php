<?php

namespace App\Filament\Resources\SssSorulari\Pages;

use App\Filament\Resources\SssSorulari\SssSorusuResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSssSorusu extends EditRecord
{
    protected static string $resource = SssSorusuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
