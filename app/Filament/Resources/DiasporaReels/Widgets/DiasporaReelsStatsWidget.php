<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiasporaReels\Widgets;

use App\Models\DiasporaAccount;
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
        $yayinda = DiasporaReel::query()->where('is_active', true)->where('status', DiasporaReel::STATUS_PUBLISHED)->count();
        $onayBekleyen = DiasporaReel::query()->where('status', DiasporaReel::STATUS_DRAFT)->count();
        $izlenenHesaplar = DiasporaAccount::query()->where('is_active', true)->count();

        return [
            Stat::make('Toplam Reels & Hikaye', (string) $toplam)
                ->description('Küratörlü diaspora arşivi')
                ->descriptionIcon('heroicon-m-film')
                ->color('primary'),

            Stat::make('Canlı Vitrinde Yayında', (string) $yayinda)
                ->description($yayinda > 0 ? 'Ana sayfada gösteriliyor' : 'Henüz aktif içerik yok')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($yayinda > 0 ? 'success' : 'gray'),

            Stat::make('Onay Bekleyenler', (string) $onayBekleyen)
                ->description($onayBekleyen > 0 ? 'İnceleme bekleyen taslak içerik' : 'Kuyrukta bekleyen yok')
                ->descriptionIcon('heroicon-m-inbox-stack')
                ->color($onayBekleyen > 0 ? 'warning' : 'gray'),

            Stat::make('İzlenen Diaspora Hesapları', (string) $izlenenHesaplar)
                ->description('Aktif taranan topluluk kanalları')
                ->descriptionIcon('heroicon-m-at-symbol')
                ->color('info'),
        ];
    }
}
