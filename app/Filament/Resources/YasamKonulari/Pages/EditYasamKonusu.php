<?php

namespace App\Filament\Resources\YasamKonulari\Pages;

use App\Filament\Resources\YasamKonulari\YasamKonusuResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditYasamKonusu extends EditRecord
{
    protected static string $resource = YasamKonusuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
