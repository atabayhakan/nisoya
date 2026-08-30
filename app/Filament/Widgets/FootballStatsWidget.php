<?php

namespace App\Filament\Widgets;

use App\Enums\FootballResultStatus;
use App\Models\FootballMatch;
use App\Models\FootballPlayerProfile;
use App\Models\FootballPlayerRequest;
use App\Models\FootballTeam;
use App\Models\FootballVenue;
use App\Support\Modules;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FootballStatsWidget extends BaseWidget
{
    protected static ?int $sort = 50;

    public static function canView(): bool
    {
        return Modules::enabled('hali_saha');
    }

    protected function getStats(): array
    {
        $disputedMatches = FootballMatch::where('result_status', FootballResultStatus::Itiraz->value)->count();
        $teamsCount = FootballTeam::where('is_active', true)->count();
        $matchesCount = FootballMatch::where('result_status', FootballResultStatus::Dogrulandi->value)->count();
        $venuesCount = FootballVenue::where('is_active', true)->count();

        return [
            Stat::make('Aktif Takımlar', $teamsCount)
                ->description('Kayıtlı halı saha takımları')
                ->icon('heroicon-m-trophy')
                ->color('success'),

            Stat::make('Doğrulanan Maçlar', $matchesCount)
                ->description('Skoru teyit edilmiş maçlar')
                ->icon('heroicon-m-check-badge')
                ->color('primary'),

            Stat::make('Kayıtlı Tesisler', $venuesCount)
                ->description('Halı sahalar')
                ->icon('heroicon-m-map-pin')
                ->color('info'),

            Stat::make('İtiraz Edilen Maçlar', $disputedMatches)
                ->description($disputedMatches > 0 ? 'İnceleme bekleyen skor itirazı' : 'Tüm maçlar mutabakatlı')
                ->icon('heroicon-m-exclamation-triangle')
                ->color($disputedMatches > 0 ? 'danger' : 'gray'),
        ];
    }
}
