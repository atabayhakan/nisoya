<?php

namespace App\Filament\Resources\FootballTeams\Pages;

use App\Filament\Resources\FootballTeams\FootballTeamResource;
use Filament\Resources\Pages\ListRecords;

class ListFootballTeams extends ListRecords
{
    protected static string $resource = FootballTeamResource::class;
}
