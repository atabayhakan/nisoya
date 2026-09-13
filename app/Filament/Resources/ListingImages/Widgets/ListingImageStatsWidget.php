<?php

declare(strict_types=1);

namespace App\Filament\Resources\ListingImages\Widgets;

use App\Models\ListingImage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Görseller — Medya havuzu, kapak dağılımı, GPS/EXIF gizliliği ve AI moderasyon metrikleri.
 */
class ListingImageStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = ListingImage::query()->count();
        $kapak = ListingImage::query()->where('is_cover', true)->count();
        $gpsVar = ListingImage::query()->whereNotNull('gps_lat')->whereNotNull('gps_lng')->count();
        $isaretli = ListingImage::query()->where('is_flagged', true)->count();
        $toplamBayt = (int) (ListingImage::query()->sum('size_bytes') ?: 0);

        if ($toplamBayt >= 1073741824) {
            $depolamaStr = round($toplamBayt / 1073741824, 2).' GB';
        } elseif ($toplamBayt >= 1048576) {
            $depolamaStr = round($toplamBayt / 1048576, 1).' MB';
        } else {
            $depolamaStr = round($toplamBayt / 1024, 1).' KB';
        }

        return [
            Stat::make('Toplam Görsel', (string) $toplam)
                ->description("{$kapak} kapak • {$depolamaStr} depolama")
                ->descriptionIcon('heroicon-m-photo')
                ->color('primary'),

            Stat::make('Kapak Görselleri', (string) $kapak)
                ->description('İlanların vitrin ana görselleri')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make('GPS & Konumlu', (string) $gpsVar)
                ->description($gpsVar > 0 ? "{$gpsVar} görselde koordinat mevcut" : 'Koordinat verisi bulunmuyor')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('info'),

            Stat::make('AI Moderasyon Uyarısı', (string) $isaretli)
                ->description($isaretli > 0 ? "{$isaretli} görsel onay bekliyor" : 'Tüm görseller moderasyondan geçti')
                ->descriptionIcon($isaretli > 0 ? 'heroicon-m-shield-exclamation' : 'heroicon-m-shield-check')
                ->color($isaretli > 0 ? 'danger' : 'success'),
        ];
    }
}
