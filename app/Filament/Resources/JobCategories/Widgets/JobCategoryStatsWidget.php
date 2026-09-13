<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobCategories\Widgets;

use App\Models\JobCategory;
use App\Models\JobListing;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * İş Kategorileri — Sektörel istihdam dağılımı ve aktif kategori metrikleri.
 */
class JobCategoryStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = JobCategory::query()->count();
        $aktif = JobCategory::query()->where('is_active', true)->count();
        $oran = $toplam > 0 ? (int) round(($aktif / $toplam) * 100) : 0;

        $ilanliKategoriler = JobCategory::query()->has('jobListings')->count();
        $toplamIlan = JobListing::query()->count();

        $populerKategori = JobCategory::query()
            ->withCount('jobListings')
            ->orderByDesc('job_listings_count')
            ->first();

        $populerCount = $populerKategori ? $populerKategori->job_listings_count : 0;
        $populerText = ($populerKategori && $populerCount > 0)
            ? "{$populerKategori->name} ({$populerCount} ilan)"
            : 'Henüz ilan dağılımı oluşmadı';

        return [
            Stat::make('Toplam İş Kategorisi', (string) $toplam)
                ->description("{$aktif} sektör sitede yayında (%{$oran})")
                ->descriptionIcon('heroicon-m-rectangle-group')
                ->color('primary'),

            Stat::make('Aktif Sektörler', (string) $aktif)
                ->description($aktif > 0 ? 'Adayların arama ve filtrelerinde aktif' : 'Aktif sektör bulunmuyor')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('İlan Bulunan Sektörler', (string) $ilanliKategoriler)
                ->description($toplamIlan > 0 ? "Toplam {$toplamIlan} iş ilanı mevcut" : 'Henüz yayında iş ilanı yok')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make('Lider İstihdam Sektörü', $populerCount > 0 ? (string) $populerCount : '—')
                ->description($populerText)
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}
