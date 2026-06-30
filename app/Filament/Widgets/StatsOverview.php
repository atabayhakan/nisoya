<?php

namespace App\Filament\Widgets;

use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Üyeler', User::query()->count())
                ->description(User::query()->where('created_at', '>=', now()->subWeek())->count().' bu hafta')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            Stat::make('Aktif ilanlar', Listing::query()->where('status', 'aktif')->count())
                ->description(Listing::query()->count().' toplam ilan')
                ->descriptionIcon('heroicon-m-rectangle-stack'),

            Stat::make('Mesajlar', Message::query()->count())
                ->description('Toplam mesaj sayısı')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right'),

            Stat::make('Öne çıkan ilanlar', Listing::query()->where('is_featured', true)->count())
                ->description('Yayındaki öne çıkanlar')
                ->color('warning'),
        ];
    }
}
