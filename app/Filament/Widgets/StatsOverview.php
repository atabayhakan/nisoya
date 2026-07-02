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
        $adsenseOk = (bool) config('services.adsense.enabled') && (bool) config('services.adsense.publisher_id');
        $analyticsOk = (bool) config('services.analytics.enabled') && (bool) config('services.analytics.measurement_id');
        $paypal = setting('bagis.paypal_me');
        $iban = setting('bagis.iban');

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

            Stat::make('AdSense', $adsenseOk ? 'Aktif' : 'Pasif')
                ->description($adsenseOk ? 'Yayıncı: '.config('services.adsense.publisher_id') : '.env veya admin panelden etkinleştir')
                ->descriptionIcon($adsenseOk ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($adsenseOk ? 'success' : 'danger'),

            Stat::make('Analytics', $analyticsOk ? 'Aktif' : 'Pasif')
                ->description($analyticsOk ? config('services.analytics.measurement_id') : 'Ölçüm ID eksik')
                ->descriptionIcon($analyticsOk ? 'heroicon-m-chart-bar' : 'heroicon-m-x-circle')
                ->color($analyticsOk ? 'success' : 'danger'),

            Stat::make('Bağış', ($paypal || $iban) ? 'Yapılandırıldı' : 'Boş')
                ->description($paypal ? 'PayPal: '.$paypal : ($iban ? 'IBAN eklendi' : 'Site Yönetimi → Bağış'))
                ->descriptionIcon(($paypal || $iban) ? 'heroicon-m-heart' : 'heroicon-m-exclamation-triangle')
                ->color(($paypal || $iban) ? 'success' : 'warning'),
        ];
    }
}
