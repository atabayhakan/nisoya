<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeatureRequests\Widgets;

use App\Enums\FeatureRequestStatus;
use App\Models\FeatureRequest;
use App\Models\Listing;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Öne Çıkarma Talepleri — Vitrin doluluğu, bekleyen incelemeler ve onay metrikleri.
 */
class FeatureRequestStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = FeatureRequest::query()->count();
        $bekleyen = FeatureRequest::query()->where('status', FeatureRequestStatus::Beklemede)->count();
        $onaylanan = FeatureRequest::query()->where('status', FeatureRequestStatus::Onaylandi)->count();
        $reddedilen = FeatureRequest::query()->where('status', FeatureRequestStatus::Reddedildi)->count();

        $vitrindeAktif = Listing::query()
            ->where('is_featured', true)
            ->where(function ($q): void {
                $q->whereNull('featured_until')
                    ->orWhere('featured_until', '>', now());
            })
            ->count();

        return [
            Stat::make('Bekleyen Talepler', (string) $bekleyen)
                ->description($bekleyen > 0 ? "{$bekleyen} talep inceleme bekliyor" : 'Tüm talepler incelendi')
                ->descriptionIcon('heroicon-m-clock')
                ->color($bekleyen > 0 ? 'warning' : 'success'),

            Stat::make('Onaylanan Talepler', (string) $onaylanan)
                ->description("{$toplam} başvurudan {$onaylanan} onay")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Vitrinde Aktif İlanlar', (string) $vitrindeAktif)
                ->description('Şu an sitede öne çıkan ilanlar')
                ->descriptionIcon('heroicon-m-rocket-launch')
                ->color('primary'),

            Stat::make('Reddedilen Talepler', (string) $reddedilen)
                ->description($reddedilen > 0 ? "{$reddedilen} başvuru reddedildi" : 'Reddedilen talep yok')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('gray'),
        ];
    }
}
