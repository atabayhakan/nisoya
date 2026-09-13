<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyReviews\Widgets;

use App\Enums\ReviewStatus;
use App\Models\CompanyReview;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Şirket Değerlendirmeleri — Aday geri bildirimleri, onay durumu ve memnuniyet metrikleri.
 */
class CompanyReviewStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = CompanyReview::query()->count();
        $yayinda = CompanyReview::query()->where('status', ReviewStatus::Yayinda)->count();
        $gizli = CompanyReview::query()->where('status', ReviewStatus::Gizli)->count();
        $oran = $toplam > 0 ? (int) round(($yayinda / $toplam) * 100) : 0;

        $avg = $yayinda > 0
            ? round((float) CompanyReview::query()->where('status', ReviewStatus::Yayinda)->avg('rating'), 1)
            : 0.0;

        return [
            Stat::make('Toplam Değerlendirme', (string) $toplam)
                ->description("{$yayinda} onaylı • {$gizli} incelemede")
                ->descriptionIcon('heroicon-m-chat-bubble-bottom-center-text')
                ->color('primary'),

            Stat::make('Yayında (Onaylı)', (string) $yayinda)
                ->description($toplam > 0 ? "%{$oran} onaylanma oranı" : 'Yayında değerlendirme yok')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Gizli / Moderasyon', (string) $gizli)
                ->description($gizli > 0 ? "{$gizli} değerlendirme inceleniyor" : 'Bekleyen moderasyon yok')
                ->descriptionIcon($gizli > 0 ? 'heroicon-m-shield-exclamation' : 'heroicon-m-shield-check')
                ->color($gizli > 0 ? 'danger' : 'success'),

            Stat::make('Ortalama Puan', $yayinda > 0 ? "⭐ {$avg} / 5.0" : '—')
                ->description($yayinda > 0 ? 'Adayların genel memnuniyet puanı' : 'Puan verisi bulunmuyor')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}
