<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobFeatureRequests\Pages;

use App\Enums\FeatureRequestStatus;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\JobFeatureRequests\JobFeatureRequestResource;
use App\Filament\Resources\JobFeatureRequests\Widgets\JobFeatureRequestStatsWidget;
use App\Filament\Resources\JobListings\JobListingResource;
use App\Models\JobFeatureRequest;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ListJobFeatureRequests extends ListRecords
{
    protected static string $resource = JobFeatureRequestResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            JobFeatureRequestStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $toplam = JobFeatureRequest::query()->count();
        $bekleyen = JobFeatureRequest::query()->where('status', FeatureRequestStatus::Beklemede)->count();
        $onaylanan = JobFeatureRequest::query()->where('status', FeatureRequestStatus::Onaylandi)->count();
        $reddedilen = JobFeatureRequest::query()->where('status', FeatureRequestStatus::Reddedildi)->count();

        return [
            'hepsi' => Tab::make('Tüm Talepler')
                ->badge((string) $toplam),

            'beklemede' => Tab::make('Beklemede (İnceleme)')
                ->badge($bekleyen > 0 ? (string) $bekleyen : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', FeatureRequestStatus::Beklemede)),

            'onaylandi' => Tab::make('Onaylananlar (Vitrinde)')
                ->badge($onaylanan > 0 ? (string) $onaylanan : null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', FeatureRequestStatus::Onaylandi)),

            'reddedildi' => Tab::make('Reddedilenler')
                ->badge($reddedilen > 0 ? (string) $reddedilen : null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', FeatureRequestStatus::Reddedildi)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Yeni Talep Ekle')
                ->icon(Heroicon::OutlinedPlus),

            Action::make('vitrinRehberi')
                ->label('Vitrin Operasyon Rehberi')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('İş İlanı Vitrin & Öne Çıkarma Operasyonu')
                ->modalDescription(new HtmlString(
                    "<div class='space-y-3.5 text-sm'>"
                    ."<div class='p-3.5 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-xl space-y-1'>"
                    ."<strong class='text-primary-800 dark:text-primary-300'>🚀 Vitrinde Öne Çıkarmanın Avantajları:</strong>"
                    ."<p class='text-xs text-gray-600 dark:text-gray-300'>Öne çıkarılan iş ilanları arama sonuçlarında, ilgili sektör sayfalarında ve ana sayfada en üst sıraya sabitlenir, aday görüntülenme ve başvuru oranını 4 katına kadar artırır.</p>"
                    .'</div>'
                    ."<div class='grid grid-cols-2 gap-2 text-xs'>"
                    ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl'><strong>✅ Onaylama İşlemi:</strong> Talep onaylandığında ilanın `is_featured` değeri true yapılır ve talep edilen gün sayısı (7, 14, 30 vb.) kadar vitrin süresi tanınır.</div>"
                    ."<div class='p-3 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-xl'><strong>❌ Reddetme İşlemi:</strong> Talep uygun bulunmazsa reddedilir; ilan standart yayınına devam eder ancak vitrin süresi almaz.</div>"
                    .'</div>'
                    ."<div class='p-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-500'>"
                    .'💡 <em>Not:</em> Süresi biten öne çıkarılmış ilanlar zamanlanmış <code>job-listings:expire-featured</code> artisan komutuyla otomatik olarak vitrinden standart görünüme düşürülür.'
                    .'</div>'
                    .'</div>'
                ))
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
