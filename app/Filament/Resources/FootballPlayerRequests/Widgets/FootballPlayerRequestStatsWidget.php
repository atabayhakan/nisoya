<?php

declare(strict_types=1);

namespace App\Filament\Resources\FootballPlayerRequests\Widgets;

use App\Models\FootballPlayerRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FootballPlayerRequestStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplamIlan = FootballPlayerRequest::query()->count();
        $aktifIlan = FootballPlayerRequest::query()->where('is_active', true)->count();
        $oyuncuArayan = FootballPlayerRequest::query()->where('is_active', true)->where('type', 'oyuncu_arayan_takim')->count();
        $macArayan = FootballPlayerRequest::query()->where('is_active', true)->whereIn('type', ['takim_arayan_oyuncu', 'rakip_arayan_takim'])->count();

        return [
            Stat::make('Aktif Futbol İlanı', "{$aktifIlan} / {$toplamIlan}")
                ->description('Yayında olan açık ilanlar')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Oyuncu Arayan Takımlar', "{$oyuncuArayan} Takım")
                ->description('Eksik kadrolar (Adam Lazım)')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('warning'),

            Stat::make('Takım / Maç Arayanlar', "{$macArayan} İlan")
                ->description('Bireysel oyuncu ve rakip talepleri')
                ->descriptionIcon('heroicon-m-magnifying-glass')
                ->color('info'),

            Stat::make('Bu Haftaki Karşılaşmalar', (string) FootballPlayerRequest::query()->where('is_active', true)->where('match_time', '>=', now())->count())
                ->description('Yaklaşan maç saatleri')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),
        ];
    }
}
