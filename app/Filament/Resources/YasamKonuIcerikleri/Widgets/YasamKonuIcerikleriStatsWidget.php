<?php

declare(strict_types=1);

namespace App\Filament\Resources\YasamKonuIcerikleri\Widgets;

use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonuOnerisi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Yaşam Konu İçerikleri — Ülke bazlı yerel rehber içerikleri, tazelik ve doğrulama metrikleri.
 */
class YasamKonuIcerikleriStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = YasamKonuIcerigi::query()->count();
        $yayinda = YasamKonuIcerigi::query()->where('status', YasamKonuIcerigi::STATUS_YAYIN)->count();
        $taslak = YasamKonuIcerigi::query()->where('status', YasamKonuIcerigi::STATUS_TASLAK)->count();
        $yayinOrani = $toplam > 0 ? (int) round(($yayinda / $toplam) * 100) : 0;

        $bayat = YasamKonuIcerigi::query()->bayat()->count();
        $guncel = max(0, $yayinda - $bayat);

        $toplamOneri = YasamKonuOnerisi::query()->count();
        $bekleyenOneri = YasamKonuOnerisi::query()->where('durum', YasamKonuOnerisi::DURUM_BEKLIYOR)->count();

        return [
            Stat::make('Yayındaki İçerikler', "{$yayinda} / {$toplam} (%{$yayinOrani})")
                ->description('Sitede yayında olan rehber dökümanları')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Taslak / Bekleyen', "{$taslak} İçerik")
                ->description('Resmî kaynaktan doğrulama bekliyor')
                ->descriptionIcon('heroicon-m-clock')
                ->color($taslak > 0 ? 'warning' : 'gray'),

            Stat::make('Tazelik Durumu', "{$guncel} Güncel / {$bayat} Bayat")
                ->description(YasamKonuIcerigi::BAYATLIK_GUN.' günü aşanlar bayat uyarısı alır')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($bayat > 0 ? 'warning' : 'success'),

            Stat::make('Topluluk Önerileri', "{$bekleyenOneri} Bekliyor")
                ->description("Toplam {$toplamOneri} kullanıcı önerisi")
                ->descriptionIcon('heroicon-m-chat-bubble-left-ellipsis')
                ->color($bekleyenOneri > 0 ? 'warning' : 'gray'),
        ];
    }
}
