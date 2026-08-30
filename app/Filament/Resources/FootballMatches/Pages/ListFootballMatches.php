<?php

namespace App\Filament\Resources\FootballMatches\Pages;

use App\Filament\Resources\FootballMatches\FootballMatchResource;
use Filament\Resources\Pages\ListRecords;

class ListFootballMatches extends ListRecords
{
    protected static string $resource = FootballMatchResource::class;
}
