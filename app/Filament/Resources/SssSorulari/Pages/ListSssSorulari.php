<?php

namespace App\Filament\Resources\SssSorulari\Pages;

use App\Filament\Resources\SssSorulari\SssSorusuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSssSorulari extends ListRecords
{
    protected static string $resource = SssSorusuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
