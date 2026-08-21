<?php

namespace App\Filament\Resources\YasamKonuIcerikleri\Pages;

use App\Filament\Resources\YasamKonuIcerikleri\YasamKonuIcerigiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditYasamKonuIcerigi extends EditRecord
{
    protected static string $resource = YasamKonuIcerigiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
