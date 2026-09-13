<?php

namespace App\Filament\Resources\FeatureRequests\Pages;

use App\Enums\FeatureRequestStatus;
use App\Filament\Resources\FeatureRequests\FeatureRequestResource;
use App\Filament\Resources\FeatureRequests\Widgets\FeatureRequestStatsWidget;
use App\Filament\Resources\Listings\ListingResource;
use App\Models\FeatureRequest;
use App\Models\Listing;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ListFeatureRequests extends ListRecords
{
    protected static string $resource = FeatureRequestResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            FeatureRequestStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $toplam = FeatureRequest::query()->count();
        $bekleyen = FeatureRequest::query()->where('status', FeatureRequestStatus::Beklemede)->count();
        $onaylandi = FeatureRequest::query()->where('status', FeatureRequestStatus::Onaylandi)->count();
        $reddedildi = FeatureRequest::query()->where('status', FeatureRequestStatus::Reddedildi)->count();

        return [
            'hepsi' => Tab::make('Tüm Talepler')
                ->badge((string) $toplam),

            'beklemede' => Tab::make('İnceleme Bekleyenler')
                ->badge($bekleyen > 0 ? (string) $bekleyen : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', FeatureRequestStatus::Beklemede)),

            'onaylandi' => Tab::make('Onaylananlar (Vitrinde)')
                ->badge($onaylandi > 0 ? (string) $onaylandi : null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', FeatureRequestStatus::Onaylandi)),

            'reddedildi' => Tab::make('Reddedilenler')
                ->badge($reddedildi > 0 ? (string) $reddedildi : null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', FeatureRequestStatus::Reddedildi)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiVitrinRaporu')
                ->label('AI Vitrin & Gelir Raporu')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Vitrin Doluluğu ve Öne Çıkarma Talepleri Analizi')
                ->modalDescription(function (): HtmlString {
                    $toplam = FeatureRequest::query()->count();
                    $bekleyen = FeatureRequest::query()->where('status', FeatureRequestStatus::Beklemede)->count();
                    $onaylanan = FeatureRequest::query()->where('status', FeatureRequestStatus::Onaylandi)->count();

                    $vitrindeAktif = Listing::query()
                        ->where('is_featured', true)
                        ->where(function ($q): void {
                            $q->whereNull('featured_until')
                                ->orWhere('featured_until', '>', now());
                        })
                        ->count();

                    $ortalamaGun = (int) round((float) FeatureRequest::query()->avg('days') ?: 7);

                    return new HtmlString(
                        "<div class='space-y-3.5 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2.5 text-center'>"
                        ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-xl'>"
                        ."<div class='text-primary-700 dark:text-primary-400 font-bold text-lg'>{$vitrindeAktif}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Vitrinde Aktif İlan</div>"
                        .'</div>'
                        ."<div class='p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl'>"
                        ."<div class='text-amber-700 dark:text-amber-400 font-bold text-lg'>{$bekleyen}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Onay Bekleyen</div>"
                        .'</div>'
                        ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl'>"
                        ."<div class='text-emerald-700 dark:text-emerald-400 font-bold text-lg'>{$onaylanan}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Toplam Onay ({$toplam} Başvuru)</div>"
                        .'</div>'
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 text-xs space-y-1'>"
                        ."<div class='flex justify-between items-center'><span>Ortalama Talep Edilen Süre:</span><span class='font-bold text-gray-900 dark:text-gray-100'>{$ortalamaGun} Gün</span></div>"
                        ."<div class='flex justify-between items-center'><span>Vitrin Kapasite Durumu:</span><span class='font-bold text-emerald-600'>Aktif & Yayında</span></div>"
                        .'</div>'
                        ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs text-stone-600 dark:text-stone-300 space-y-1'>"
                        ."<strong class='text-stone-900 dark:text-stone-100'>🚀 Vitrin & SEO Görünürlük Kuralı:</strong>"
                        .'<p>Onaylanan talepler ilanın <code>is_featured</code> değerini aktif eder ve <code>featured_until</code> tarihini günceller. Süresi dolan ilanlar otomatik olarak standart sıralamaya döner. Onay bekleyen talepleri hızlı yanıtlamak pazar yeri gelirini ve kullanıcı memnuniyetini artırır.</p>'
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            Action::make('ilanlar')
                ->label('Tüm İlanlar')
                ->icon(Heroicon::OutlinedShoppingBag)
                ->color('gray')
                ->url(fn (): string => ListingResource::getUrl('index')),

            CreateAction::make()
                ->label('Yeni Talep Oluştur')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
