<?php

namespace App\Filament\Resources\BekleyenHamleler\Pages;

use App\Filament\Resources\BekleyenHamleler\BekleyenHamlelerResource;
use Filament\Resources\Pages\ListRecords;

class ListBekleyenHamleler extends ListRecords
{
    protected static string $resource = BekleyenHamlelerResource::class;

    // Kart yalnız Kâhya tarafından açılır (hamle-oner) — başlık aksiyonu yok.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
