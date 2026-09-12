<?php

declare(strict_types=1);

namespace App\Filament\Resources\YasamKonuOnerileri\Widgets;

use App\Models\YasamKonuOnerisi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Yaşam Konu Önerileri — Topluluk katkıları ve inceleme kuyruğu panosu.
 */
class YasamKonuOnerileriStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = YasamKonuOnerisi::query()->count();
        $bekleyen = YasamKonuOnerisi::query()->bekleyen()->count();
        $onaylanan = YasamKonuOnerisi::query()->onaylanan()->count();
        $reddedilen = YasamKonuOnerisi::query()->reddedilen()->count();
        $kaynakli = YasamKonuOnerisi::query()->whereNotNull('kaynak_url')->where('kaynak_url', '!=', '')->count();

        $onayOrani = $toplam > 0 ? (int) round(($onaylanan / $toplam) * 100) : 0;
        $kaynakOrani = $toplam > 0 ? (int) round(($kaynakli / $toplam) * 100) : 0;

        return [
            Stat::make('İnceleme Bekleyenler', "{$bekleyen} Öneri")
                ->description($bekleyen > 0 ? 'İşlem bekleyen yeni katkılar' : 'Tüm öneriler sonuçlandırıldı')
                ->descriptionIcon($bekleyen > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-badge')
                ->color($bekleyen > 0 ? 'warning' : 'success'),

            Stat::make('Onaylanan Katkılar', "{$onaylanan} / {$toplam} (%{$onayOrani})")
                ->description('Rehbere dahil edilen öneriler')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Reddedilen Öneriler', "{$reddedilen} Öneri")
                ->description('Uygun bulunmayan veya mükerrer')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($reddedilen > 0 ? 'danger' : 'gray'),

            Stat::make('Resmî Kaynaklı Katkı', "{$kaynakli} / {$toplam} (%{$kaynakOrani})")
                ->description('Doğrulama linki içeren öneriler')
                ->descriptionIcon('heroicon-m-link')
                ->color('info'),
        ];
    }
}
