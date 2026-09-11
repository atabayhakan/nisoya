<?php

namespace App\Filament\Resources\TelegramSohbetleri\Pages;

use App\Filament\Resources\TelegramSohbetleri\TelegramSohbetleriResource;
use Filament\Resources\Pages\ListRecords;

class ListTelegramSohbetleri extends ListRecords
{
    protected static string $resource = TelegramSohbetleriResource::class;

    // Kayıt yalnız TelegramDinleyici tarafından açılır — başlık aksiyonu yok.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
