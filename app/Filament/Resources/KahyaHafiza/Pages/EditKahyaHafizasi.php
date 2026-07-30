<?php

namespace App\Filament\Resources\KahyaHafiza\Pages;

use App\Filament\Resources\KahyaHafiza\KahyaHafizaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKahyaHafizasi extends EditRecord
{
    protected static string $resource = KahyaHafizaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
