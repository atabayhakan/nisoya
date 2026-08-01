<?php

namespace App\Filament\Resources\TemsilcilikIslemleri\Pages;

use App\Filament\Resources\TemsilcilikIslemleri\TemsilcilikIslemiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTemsilcilikIslemleri extends ListRecords
{
    protected static string $resource = TemsilcilikIslemiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
