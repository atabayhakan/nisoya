<?php

declare(strict_types=1);

namespace App\Filament\Resources\FootballVenues\Widgets;

use App\Models\FootballVenue;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FootballVenueStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplamSaha = FootballVenue::query()->count();
        $kapali = FootballVenue::query()->where('pitch_type', 'kapali')->count();
        $gpsVar = FootballVenue::query()->whereNotNull('latitude')->whereNotNull('longitude')->count();
        $ortPuan = round((float) FootballVenue::query()->avg('rating'), 1);

        return [
            Stat::make('Toplam Halı Saha & Tesis', (string) $toplamSaha)
                ->description('Sistemde listelenen futbol tesisleri')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('success'),

            Stat::make('Kapalı / Yağmur Geçirmez', "{$kapali} Tesis")
                ->description('Kış şartlarına uygun sahalar')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),

            Stat::make('Harita & Navigasyon Hazır', "{$gpsVar} / {$toplamSaha}")
                ->description('Google & Yandex rota koordinatlı')
                ->descriptionIcon('heroicon-m-map')
                ->color('primary'),

            Stat::make('Ortalama Tesis Memnuniyeti', "⭐ {$ortPuan} / 5.0")
                ->description('Oyuncu değerlendirmeleri ortalaması')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}
