<?php

declare(strict_types=1);

namespace App\Filament\Resources\Temsilcilikler\Widgets;

use App\Models\Country;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Temsilcilikler — Dış temsilcilik ağı ve konsolosluk rehberi metrik panosu.
 */
class TemsilciliklerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = Temsilcilik::query()->count();
        $aktif = Temsilcilik::query()->where('is_active', true)->count();
        $buyukelcilik = Temsilcilik::query()->where('tur', Temsilcilik::TUR_BUYUKELCILIK)->count();
        $baskonsolosluk = Temsilcilik::query()->where('tur', Temsilcilik::TUR_BASKONSOLOSLUK)->count();

        $ulkeSayisi = Temsilcilik::query()->distinct('country_code')->count('country_code');
        $aktifUlkeSayisi = Country::query()->where('is_active', true)->count();

        $koordinatli = Temsilcilik::query()->whereNotNull('latitude')->whereNotNull('longitude')->count();
        $koordinatOran = $toplam > 0 ? (int) round(($koordinatli / $toplam) * 100) : 0;

        $toplamIslem = TemsilcilikIslemi::query()->count();
        $yayindaIslem = TemsilcilikIslemi::query()->where('status', TemsilcilikIslemi::STATUS_YAYIN)->count();

        return [
            Stat::make('Aktif Temsilcilikler', "{$aktif} / {$toplam}")
                ->description("{$buyukelcilik} Büyükelçilik • {$baskonsolosluk} Başkonsolosluk")
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),

            Stat::make('Kapsanan Ülke', "{$ulkeSayisi} Ülke")
                ->description("Rehberdeki {$aktifUlkeSayisi} aktif ülkeden")
                ->descriptionIcon('heroicon-m-globe-americas')
                ->color('primary'),

            Stat::make('Harita & Navigasyon', "{$koordinatli} / {$toplam} (%{$koordinatOran})")
                ->description('Google Haritalar & 2GIS hazır')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color($koordinatOran >= 80 ? 'success' : 'warning'),

            Stat::make('Rehber İşlemleri', "{$yayindaIslem} Yayında")
                ->description("Toplam {$toplamIslem} konsolosluk işlemi")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }
}
