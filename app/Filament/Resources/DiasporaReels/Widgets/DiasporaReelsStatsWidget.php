<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiasporaReels\Widgets;

use App\Models\DiasporaReel;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DiasporaReelsStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = DiasporaReel::query()->count();
        $yayinda = DiasporaReel::query()->where('is_active', true)->count();
        $oneCikan = DiasporaReel::query()->where('is_featured', true)->count();
        $ulkeler = DiasporaReel::query()->whereNotNull('country_code')->distinct('country_code')->count('country_code');

        return [
            Stat::make('Toplam Reels & Hikaye', (string) $toplam)
                ->description('Küratörlü diaspora arşivi')
                ->descriptionIcon('heroicon-m-film')
                ->color('primary'),

            Stat::make('Canlı Vitrinde Yayında', (string) $yayinda)
                ->description($yayinda > 0 ? 'Ana sayfada gösteriliyor' : 'Henüz aktif içerik yok')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($yayinda > 0 ? 'success' : 'gray'),

            Stat::make('Öne Çıkanlar', (string) $oneCikan)
                ->description('Büyük kart vurgusu alanlar')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('amber'),

            Stat::make('Kapsanan Ülkeler', (string) $ulkeler)
                ->description('Farklı diaspora bölgeleri')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),
        ];
    }
}
