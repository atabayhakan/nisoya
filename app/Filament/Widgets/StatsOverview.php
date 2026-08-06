<?php

namespace App\Filament\Widgets;

use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Pazaryerinin sayıları — panonun "işler nasıl gidiyor" satırı.
 *
 * ---------------------------------------------------------------------------
 * AdSense / Analytics / Bağış BURADAN ÇIKARILDI (2026-08-06)
 *
 * Bu üçü sayı değil YAPILANDIRMA DURUMUdur ve saatlik değil yılda bir değişir.
 * Aynı satırda durdukları sürece, panonun en değerli yerinde kalıcı olarak yer
 * kaplıyorlardı: sahip her sabah "AdSense hâlâ aktif" cümlesini okumak zorunda
 * değil. Artık {@see EntegrasyonlarWidget} içinde, sistem sağlığının yanında.
 *
 * Ayrım kuralı: burada YALNIZ zamanla değişen ve karar değiştiren sayılar durur.
 */
class StatsOverview extends BaseWidget
{
    /** Sıra merdiveni {@see AdminPanelProvider} içinde. */
    protected static ?int $sort = 40;

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
