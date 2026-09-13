<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyReviews\Pages;

use App\Enums\ReviewStatus;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\CompanyReviews\CompanyReviewResource;
use App\Filament\Resources\CompanyReviews\Widgets\CompanyReviewStatsWidget;
use App\Filament\Resources\JobListings\JobListingResource;
use App\Models\CompanyReview;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class ListCompanyReviews extends ListRecords
{
    protected static string $resource = CompanyReviewResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CompanyReviewStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $toplam = CompanyReview::query()->count();
        $yayinda = CompanyReview::query()->where('status', ReviewStatus::Yayinda)->count();
        $gizli = CompanyReview::query()->where('status', ReviewStatus::Gizli)->count();
        $yuksek = CompanyReview::query()->where('rating', '>=', 4)->count();
        $dusuk = CompanyReview::query()->where('rating', '<=', 2)->count();

        return [
            'hepsi' => Tab::make('Tüm Değerlendirmeler')
                ->badge((string) $toplam),

            'yayinda' => Tab::make('Yayında (Onaylı)')
                ->badge($yayinda > 0 ? (string) $yayinda : null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', ReviewStatus::Yayinda)),

            'gizli' => Tab::make('Gizli / Moderasyon')
                ->badge($gizli > 0 ? (string) $gizli : null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', ReviewStatus::Gizli)),

            'yuksek_puan' => Tab::make('Yüksek Puan (4-5 ⭐)')
                ->badge($yuksek > 0 ? (string) $yuksek : null)
                ->badgeColor('amber')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('rating', '>=', 4)),

            'dusuk_puan' => Tab::make('Düşük Puan (1-2 ⭐)')
                ->badge($dusuk > 0 ? (string) $dusuk : null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('rating', '<=', 2)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Yeni Değerlendirme Ekle')
                ->icon(Heroicon::OutlinedPlus),

            Action::make('aiYorumAnalizi')
                ->label('AI Yorum & Memnuniyet Analizi')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Kurumsal Memnuniyet ve Geri Bildirim İstihbaratı')
                ->modalDescription(function (): HtmlString {
                    $toplam = CompanyReview::query()->count();
                    $yayinda = CompanyReview::query()->where('status', ReviewStatus::Yayinda)->count();
                    $avg = $yayinda > 0
                        ? round((float) CompanyReview::query()->where('status', ReviewStatus::Yayinda)->avg('rating'), 1)
                        : 0.0;

                    // Puan dağılımı
                    $puanlar = DB::table('company_reviews')
                        ->selectRaw('rating, count(*) as adet')
                        ->groupBy('rating')
                        ->orderByDesc('rating')
                        ->get();

                    $puanHtml = '';
                    if ($puanlar->isEmpty()) {
                        $puanHtml = "<div class='text-xs text-gray-500 py-1'>Henüz değerlendirme verisi oluşmadı.</div>";
                    } else {
                        foreach ($puanlar as $p) {
                            $yildiz = str_repeat('⭐', (int) $p->rating);
                            $puanHtml .= "<div class='flex items-center justify-between text-xs py-1 border-b border-gray-100 dark:border-gray-800'><span>{$yildiz} ({$p->rating} Yıldız)</span><span class='font-bold text-gray-700 dark:text-gray-300'>{$p->adet} aday</span></div>";
                        }
                    }

                    return new HtmlString(
                        "<div class='space-y-3.5 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2.5 text-center'>"
                        ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-xl'>"
                        ."<div class='text-primary-700 dark:text-primary-400 font-bold text-lg'>{$toplam}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Toplam Yorum</div>"
                        .'</div>'
                        ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl'>"
                        ."<div class='text-emerald-700 dark:text-emerald-400 font-bold text-lg'>⭐ {$avg}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Ortalama Puan</div>"
                        .'</div>'
                        ."<div class='p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-xl'>"
                        ."<div class='text-blue-700 dark:text-blue-400 font-bold text-lg'>{$yayinda}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Yayındaki İnceleme</div>"
                        .'</div>'
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700'>"
                        ."<div class='text-xs font-bold text-gray-900 dark:text-gray-100 mb-1.5'>⭐ Puanlama Dağılımı</div>"
                        .$puanHtml
                        .'</div>'
                        ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs text-stone-600 dark:text-stone-300 space-y-1'>"
                        ."<strong class='text-stone-900 dark:text-stone-100'>🛡️ Güvenli İşveren Ekosistemi:</strong>"
                        ."<p>Nisoya platformunda yalnızca ilgili şirkete daha önce başvurmuş veya mülakat sürecine katılmış gerçek adaylar şirketleri değerlendirebilir. Asılsız karalama veya hakaret içeren şüpheli yorumları <em>'Gizle'</em> butonuyla moderasyona alabilirsiniz.</p>"
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

            Action::make('isIlanlari')
                ->label('İş İlanları')
                ->icon(Heroicon::OutlinedBriefcase)
                ->color('gray')
                ->url(fn (): string => JobListingResource::getUrl('index')),
        ];
    }
}
