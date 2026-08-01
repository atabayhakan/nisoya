<?php

namespace App\Filament\Resources\IslemTurleri\Pages;

use App\Filament\Resources\IslemTurleri\IslemTuruResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIslemTuru extends EditRecord
{
    protected static string $resource = IslemTuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
