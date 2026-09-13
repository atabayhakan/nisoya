<?php

declare(strict_types=1);

namespace App\Filament\Resources\FootballMatches\Widgets;

use App\Models\FootballMatch;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FootballMatchStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplamMac = FootballMatch::query()->count();
        $onayBekleyen = FootballMatch::query()->where('result_status', 'kaptan_onayi_bekliyor')->count();
        $itirazli = FootballMatch::query()->where('result_status', 'itiraz_edildi')->count();
        $buHafta = FootballMatch::query()->whereBetween('match_date', [now()->startOfWeek(), now()->endOfWeek()])->count();

        return [
            Stat::make('Toplam Halı Saha Maçı', (string) $toplamMac)
                ->description("{$buHafta} maç bu hafta planlandı")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Skor Onayı Bekleyen', (string) $onayBekleyen)
                ->description($onayBekleyen > 0 ? 'Kaptan doğrulaması bekliyor' : 'Tüm skorlar güncel')
                ->descriptionIcon('heroicon-m-clock')
                ->color($onayBekleyen > 0 ? 'warning' : 'success'),

            Stat::make('İtirazlı Maçlar', (string) $itirazli)
                ->description($itirazli > 0 ? 'Hakem/Moderatör incelemesi gerekir' : 'İtiraz bulunmuyor')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($itirazli > 0 ? 'danger' : 'gray'),

            Stat::make('Haftalık Maç Temposu', "{$buHafta} Karşılaşma")
                ->description('Bu haftaki aktif maçlar')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('success'),
        ];
    }
}
