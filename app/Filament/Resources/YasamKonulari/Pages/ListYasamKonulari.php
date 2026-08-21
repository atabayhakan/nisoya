<?php

namespace App\Filament\Resources\YasamKonulari\Pages;

use App\Filament\Resources\YasamKonulari\YasamKonusuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListYasamKonulari extends ListRecords
{
    protected static string $resource = YasamKonusuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
