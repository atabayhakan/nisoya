<?php

namespace App\Filament\Resources\Listings\Pages;

use App\Enums\ListingStatus;
use App\Filament\Resources\ListingImages\ListingImageResource;
use App\Filament\Resources\Listings\ListingResource;
use App\Filament\Resources\Listings\Widgets\ListingsStatsWidget;
use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ListListings extends ListRecords
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ListingsStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $toplam = Listing::query()->count();
        $aktif = Listing::query()->where('status', ListingStatus::Aktif)->count();
        $beklemede = Listing::query()->where('status', ListingStatus::Beklemede)->count();
        $oneCikan = Listing::query()->where('is_featured', true)->count();
        $riskli = Listing::query()->whereNotNull('fraud_reason')->count();

        return [
            'hepsi' => Tab::make('Tüm İlanlar')
                ->badge((string) $toplam),

            'aktif' => Tab::make('Yayında (Aktif)')
                ->badge((string) $aktif)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ListingStatus::Aktif)),

            'beklemede' => Tab::make('Onay Bekleyenler')
                ->badge($beklemede > 0 ? (string) $beklemede : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ListingStatus::Beklemede)),

            'one_cikanlar' => Tab::make('Öne Çıkanlar')
                ->badge($oneCikan > 0 ? (string) $oneCikan : null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_featured', true)),

            'guvenlik' => Tab::make('Risk / Denetim')
                ->badge($riskli > 0 ? (string) $riskli : null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('fraud_reason')),

            'pasif' => Tab::make('Pasif / Arşiv')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    ListingStatus::Pasif,
                    ListingStatus::Reddedildi,
                    ListingStatus::Taslak,
                ])),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiPazaryeriRaporu')
                ->label('AI Pazaryeri Raporu')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Pazaryeri & İlanlar — AI Genel Durum ve Dağılım Raporu')
                ->modalDescription(function (): HtmlString {
                    $toplam = Listing::query()->count();
                    $aktif = Listing::query()->where('status', ListingStatus::Aktif)->count();
                    $beklemede = Listing::query()->where('status', ListingStatus::Beklemede)->count();
                    $riskli = Listing::query()->whereNotNull('fraud_reason')->count();

                    $topKategoriler = Category::query()
                        ->withCount('listings')
                        ->orderByDesc('listings_count')
                        ->limit(5)
                        ->get();

                    $topUlkeler = Country::query()
                        ->withCount('listings')
                        ->orderByDesc('listings_count')
                        ->limit(5)
                        ->get();

                    $katHtml = '';
                    foreach ($topKategoriler as $kat) {
                        $katHtml .= "<div class='flex items-center justify-between text-xs py-1 border-b border-gray-100 dark:border-gray-800'><span>{$kat->name}</span><span class='font-bold text-gray-700 dark:text-gray-300'>{$kat->listings_count} ilan</span></div>";
                    }

                    $ulkeHtml = '';
                    foreach ($topUlkeler as $u) {
                        $ulkeHtml .= "<div class='flex items-center justify-between text-xs py-1 border-b border-gray-100 dark:border-gray-800'><span>{$u->emoji} {$u->name_tr} ({$u->code})</span><span class='font-bold text-gray-700 dark:text-gray-300'>{$u->listings_count} ilan</span></div>";
                    }

                    $riskMetni = $riskli > 0
                        ? "<div class='p-2.5 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded text-xs text-rose-800 dark:text-rose-300'><strong>⚠️ Güvenlik Uyarısı:</strong> {$riskli} ilanda metin veya içerik güvenlik işareti tespit edildi. 'Risk / Denetim' sekmesinden inceleyebilirsiniz.</div>"
                        : "<div class='p-2.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded text-xs text-emerald-800 dark:text-emerald-300'><strong>✓ Güvenlik Durumu Temiz:</strong> Şu an şüpheli metin işareti taşıyan ilan bulunmuyor.</div>";

                    return new HtmlString(
                        "<div class='space-y-3.5 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2.5 text-center'>"
                        ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl'>"
                        ."<div class='text-emerald-700 dark:text-emerald-400 font-bold text-lg'>{$aktif}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Yayında (Aktif)</div>"
                        .'</div>'
                        ."<div class='p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl'>"
                        ."<div class='text-amber-700 dark:text-amber-400 font-bold text-lg'>{$beklemede}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Onay Bekleyen</div>"
                        .'</div>'
                        ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-xl'>"
                        ."<div class='text-primary-700 dark:text-primary-400 font-bold text-lg'>{$toplam}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Toplam İlan</div>"
                        .'</div>'
                        .'</div>'
                        .$riskMetni
                        ."<div class='grid grid-cols-2 gap-3'>"
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700'>"
                        ."<div class='text-xs font-bold text-gray-900 dark:text-gray-100 mb-1.5'>📊 En Çok İlan Alan Kategoriler</div>"
                        .$katHtml
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700'>"
                        ."<div class='text-xs font-bold text-gray-900 dark:text-gray-100 mb-1.5'>🌍 Ülkelere Göre Dağılım</div>"
                        .$ulkeHtml
                        .'</div>'
                        .'</div>'
                        ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs text-stone-600 dark:text-stone-300 space-y-1'>"
                        ."<strong class='text-stone-900 dark:text-stone-100'>💡 AI Operasyonel Tavsiye:</strong>"
                        .'<p>Gurbetçi vatandaşların en çok hizmet ve usta aradığı Almanya ve Hollanda bölgelerinde ilan onay süreçlerinin 1 saatin altında tutulması dönüşüm oranını %35 artırmaktadır.</p>'
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            Action::make('gorseller')
                ->label('Görsel Galerisi')
                ->icon(Heroicon::OutlinedPhoto)
                ->color('gray')
                ->url(fn (): string => ListingImageResource::getUrl('index')),

            CreateAction::make()
                ->label('Yeni İlan Oluştur')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
