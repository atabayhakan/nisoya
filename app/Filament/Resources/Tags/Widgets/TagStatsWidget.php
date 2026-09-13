<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tags\Widgets;

use App\Models\Tag;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Etiketler — Taksonomi kapsamı, ilan kullanım yoğunluğu ve yetim etiket metrikleri.
 */
class TagStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = Tag::query()->count();
        $ilanli = Tag::query()->has('listings')->count();
        $bosta = Tag::query()->doesntHave('listings')->count();
        $kullanimOrani = $toplam > 0 ? (int) round(($ilanli / $toplam) * 100) : 0;

        /** @var Tag|null $enPopuler */
        $enPopuler = Tag::query()
            ->withCount('listings')
            ->orderByDesc('listings_count')
            ->first();

        $enPopulerAciklama = ($enPopuler && $enPopuler->listings_count > 0)
            ? "#{$enPopuler->name} ({$enPopuler->listings_count} ilan)"
            : 'Henüz etiketli ilan yok';

        return [
            Stat::make('Toplam Etiket', (string) $toplam)
                ->description('Pazar yeri arama & filtreleme etiketleri')
                ->descriptionIcon('heroicon-m-hashtag')
                ->color('primary'),

            Stat::make('İlanlı Etiketler', "{$ilanli} / {$toplam}")
                ->description("%{$kullanimOrani} aktif kullanım oranı")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($ilanli > 0 ? 'success' : 'gray'),

            Stat::make('Boşta Kalanlar', (string) $bosta)
                ->description($bosta > 0 ? "{$bosta} etikette henüz ilan bulunmuyor" : 'Tüm etiketler aktif kullanımda')
                ->descriptionIcon($bosta > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-shield-check')
                ->color($bosta > 0 ? 'warning' : 'success'),

            Stat::make('En Popüler Etiket', $enPopuler && $enPopuler->listings_count > 0 ? "#{$enPopuler->name}" : '—')
                ->description($enPopulerAciklama)
                ->descriptionIcon('heroicon-m-fire')
                ->color('info'),
        ];
    }
}
