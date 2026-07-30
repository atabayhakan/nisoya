<?php

namespace App\Filament\Resources\KahyaGorevleri\Pages;

use App\Filament\Resources\KahyaGorevleri\KahyaGorevleriResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKahyaGorevleri extends ListRecords
{
    protected static string $resource = KahyaGorevleriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
