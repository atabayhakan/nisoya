<?php

declare(strict_types=1);

namespace App\Filament\Resources\FootballTeams\Widgets;

use App\Models\FootballTeam;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FootballTeamStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplamTakim = FootballTeam::query()->count();
        $dogrulanmis = FootballTeam::query()->where('is_verified', true)->count();
        $aktifTakim = FootballTeam::query()->where('is_active', true)->count();
        $ortPuan = round((float) FootballTeam::query()->avg('points'), 1);

        return [
            Stat::make('Toplam Takım', "{$aktifTakim} / {$toplamTakim}")
                ->description('Platformdaki kayıtlı halı saha takımları')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),

            Stat::make('Doğrulanmış Rozetli Takımlar', (string) $dogrulanmis)
                ->description('Resmi onaylı kulüpler')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary'),

            Stat::make('Lig Liderleri', (string) FootballTeam::query()->where('points', '>=', 15)->count())
                ->description('15+ puanlı zirve takımları')
                ->descriptionIcon('heroicon-m-fire')
                ->color('warning'),

            Stat::make('Ortalama Takım Puanı', "{$ortPuan} Puan")
                ->description('Şehir ligleri rekabet dengesi')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
