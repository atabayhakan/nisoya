<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Admin için EXIF Haritası.
 * GPS koordinatı içeren tüm görselleri Leaflet haritasında gösterir.
 * Marker'lar üzerinde görsel önizleme + ilan + kullanıcı bilgisi.
 * Cluster view ile duplicate tespiti (aynı yerde çok sayıda görsel).
 */
class ExifMapPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup = 'Pazaryeri';

    protected static ?string $navigationLabel = 'EXIF Haritası';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.exif-map';

    public function getTitle(): string
    {
        return 'EXIF Haritası (GPS\'li Görseller)';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Models\ListingImage::query()->withGps()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function getHeading(): string|Htmlable
    {
        return 'EXIF Haritası';
    }

    public function getSubheading(): ?string
    {
        $count = \App\Models\ListingImage::query()->withGps()->count();
        $clusters = \App\Models\ListingImage::query()->withGps()
            ->selectRaw('COUNT(*) as cnt, ROUND(gps_lat, 2) as lat, ROUND(gps_lng, 2) as lng')
            ->groupBy('lat', 'lng')
            ->havingRaw('cnt > 1')
            ->get();

        return sprintf(
            '%d GPS\'li görsel · %d konumda kümelendi',
            $count,
            $clusters->count()
        );
    }
}