<?php

namespace App\Filament\Resources\KahyaHafiza\Pages;

use App\Filament\Resources\KahyaHafiza\KahyaHafizaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKahyaHafizasi extends ListRecords
{
    protected static string $resource = KahyaHafizaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
