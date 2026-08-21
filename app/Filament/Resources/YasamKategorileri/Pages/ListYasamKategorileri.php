<?php

namespace App\Filament\Resources\YasamKategorileri\Pages;

use App\Filament\Resources\YasamKategorileri\YasamKategorisiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListYasamKategorileri extends ListRecords
{
    protected static string $resource = YasamKategorisiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
