<?php

namespace App\Filament\Widgets;

use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\GlobalCommand\GeoContext;
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
    protected ?string $pollingInterval = '30s';

    /** Sıra merdiveni {@see AdminPanelProvider} içinde. */
    protected static ?int $sort = 40;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Üyeler', app(GeoContext::class)->apply(User::query())->count())
                ->description(app(GeoContext::class)->apply(User::query())->where('created_at', '>=', now()->subWeek())->count().' bu hafta')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            Stat::make('Aktif ilanlar', app(GeoContext::class)->apply(Listing::query())->where('status', 'aktif')->count())
                ->description(app(GeoContext::class)->apply(Listing::query())->count().' toplam ilan')
                ->descriptionIcon('heroicon-m-rectangle-stack'),

            Stat::make('Mesajlar', Message::query()->whereHas('sender', fn ($query) => app(GeoContext::class)->apply($query))->count())
                ->description('Gönderenin konumuna göre mesajlar')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right'),

            Stat::make('Öne çıkan ilanlar', app(GeoContext::class)->apply(Listing::query())->where('is_featured', true)->count())
                ->description('Yayındaki öne çıkanlar')
                ->color('warning'),
        ];
    }
}
