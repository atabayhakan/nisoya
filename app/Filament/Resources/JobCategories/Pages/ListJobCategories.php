<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobCategories\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\JobCategories\JobCategoryResource;
use App\Filament\Resources\JobCategories\Widgets\JobCategoryStatsWidget;
use App\Filament\Resources\JobListings\JobListingResource;
use App\Models\JobCategory;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class ListJobCategories extends ListRecords
{
    protected static string $resource = JobCategoryResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            JobCategoryStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $toplam = JobCategory::query()->count();
        $aktif = JobCategory::query()->where('is_active', true)->count();
        $pasif = JobCategory::query()->where('is_active', false)->count();
        $ilanli = JobCategory::query()->has('jobListings')->count();
        $bos = JobCategory::query()->doesntHave('jobListings')->count();

        return [
            'hepsi' => Tab::make('Tüm Kategoriler')
                ->badge((string) $toplam),

            'aktif' => Tab::make('Aktif Sektörler')
                ->badge($aktif > 0 ? (string) $aktif : null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),

            'pasif' => Tab::make('Pasif / Gizli')
                ->badge($pasif > 0 ? (string) $pasif : null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),

            'ilanli' => Tab::make('İlan Bulunanlar')
                ->badge($ilanli > 0 ? (string) $ilanli : null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('jobListings')),

            'bos' => Tab::make('Boş Sektörler')
                ->badge($bos > 0 ? (string) $bos : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('jobListings')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Yeni İş Kategorisi Ekle')
                ->icon(Heroicon::OutlinedPlus),

            Action::make('aiKategoriRaporu')
                ->label('AI Sektör & İstihdam Analizi')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Kariyer Portalı Sektörel Dağılım ve Pazar Analizi')
                ->modalDescription(function (): HtmlString {
                    $toplam = JobCategory::query()->count();
                    $aktif = JobCategory::query()->where('is_active', true)->count();
                    $oran = $toplam > 0 ? (int) round(($aktif / $toplam) * 100) : 0;
                    $toplamIlan = DB::table('job_listings')->count();

                    // Kategorilere göre ilan sayıları
                    $kategoriler = JobCategory::query()
                        ->withCount('jobListings')
                        ->orderByDesc('job_listings_count')
                        ->limit(6)
                        ->get();

                    $katHtml = '';
                    if ($kategoriler->isEmpty()) {
                        $katHtml = "<div class='text-xs text-gray-500 py-1'>Kayıtlı kategori bulunmuyor.</div>";
                    } else {
                        foreach ($kategoriler as $k) {
                            $katHtml .= "<div class='flex items-center justify-between text-xs py-1 border-b border-gray-100 dark:border-gray-800'><span>🏷️ {$k->name}</span><span class='font-bold text-gray-700 dark:text-gray-300'>{$k->job_listings_count} ilan</span></div>";
                        }
                    }

                    return new HtmlString(
                        "<div class='space-y-3.5 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2.5 text-center'>"
                        ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-xl'>"
                        ."<div class='text-primary-700 dark:text-primary-400 font-bold text-lg'>{$toplam}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Toplam Kategori</div>"
                        .'</div>'
                        ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl'>"
                        ."<div class='text-emerald-700 dark:text-emerald-400 font-bold text-lg'>%{$oran}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Aktiflik Oranı ({$aktif})</div>"
                        .'</div>'
                        ."<div class='p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-xl'>"
                        ."<div class='text-blue-700 dark:text-blue-400 font-bold text-lg'>{$toplamIlan}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Toplam İlan Hacmi</div>"
                        .'</div>'
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700'>"
                        ."<div class='text-xs font-bold text-gray-900 dark:text-gray-100 mb-1.5'>📊 En Çok İlan Açılan Sektörler</div>"
                        .$katHtml
                        .'</div>'
                        ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs text-stone-600 dark:text-stone-300 space-y-1'>"
                        ."<strong class='text-stone-900 dark:text-stone-100'>💡 Sektörel İpuçları:</strong>"
                        .'<p>Gurbetçi diasporasında en çok aranan istihdam alanları lojistik, gastronomi, inşaat/tadilat ve bilişim sektörleridir. Boş kalan kategoriler için işverenlere yönelik sektörel teşvikler planlayabilirsiniz.</p>'
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

            Action::make('sirketler')
                ->label('Şirketler')
                ->icon(Heroicon::OutlinedBuildingOffice2)
                ->color('gray')
                ->url(fn (): string => CompanyResource::getUrl('index')),
        ];
    }
}
