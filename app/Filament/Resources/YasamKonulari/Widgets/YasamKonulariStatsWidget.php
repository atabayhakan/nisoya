<?php

declare(strict_types=1);

namespace App\Filament\Resources\YasamKonulari\Widgets;

use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonusu;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Yaşam Konuları — Konu başlıkları, kategori dağılımı ve yerel içerik metrikleri.
 */
class YasamKonulariStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplamKonu = YasamKonusu::query()->count();
        $aktifKonu = YasamKonusu::query()->where('is_active', true)->count();

        $toplamKategori = YasamKategorisi::query()->count();
        $doluKategori = YasamKategorisi::has('konular')->count();

        $toplamIcerik = YasamKonuIcerigi::query()->count();
        $yayindaIcerik = YasamKonuIcerigi::query()->where('status', YasamKonuIcerigi::STATUS_YAYIN)->count();

        $iceriksiz = YasamKonusu::doesntHave('icerikler')->count();

        return [
            Stat::make('Yaşam Konuları', "{$aktifKonu} / {$toplamKonu}")
                ->description('Rehberde aktif sorular ve başlıklar')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('success'),

            Stat::make('Kapsanan Kategoriler', "{$doluKategori} / {$toplamKategori}")
                ->description('Konusu tanımlanmış ana başlıklar')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info'),

            Stat::make('Yerel Ülke İçerikleri', "{$yayindaIcerik} / {$toplamIcerik}")
                ->description('Farklı ülkeler için yayında olan rehberler')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success'),

            Stat::make('İçerik Bekleyenler', "{$iceriksiz} Konu")
                ->description($iceriksiz > 0 ? 'Henüz ülke içeriği girilmemiş' : 'Tüm konuların ülke içeriği hazır')
                ->descriptionIcon($iceriksiz > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-badge')
                ->color($iceriksiz > 0 ? 'warning' : 'success'),
        ];
    }
}
