<?php

namespace App\Filament\Resources\TemsilcilikIslemleri\Pages;

use App\Filament\Resources\TemsilcilikIslemleri\TemsilcilikIslemiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTemsilcilikIslemi extends EditRecord
{
    protected static string $resource = TemsilcilikIslemiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
