<?php

declare(strict_types=1);

namespace App\Filament\Resources\Stories\Widgets;

use App\Models\Story;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoryStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = Story::query()->count();
        $yayinda = Story::query()->where('status', 'published')->count();
        $bekleyen = Story::query()->where('status', 'pending')->count();
        $toplamGoruntulenme = Story::query()->sum('views_count') ?? 0;

        return [
            Stat::make('Gurbet Günlüğü Hikayeleri', (string) $toplam)
                ->description('Kullanıcı anı ve hikayeleri')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary'),

            Stat::make('Yayındaki Hikayeler', (string) $yayinda)
                ->description('Okuyucuya açık yazılar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('İnceleme Bekleyen', (string) $bekleyen)
                ->description($bekleyen > 0 ? 'Yayın onayı bekliyor' : 'Kuyruk temiz')
                ->descriptionIcon('heroicon-m-clock')
                ->color($bekleyen > 0 ? 'warning' : 'gray'),

            Stat::make('Toplam Okunma', number_format($toplamGoruntulenme))
                ->description('Kümülatif görüntülenme sayısı')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),
        ];
    }
}
