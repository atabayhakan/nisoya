<?php

declare(strict_types=1);

namespace App\Filament\Resources\YasamKategorileri\Widgets;

use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonuOnerisi;
use App\Models\YasamKonusu;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Yaşam Kategorileri — Ana taksonomi, rehber konuları ve topluluk katkı metrikleri.
 */
class YasamKategorileriStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplamKategori = YasamKategorisi::query()->count();
        $aktifKategori = YasamKategorisi::query()->where('is_active', true)->count();

        $toplamKonu = YasamKonusu::query()->count();
        $aktifKonu = YasamKonusu::query()->where('is_active', true)->count();

        $toplamIcerik = YasamKonuIcerigi::query()->count();
        $yayindaIcerik = YasamKonuIcerigi::query()->where('status', YasamKonuIcerigi::STATUS_YAYIN)->count();
        $yayinOrani = $toplamIcerik > 0 ? (int) round(($yayindaIcerik / $toplamIcerik) * 100) : 0;

        $bekleyenOneri = YasamKonuOnerisi::query()->where('durum', YasamKonuOnerisi::DURUM_BEKLIYOR)->count();

        return [
            Stat::make('Yaşam Kategorileri', "{$aktifKategori} / {$toplamKategori}")
                ->description('Rehberde yayınlanan ana başlıklar')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('success'),

            Stat::make('Tanımlı Konular', "{$toplamKonu} Konu")
                ->description("{$aktifKonu} aktif yaşam konusu")
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('info'),

            Stat::make('Ülke Rehberleri', "{$yayindaIcerik} / {$toplamIcerik} (%{$yayinOrani})")
                ->description('Yayındaki yerelleştirilmiş içerikler')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),

            Stat::make('Topluluk Önerileri', "{$bekleyenOneri} Bekliyor")
                ->description($bekleyenOneri > 0 ? 'İnceleme bekleyen düzeltme önerileri' : 'Tüm öneriler incelendi')
                ->descriptionIcon('heroicon-m-chat-bubble-left-ellipsis')
                ->color($bekleyenOneri > 0 ? 'warning' : 'gray'),
        ];
    }
}
