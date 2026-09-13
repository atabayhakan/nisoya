<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobListings\Widgets;

use App\Enums\JobStatus;
use App\Models\JobApplication;
use App\Models\JobListing;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * İş İlanları — Açık pozisyon hacmi, aktiflik oranı, öne çıkan ilanlar ve aday başvuruları.
 */
class JobListingStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = JobListing::query()->count();
        $aktif = JobListing::query()->where('status', JobStatus::Aktif)->count();
        $oran = $toplam > 0 ? (int) round(($aktif / $toplam) * 100) : 0;

        $oneCikan = JobListing::query()->where('is_featured', true)->count();
        $toplamBasvuru = JobApplication::query()->count();
        $toplamPozisyon = (int) JobListing::query()->sum('positions');

        return [
            Stat::make('Toplam İş İlanı', (string) $toplam)
                ->description($toplam > 0 ? "{$toplamPozisyon} açık istihdam pozisyonu" : 'Henüz kayıtlı iş ilanı yok')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),

            Stat::make('Aktif Yayında', (string) $aktif)
                ->description($toplam > 0 ? "%{$oran} ilan yayında ve başvurulara açık" : 'Yayında aktif ilan bulunmuyor')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Öne Çıkan İlanlar', (string) $oneCikan)
                ->description($oneCikan > 0 ? "{$oneCikan} ilan vitrinde öne çıkarılıyor" : 'Vitrinde öne çıkan ilan yok')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning'),

            Stat::make('Toplam Aday Başvurusu', (string) $toplamBasvuru)
                ->description($toplamBasvuru > 0 ? "Platform genelinde {$toplamBasvuru} başvuru alındı" : 'Henüz aday başvurusu yapılmadı')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
        ];
    }
}
