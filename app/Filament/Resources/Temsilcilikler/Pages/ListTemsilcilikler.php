<?php

namespace App\Filament\Resources\Temsilcilikler\Pages;

use App\Filament\Resources\Temsilcilikler\TemsilcilikResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTemsilcilikler extends ListRecords
{
    protected static string $resource = TemsilcilikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
