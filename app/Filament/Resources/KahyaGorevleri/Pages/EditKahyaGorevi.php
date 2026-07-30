<?php

namespace App\Filament\Resources\KahyaGorevleri\Pages;

use App\Filament\Resources\KahyaGorevleri\KahyaGorevleriResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKahyaGorevi extends EditRecord
{
    protected static string $resource = KahyaGorevleriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
