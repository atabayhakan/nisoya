<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Widgets;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\JobListing;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * Şirketler — Kurumsal firma hacmi, doğrulama oranları, iş ilanı sayısı ve değerlendirmeler.
 */
class CompanyStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = Company::query()->count();
        $dogrulanmis = Company::query()->where('is_verified', true)->count();
        $bekleyen = $toplam - $dogrulanmis;
        $oran = $toplam > 0 ? (int) round(($dogrulanmis / $toplam) * 100) : 0;

        $aktifIlanlar = JobListing::query()->where('status', JobStatus::Aktif)->count();
        $toplamIlanlar = JobListing::query()->count();

        $toplamYorum = (int) DB::table('company_reviews')->where('status', 'yayinda')->count();
        $ortalamaPuan = $toplamYorum > 0
            ? round((float) DB::table('company_reviews')->where('status', 'yayinda')->avg('rating'), 1)
            : 0.0;

        return [
            Stat::make('Toplam Kurumsal Şirket', (string) $toplam)
                ->description($toplam > 0 ? "{$dogrulanmis} onaylı • {$bekleyen} incelemede" : 'Henüz kurumsal profil kaydı yok')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make('Doğrulanmış Firmalar', (string) $dogrulanmis)
                ->description($toplam > 0 ? "%{$oran} onaylı mavi rozet oranı" : 'Doğrulanmış firma bulunmuyor')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Aktif İş İlanları', (string) $aktifIlanlar)
                ->description($toplamIlanlar > 0 ? "Toplam {$toplamIlanlar} ilanın {$aktifIlanlar}'i yayında" : 'Yayında iş ilanı bulunmuyor')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make('Kurumsal Değerlendirmeler', (string) $toplamYorum)
                ->description($toplamYorum > 0 ? "Ortalama puan: ⭐ {$ortalamaPuan} / 5.0" : 'Henüz onaylı değerlendirme yok')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}
