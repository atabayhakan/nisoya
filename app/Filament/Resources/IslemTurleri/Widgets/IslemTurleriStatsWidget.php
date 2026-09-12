<?php

declare(strict_types=1);

namespace App\Filament\Resources\IslemTurleri\Widgets;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * İşlem Türleri — Konsolosluk kategori şablonları ve temsilcilik kapsam metrikleri.
 */
class IslemTurleriStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplamTur = IslemTuru::query()->count();
        $aktifTur = IslemTuru::query()->where('is_active', true)->count();

        $kapsananTemsilcilik = TemsilcilikIslemi::query()->distinct('temsilcilik_id')->count('temsilcilik_id');
        $toplamTemsilcilik = Temsilcilik::query()->count();

        $toplamIcerik = TemsilcilikIslemi::query()->count();
        $yayindaIcerik = TemsilcilikIslemi::query()->where('status', TemsilcilikIslemi::STATUS_YAYIN)->count();
        $yayinOran = $toplamIcerik > 0 ? (int) round(($yayindaIcerik / $toplamIcerik) * 100) : 0;

        return [
            Stat::make('İşlem Türleri', "{$aktifTur} / {$toplamTur}")
                ->description('Tüm dünyada geçerli standart prosedürler')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('success'),

            Stat::make('Kapsanan Temsilcilik', "{$kapsananTemsilcilik} / {$toplamTemsilcilik}")
                ->description('İçerik girilen dış temsilcilikler')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary'),

            Stat::make('Toplam Rehber İçeriği', "{$toplamIcerik} Döküman")
                ->description('Konsolosluk işlem yönergesi')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Yayında & Doğrulanmış', "{$yayindaIcerik} (%{$yayinOran})")
                ->description('Ziyaretçiye açık aktif rehber')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($yayinOran >= 70 ? 'success' : 'warning'),
        ];
    }
}
