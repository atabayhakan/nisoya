<?php

namespace App\Filament\Resources\SmallHighlights\Pages;

use App\Filament\Resources\SmallHighlights\SmallHighlightResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSmallHighlight extends CreateRecord
{
    protected static string $resource = SmallHighlightResource::class;

    /** Formda slot alanı gösterilmiyor — kaynağın kendi konumundan otomatik atanır. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slot'] = SmallHighlightResource::slotValue();

        return $data;
    }
}
