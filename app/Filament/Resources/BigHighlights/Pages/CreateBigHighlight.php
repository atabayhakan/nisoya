<?php

namespace App\Filament\Resources\BigHighlights\Pages;

use App\Filament\Resources\BigHighlights\BigHighlightResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBigHighlight extends CreateRecord
{
    protected static string $resource = BigHighlightResource::class;

    /** Formda slot alanı gösterilmiyor — kaynağın kendi konumundan otomatik atanır. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slot'] = BigHighlightResource::slotValue();

        return $data;
    }
}
