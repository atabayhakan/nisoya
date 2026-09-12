<?php

declare(strict_types=1);

namespace App\Filament\Resources\RehberGeriBildirimleri\Widgets;

use App\Models\RehberGeriBildirimi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Geri Bildirimler — Konsolosluk rehberi kullanıcı bildirimleri ve kalite sinyalleri panosu.
 */
class RehberGeriBildirimleriStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = RehberGeriBildirimi::query()->count();
        $bekleyen = RehberGeriBildirimi::query()->where('incelendi', false)->count();
        $incelenen = RehberGeriBildirimi::query()->where('incelendi', true)->count();
        $kritik = RehberGeriBildirimi::query()
            ->where('incelendi', false)
            ->whereIn('tur', ['guncel_degil', 'hata'])
            ->count();

        $cozumOrani = $toplam > 0 ? (int) round(($incelenen / $toplam) * 100) : 100;

        return [
            Stat::make('İnceleme Bekleyenler', "{$bekleyen} Bildirim")
                ->description($bekleyen > 0 ? 'İşlem bekleyen yeni bildirimler' : 'Tüm bildirimler incelendi')
                ->descriptionIcon($bekleyen > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-badge')
                ->color($bekleyen > 0 ? 'warning' : 'success'),

            Stat::make('Kritik Sinyaller', "{$kritik} Acil")
                ->description('Hata veya güncel değil uyarıları')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($kritik > 0 ? 'danger' : 'success'),

            Stat::make('İncelendi / Çözüldü', "{$incelenen} / {$toplam} (%{$cozumOrani})")
                ->description('Teyit edilip işleme alınanlar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Toplam Katkı Hacmi', "{$toplam} Geri Bildirim")
                ->description('Vatandaş ve gurbetçi katkıları')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info'),
        ];
    }
}
