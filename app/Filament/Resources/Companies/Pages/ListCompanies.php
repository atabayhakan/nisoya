<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Pages;

use App\Enums\JobStatus;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Companies\Widgets\CompanyStatsWidget;
use App\Filament\Resources\CompanyReviews\CompanyReviewResource;
use App\Filament\Resources\JobListings\JobListingResource;
use App\Models\Company;
use App\Models\JobListing;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CompanyStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $toplam = Company::query()->count();
        $dogrulanmis = Company::query()->where('is_verified', true)->count();
        $bekleyen = Company::query()->where('is_verified', false)->count();
        $ilanli = Company::query()->has('jobListings')->count();
        $yorumlu = Company::query()->has('reviews')->count();

        return [
            'hepsi' => Tab::make('Tüm Şirketler')
                ->badge((string) $toplam),

            'dogrulanmis' => Tab::make('Doğrulanmış Kurumsal')
                ->badge($dogrulanmis > 0 ? (string) $dogrulanmis : null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('is_verified', true)),

            'bekleyen' => Tab::make('Doğrulama Bekleyen')
                ->badge($bekleyen > 0 ? (string) $bekleyen : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('is_verified', false)),

            'ilanli' => Tab::make('Aktif İlanı Olanlar')
                ->badge($ilanli > 0 ? (string) $ilanli : null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $q) => $q->has('jobListings')),

            'yorumlu' => Tab::make('Değerlendirmesi Olanlar')
                ->badge($yorumlu > 0 ? (string) $yorumlu : null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $q) => $q->has('reviews')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Yeni Şirket Ekle')
                ->icon(Heroicon::OutlinedPlus),

            Action::make('aiKurumsalRapor')
                ->label('AI Kurumsal İstihbarat')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Kurumsal Ekosistem & İstihdam İstihbaratı')
                ->modalDescription(function (): HtmlString {
                    $toplam = Company::query()->count();
                    $dogrulanmis = Company::query()->where('is_verified', true)->count();
                    $oran = $toplam > 0 ? (int) round(($dogrulanmis / $toplam) * 100) : 0;
                    $toplamIlan = JobListing::query()->count();
                    $aktifIlan = JobListing::query()->where('status', JobStatus::Aktif)->count();

                    // Sektör dağılımı
                    $sektorler = DB::table('companies')
                        ->whereNotNull('sector')
                        ->selectRaw('sector, count(*) as adet')
                        ->groupBy('sector')
                        ->orderByDesc('adet')
                        ->limit(5)
                        ->get();

                    $sektorHtml = '';
                    if ($sektorler->isEmpty()) {
                        $sektorHtml = "<div class='text-xs text-gray-500 py-1'>Henüz sektörel dağılım verisi oluşmadı.</div>";
                    } else {
                        foreach ($sektorler as $s) {
                            $sektorHtml .= "<div class='flex items-center justify-between text-xs py-1 border-b border-gray-100 dark:border-gray-800'><span>{$s->sector}</span><span class='font-bold text-gray-700 dark:text-gray-300'>{$s->adet} şirket</span></div>";
                        }
                    }

                    return new HtmlString(
                        "<div class='space-y-3.5 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2.5 text-center'>"
                        ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-xl'>"
                        ."<div class='text-primary-700 dark:text-primary-400 font-bold text-lg'>{$toplam}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Kayıtlı Şirket</div>"
                        .'</div>'
                        ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl'>"
                        ."<div class='text-emerald-700 dark:text-emerald-400 font-bold text-lg'>%{$oran}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Onay Oranı ({$dogrulanmis})</div>"
                        .'</div>'
                        ."<div class='p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-xl'>"
                        ."<div class='text-blue-700 dark:text-blue-400 font-bold text-lg'>{$aktifIlan}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Aktif İlan ({$toplamIlan} Toplam)</div>"
                        .'</div>'
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700'>"
                        ."<div class='text-xs font-bold text-gray-900 dark:text-gray-100 mb-1.5'>🏢 Öne Çıkan Sektörler</div>"
                        .$sektorHtml
                        .'</div>'
                        ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs text-stone-600 dark:text-stone-300 space-y-1'>"
                        ."<strong class='text-stone-900 dark:text-stone-100'>💼 Diaspora Kurumsal Gücü:</strong>"
                        ."<p>Nisoya kurumsal şirket profilleri, diaspora Türklerinin Almanya, İngiltere ve Türkiye'deki iş gücünü birleştirir. Doğrulanmış mavi rozet alan şirketler güvenilir işveren olarak öne çıkarılır ve aday başvuruları doğrudan panelde toplanır.</p>"
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            Action::make('isIlanlari')
                ->label('İş İlanları')
                ->icon(Heroicon::OutlinedBriefcase)
                ->color('gray')
                ->url(fn (): string => JobListingResource::getUrl('index')),

            Action::make('degerlendirmeler')
                ->label('Değerlendirmeler')
                ->icon(Heroicon::OutlinedStar)
                ->color('gray')
                ->url(fn (): string => CompanyReviewResource::getUrl('index')),
        ];
    }
}
