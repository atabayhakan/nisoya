<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobListings\Pages;

use App\Enums\JobStatus;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\JobCategories\JobCategoryResource;
use App\Filament\Resources\JobFeatureRequests\JobFeatureRequestResource;
use App\Filament\Resources\JobListings\JobListingResource;
use App\Filament\Resources\JobListings\Widgets\JobListingStatsWidget;
use App\Models\JobListing;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class ListJobListings extends ListRecords
{
    protected static string $resource = JobListingResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            JobListingStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $toplam = JobListing::query()->count();
        $aktif = JobListing::query()->where('status', JobStatus::Aktif)->count();
        $beklemede = JobListing::query()->where('status', JobStatus::Beklemede)->count();
        $oneCikan = JobListing::query()->where('is_featured', true)->count();
        $basvurulu = JobListing::query()->has('applications')->count();
        $kapali = JobListing::query()->whereIn('status', [JobStatus::Kapali, JobStatus::Dolu])->count();

        return [
            'hepsi' => Tab::make('Tüm İlanlar')
                ->badge((string) $toplam),

            'aktif' => Tab::make('Aktif Yayında')
                ->badge($aktif > 0 ? (string) $aktif : null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', JobStatus::Aktif)),

            'beklemede' => Tab::make('Onay / Beklemede')
                ->badge($beklemede > 0 ? (string) $beklemede : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', JobStatus::Beklemede)),

            'one_cikan' => Tab::make('Öne Çıkanlar')
                ->badge($oneCikan > 0 ? (string) $oneCikan : null)
                ->badgeColor('amber')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('is_featured', true)),

            'basvurulu' => Tab::make('Başvuru Alanlar')
                ->badge($basvurulu > 0 ? (string) $basvurulu : null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $q) => $q->has('applications')),

            'kapali' => Tab::make('Kapalı / Dolu')
                ->badge($kapali > 0 ? (string) $kapali : null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', [JobStatus::Kapali, JobStatus::Dolu])),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Yeni İş İlanı Ekle')
                ->icon(Heroicon::OutlinedPlus),

            Action::make('aiIstihdamRaporu')
                ->label('AI İstihdam & Yetenek Raporu')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Kariyer Portalı İstihdam ve Pozisyon Analizi')
                ->modalDescription(function (): HtmlString {
                    $toplam = JobListing::query()->count();
                    $aktif = JobListing::query()->where('status', JobStatus::Aktif)->count();
                    $toplamBasvuru = DB::table('job_applications')->count();
                    $uzaktan = JobListing::query()->where('is_remote', true)->count();
                    $oran = $toplam > 0 ? (int) round(($aktif / $toplam) * 100) : 0;

                    // Çalışma tipleri dağılımı
                    $tipler = DB::table('job_listings')
                        ->whereNotNull('employment_type')
                        ->selectRaw('employment_type, count(*) as adet')
                        ->groupBy('employment_type')
                        ->orderByDesc('adet')
                        ->get();

                    $tipHtml = '';
                    if ($tipler->isEmpty()) {
                        $tipHtml = "<div class='text-xs text-gray-500 py-1'>Henüz çalışma tipi verisi oluşmadı.</div>";
                    } else {
                        foreach ($tipler as $t) {
                            $tipLabel = match ($t->employment_type) {
                                'tam_zamanli' => 'Tam Zamanlı',
                                'yari_zamanli' => 'Yarı Zamanlı',
                                'sozlesmeli' => 'Sözleşmeli',
                                'staj' => 'Staj',
                                'serbest' => 'Serbest (Freelance)',
                                default => $t->employment_type,
                            };
                            $tipHtml .= "<div class='flex items-center justify-between text-xs py-1 border-b border-gray-100 dark:border-gray-800'><span>{$tipLabel}</span><span class='font-bold text-gray-700 dark:text-gray-300'>{$t->adet} ilan</span></div>";
                        }
                    }

                    return new HtmlString(
                        "<div class='space-y-3.5 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2.5 text-center'>"
                        ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-xl'>"
                        ."<div class='text-primary-700 dark:text-primary-400 font-bold text-lg'>{$toplam}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Toplam İlan</div>"
                        .'</div>'
                        ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl'>"
                        ."<div class='text-emerald-700 dark:text-emerald-400 font-bold text-lg'>%{$oran}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Aktif Yayında ({$aktif})</div>"
                        .'</div>'
                        ."<div class='p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-xl'>"
                        ."<div class='text-blue-700 dark:text-blue-400 font-bold text-lg'>{$toplamBasvuru}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Toplam Başvuru ({$uzaktan} Uzaktan)</div>"
                        .'</div>'
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700'>"
                        ."<div class='text-xs font-bold text-gray-900 dark:text-gray-100 mb-1.5'>💼 Çalışma Tipleri Dağılımı</div>"
                        .$tipHtml
                        .'</div>'
                        ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs text-stone-600 dark:text-stone-300 space-y-1'>"
                        ."<strong class='text-stone-900 dark:text-stone-100'>🎯 Diaspora Kariyer Ağı:</strong>"
                        ."<p>Avrupa'daki Türk girişimciler ve iş arayan profesyoneller bu platformda buluşur. Onaylanan her iş ilanı Google Jobs schema standartlarına uygun olarak arama motorlarında indekslenir ve diaspora üyelerine e-posta / push bildirimle ulaştırılır.</p>"
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            Action::make('sirketler')
                ->label('Şirketler')
                ->icon(Heroicon::OutlinedBuildingOffice2)
                ->color('gray')
                ->url(fn (): string => CompanyResource::getUrl('index')),

            Action::make('oneCikarmaTalepleri')
                ->label('Öne Çıkarma Talepleri')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->url(fn (): string => JobFeatureRequestResource::getUrl('index')),

            Action::make('kategoriler')
                ->label('Kategoriler')
                ->icon(Heroicon::OutlinedTag)
                ->color('gray')
                ->url(fn (): string => JobCategoryResource::getUrl('index')),
        ];
    }
}
