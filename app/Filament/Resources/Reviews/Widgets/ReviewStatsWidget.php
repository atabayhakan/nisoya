<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reviews\Widgets;

use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReviewStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = Review::query()->count();
        $bekleyen = Review::query()->where('status', 'beklemede')->count();
        $yayinda = Review::query()->where('status', 'yayinda')->count();
        $ortPuan = round((float) Review::query()->where('status', 'yayinda')->avg('rating'), 1);

        return [
            Stat::make('Toplam Değerlendirme', (string) $toplam)
                ->description('Kullanıcı & işletme yorumları')
                ->descriptionIcon('heroicon-m-chat-bubble-bottom-center-text')
                ->color('primary'),

            Stat::make('Onay Bekleyen', (string) $bekleyen)
                ->description($bekleyen > 0 ? 'Moderasyon kuyruğunda bekliyor' : 'Tüm yorumlar moderasyondan geçti')
                ->descriptionIcon('heroicon-m-clock')
                ->color($bekleyen > 0 ? 'warning' : 'success'),

            Stat::make('Yayındaki Yorumlar', (string) $yayinda)
                ->description('Platformda aktif gösterilen')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Ortalama Puan', "⭐ {$ortPuan} / 5.0")
                ->description('Genel memnuniyet skoru')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}
