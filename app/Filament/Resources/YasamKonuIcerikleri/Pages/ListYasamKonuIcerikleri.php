<?php

namespace App\Filament\Resources\YasamKonuIcerikleri\Pages;

use App\Filament\Resources\YasamKonuIcerikleri\YasamKonuIcerigiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListYasamKonuIcerikleri extends ListRecords
{
    protected static string $resource = YasamKonuIcerigiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
