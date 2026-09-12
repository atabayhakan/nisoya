<?php

declare(strict_types=1);

namespace App\Filament\Resources\TemsilcilikIslemleri\Widgets;

use App\Models\RehberGeriBildirimi;
use App\Models\TemsilcilikIslemi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * İşlem İçerikleri — Konsolosluk rehber dökümanları ve doğrulama metrikleri.
 */
class TemsilcilikIslemleriStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = TemsilcilikIslemi::query()->count();
        $yayinda = TemsilcilikIslemi::query()->where('status', TemsilcilikIslemi::STATUS_YAYIN)->count();
        $taslak = TemsilcilikIslemi::query()->where('status', TemsilcilikIslemi::STATUS_TASLAK)->count();
        $yayinOran = $toplam > 0 ? (int) round(($yayinda / $toplam) * 100) : 0;

        $bayat = TemsilcilikIslemi::query()
            ->where('status', TemsilcilikIslemi::STATUS_YAYIN)
            ->where(fn ($q) => $q->whereNull('dogrulanma_tarihi')->orWhere('dogrulanma_tarihi', '<', now()->subDays(TemsilcilikIslemi::BAYATLIK_GUN)))
            ->count();
        $guncel = max(0, $yayinda - $bayat);

        $toplamBildirim = RehberGeriBildirimi::query()->count();
        $bekleyenBildirim = RehberGeriBildirimi::query()->where('incelendi', false)->count();

        return [
            Stat::make('Yayındaki Rehberler', "{$yayinda} / {$toplam} (%{$yayinOran})")
                ->description('Sitede yayında olan rehberler')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Taslak / Bekleyen', "{$taslak} İçerik")
                ->description('Resmi kaynaktan doğrulama bekliyor')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Tazelik Durumu', "{$guncel} Güncel / {$bayat} Bayat")
                ->description(TemsilcilikIslemi::BAYATLIK_GUN.' günü aşanlar bayat uyarısı alır')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($bayat > 0 ? 'warning' : 'success'),

            Stat::make('Geri Bildirimler', "{$bekleyenBildirim} İncelenmemiş")
                ->description("Toplam {$toplamBildirim} kullanıcı geri bildirimi")
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color($bekleyenBildirim > 0 ? 'danger' : 'gray'),
        ];
    }
}
