<?php

declare(strict_types=1);

namespace App\Filament\Resources\Deals\Widgets;

use App\Enums\DealStatus;
use App\Models\Deal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Anlaşmalar — Pazar yeri güvenli işlem hacmi, tamamlanma başarı oranı ve itiraz metrikleri.
 */
class DealStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = Deal::query()->count();
        $tamamlanan = Deal::query()->where('status', DealStatus::Tamamlandi)->count();
        $acik = Deal::query()->whereIn('status', [DealStatus::Teklif, DealStatus::Kabul])->count();
        $sorunlu = Deal::query()->where('status', DealStatus::Sorunlu)->orWhereNotNull('dispute_note')->count();
        $iptal = Deal::query()->where('status', DealStatus::Iptal)->count();
        $basariOrani = $toplam > 0 ? (int) round(($tamamlanan / $toplam) * 100) : 0;

        return [
            Stat::make('Toplam Anlaşma', (string) $toplam)
                ->description("{$tamamlanan} tamamlandı • {$iptal} iptal")
                ->descriptionIcon('heroicon-m-hand-raised')
                ->color('primary'),

            Stat::make('Tamamlanan (Başarılı)', (string) $tamamlanan)
                ->description("%{$basariOrani} başarıyla sonuçlanan işlem")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Açık & Bekleyen', (string) $acik)
                ->description('Teklif ve onay aşamasında')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Sorunlu / İtirazlar', (string) $sorunlu)
                ->description($sorunlu > 0 ? "{$sorunlu} anlaşmada arabuluculuk bekleniyor" : 'İtiraz veya sorun kaydı bulunmuyor')
                ->descriptionIcon($sorunlu > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-shield-check')
                ->color($sorunlu > 0 ? 'danger' : 'success'),
        ];
    }
}
