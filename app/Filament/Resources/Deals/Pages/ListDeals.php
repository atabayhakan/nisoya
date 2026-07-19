<?php

namespace App\Filament\Resources\Deals\Pages;

use App\Filament\Resources\Deals\DealResource;
use Filament\Resources\Pages\ListRecords;

class ListDeals extends ListRecords
{
    protected static string $resource = DealResource::class;

    // Anlaşmalar yalnızca üyeler tarafından sohbet içinden oluşturulur;
    // admin panelinde salt-görüntülenir (yeni ekleme yok).
    protected function getHeaderActions(): array
    {
        return [];
    }
}
