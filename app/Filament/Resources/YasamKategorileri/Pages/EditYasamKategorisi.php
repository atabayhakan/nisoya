<?php

namespace App\Filament\Resources\YasamKategorileri\Pages;

use App\Filament\Resources\YasamKategorileri\YasamKategorisiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditYasamKategorisi extends EditRecord
{
    protected static string $resource = YasamKategorisiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
