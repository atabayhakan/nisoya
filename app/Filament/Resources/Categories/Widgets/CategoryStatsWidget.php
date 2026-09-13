<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Widgets;

use App\Enums\CategoryType;
use App\Models\Category;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Kategoriler — Taksonomi dağılımı, içerik doluluğu ve pazar yeri ağaç metrikleri.
 */
class CategoryStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = Category::query()->count();
        $anaKategoriler = Category::query()->whereNull('parent_id')->count();
        $altKategoriler = Category::query()->whereNotNull('parent_id')->count();

        $hizmetSayisi = Category::query()->where('type', CategoryType::Hizmet)->count();
        $urunSayisi = Category::query()->whereIn('type', [
            CategoryType::Urun,
            CategoryType::Ikisi,
            CategoryType::Emlak,
            CategoryType::Vasita,
        ])->count();

        $doluKategoriler = Category::query()->has('listings')->count();
        $bosKategoriler = Category::query()->doesntHave('listings')->count();

        $aktifSayisi = Category::query()->where('is_active', true)->count();
        $aktifOran = $toplam > 0 ? (int) round(($aktifSayisi / $toplam) * 100) : 0;

        return [
            Stat::make('Toplam Kategori', (string) $toplam)
                ->description("{$anaKategoriler} Ana • {$altKategoriler} Alt Kategori")
                ->descriptionIcon('heroicon-m-folder')
                ->color('primary'),

            Stat::make('Hizmet Kategorileri', (string) $hizmetSayisi)
                ->description('Usta, tercüme, danışmanlık vb.')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('info'),

            Stat::make('Ürün & Ticaret', (string) $urunSayisi)
                ->description('İkinci el, emlak ve vasıta')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),

            Stat::make('İlanlı Kategoriler', "{$doluKategoriler} / {$toplam}")
                ->description($bosKategoriler > 0 ? "{$bosKategoriler} kategoride henüz ilan yok" : 'Tüm kategorilerde aktif ilan var')
                ->descriptionIcon($bosKategoriler > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-badge')
                ->color($bosKategoriler > 0 ? 'warning' : 'success'),

            Stat::make('Aktiflik Oranı', "%{$aktifOran}")
                ->description("{$aktifSayisi} kategori yayında ve seçilebilir")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
