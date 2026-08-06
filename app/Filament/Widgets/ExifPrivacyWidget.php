<?php

namespace App\Filament\Widgets;

use App\Models\ListingImage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Admin dashboard — gizlilik uyarıları.
 * GPS'li görseller, hassas EXIF içeren görseller için hızlı görünüm.
 */
class ExifPrivacyWidget extends BaseWidget
{
    /**
     * Sıra merdiveni {@see \App\Providers\Filament\AdminPanelProvider} içinde.
     *
     * Eski yorum "En üstte" diyordu; değeri (-1) onu panonun EN ALTINA
     * koyuyordu. Gizlilik denetimi zaten günlük bir iş değil — yeri altta
     * doğru, yanlış olan yorumdu.
     */
    protected static ?int $sort = 90;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $totalImages = ListingImage::query()->count();
        $withGps = ListingImage::query()->where('had_gps', true)->count();
        $sensitive = ListingImage::query()->where('has_sensitive_exif', true)->count();
        $noExif = ListingImage::query()->where(function ($q) {
            $q->whereNull('exif_metadata')->orWhere('exif_metadata', '{}');
        })->count();

        // Son 7 günde yüklenen GPS'li görseller
        $recentGps = ListingImage::query()
            ->where('had_gps', true)
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        $gpsPercent = $totalImages > 0 ? round(($withGps / $totalImages) * 100, 1) : 0;

        return [
            Stat::make('GPS\'li Görsel', number_format($withGps))
                ->description("Toplamın %{$gpsPercent}'i · Son 7 gün: {$recentGps}")
                ->descriptionIcon('heroicon-m-map-pin')
                ->color($withGps > 0 ? 'warning' : 'success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('Hassas EXIF', number_format($sensitive))
                ->description('GPS, seri no vb. içeren')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($sensitive > 0 ? 'danger' : 'success'),

            Stat::make('Temiz Görseller', number_format($noExif))
                ->description('EXIF metadata yok')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success'),

            Stat::make('Toplam', number_format($totalImages))
                ->description('Tüm ilan görselleri')
                ->descriptionIcon('heroicon-m-photo')
                ->color('gray'),
        ];
    }
}
