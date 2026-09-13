<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\ListingImage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * EXIF Haritası — Coğrafi istihbarat, hassas EXIF dağılımı ve şüpheli kümelenme metrikleri.
 */
class ExifMapStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplamGps = ListingImage::query()->withGps()->count();
        $hassasExif = ListingImage::query()->where('has_sensitive_exif', true)->count();
        $cozumlenenKonumlar = ListingImage::query()->whereNotNull('reverse_city')->count();

        $kumelerCount = DB::query()
            ->fromSub(
                DB::table('listing_images')
                    ->whereNotNull('gps_lat')
                    ->whereNotNull('gps_lng')
                    ->selectRaw('ROUND(gps_lat, 2) as lat, ROUND(gps_lng, 2) as lng')
                    ->groupBy('lat', 'lng')
                    ->havingRaw('COUNT(*) > 1'),
                'clusters'
            )
            ->count();

        return [
            Stat::make('GPS Koordinatlı Görseller', (string) $toplamGps)
                ->description($toplamGps > 0 ? "{$toplamGps} görselde koordinat mevcut" : 'Fotoğraflarda koordinat verisi yok')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('primary'),

            Stat::make('Hassas EXIF İçerenler', (string) $hassasExif)
                ->description($hassasExif > 0 ? "{$hassasExif} görselde cihaz/konum verisi var" : 'Tüm fotoğraflar EXIF açısından temiz')
                ->descriptionIcon($hassasExif > 0 ? 'heroicon-m-shield-exclamation' : 'heroicon-m-shield-check')
                ->color($hassasExif > 0 ? 'danger' : 'success'),

            Stat::make('Coğrafi Kümeler (Clusters)', (string) $kumelerCount)
                ->description($kumelerCount > 0 ? "{$kumelerCount} noktada görsel yoğunlaşması" : 'Kümelenmiş kopya nokta bulunmuyor')
                ->descriptionIcon('heroicon-m-squares-plus')
                ->color($kumelerCount > 0 ? 'warning' : 'gray'),

            Stat::make('Çözümlenen Konumlar', (string) $cozumlenenKonumlar)
                ->description("{$cozumlenenKonumlar} görselin şehri tespit edildi")
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),
        ];
    }
}
