<?php

declare(strict_types=1);

namespace App\Filament\Resources\Listings\Widgets;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Support\GlobalCommand\GeoContext;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * İlanlar — Pazaryeri KPI, durum dağılımı ve güvenlik göstergeleri.
 */
class ListingsStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = app(GeoContext::class)->apply(Listing::query())->count();
        $aktif = app(GeoContext::class)->apply(Listing::query())->where('status', ListingStatus::Aktif)->count();
        $beklemede = app(GeoContext::class)->apply(Listing::query())->where('status', ListingStatus::Beklemede)->count();
        $oneCikan = app(GeoContext::class)->apply(Listing::query())->where('is_featured', true)->count();
        $riskli = app(GeoContext::class)->apply(Listing::query())->whereNotNull('fraud_reason')->count();

        $urun = app(GeoContext::class)->apply(Listing::query())->where('type', ListingType::Urun)->count();
        $hizmet = app(GeoContext::class)->apply(Listing::query())->where('type', ListingType::Hizmet)->count();

        $aktifOran = $toplam > 0 ? (int) round(($aktif / $toplam) * 100) : 0;

        return [
            Stat::make('Toplam İlan', (string) $toplam)
                ->description("{$urun} Ürün • {$hizmet} Hizmet")
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Yayında (Aktif)', "{$aktif} (%{$aktifOran})")
                ->description('Canlıda aramalara ve vitrine açık')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Onay Bekleyenler', (string) $beklemede)
                ->description($beklemede > 0 ? 'Moderasyon bekleyen yeni kayıtlar' : 'İnceleme kuyruğu temiz')
                ->descriptionIcon($beklemede > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($beklemede > 0 ? 'warning' : 'success'),

            Stat::make('Öne Çıkanlar', (string) $oneCikan)
                ->description('Vitrinde ve üst sıralarda listelenen')
                ->descriptionIcon('heroicon-m-star')
                ->color('info'),

            Stat::make('Güvenlik & Risk', (string) $riskli)
                ->description($riskli > 0 ? 'Metin denetiminde işaretlenenler' : 'Şüpheli işaret bulunmuyor')
                ->descriptionIcon($riskli > 0 ? 'heroicon-m-shield-exclamation' : 'heroicon-m-shield-check')
                ->color($riskli > 0 ? 'danger' : 'success'),
        ];
    }
}
