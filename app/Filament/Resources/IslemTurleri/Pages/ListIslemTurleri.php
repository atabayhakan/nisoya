<?php

namespace App\Filament\Resources\IslemTurleri\Pages;

use App\Filament\Resources\IslemTurleri\IslemTuruResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIslemTurleri extends ListRecords
{
    protected static string $resource = IslemTuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
