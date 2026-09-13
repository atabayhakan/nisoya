<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Enums\CategoryType;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Widgets\CategoryStatsWidget;
use App\Filament\Resources\Listings\ListingResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CategoryStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $toplam = Category::query()->count();
        $anaKategoriler = Category::query()->whereNull('parent_id')->count();
        $altKategoriler = Category::query()->whereNotNull('parent_id')->count();
        $hizmetSayisi = Category::query()->where('type', CategoryType::Hizmet)->count();
        $urunSayisi = Category::query()->whereIn('type', [
            CategoryType::Urun,
            CategoryType::Ikisi,
            CategoryType::Emlak,
            CategoryType::Vasita,
        ])->count();
        $bosKategoriler = Category::query()->doesntHave('listings')->count();

        return [
            'hepsi' => Tab::make('Tüm Kategoriler')
                ->badge((string) $toplam),

            'ana_kategoriler' => Tab::make('Ana Kategoriler')
                ->badge((string) $anaKategoriler)
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNull('parent_id')),

            'alt_kategoriler' => Tab::make('Alt Kategoriler')
                ->badge((string) $altKategoriler)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('parent_id')),

            'hizmet' => Tab::make('Hizmet')
                ->badge((string) $hizmetSayisi)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('type', CategoryType::Hizmet)),

            'urun' => Tab::make('Ürün & Ticaret')
                ->badge((string) $urunSayisi)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('type', [
                    CategoryType::Urun,
                    CategoryType::Ikisi,
                    CategoryType::Emlak,
                    CategoryType::Vasita,
                ])),

            'bos' => Tab::make('İçeriksiz (0 İlan)')
                ->badge($bosKategoriler > 0 ? (string) $bosKategoriler : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $q) => $q->doesntHave('listings')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiKategoriRaporu')
                ->label('AI Taksonomi Raporu')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Kategori Ağacı — AI Taksonomi ve Doluluk Analizi')
                ->modalDescription(function (): HtmlString {
                    $toplam = Category::query()->count();
                    $ana = Category::query()->whereNull('parent_id')->count();
                    $alt = Category::query()->whereNotNull('parent_id')->count();

                    $dolu = Category::query()->has('listings')->count();
                    $bos = Category::query()->doesntHave('listings')->count();

                    $topKategoriler = Category::query()
                        ->withCount('listings')
                        ->orderByDesc('listings_count')
                        ->limit(5)
                        ->get();

                    $boslar = Category::query()
                        ->doesntHave('listings')
                        ->limit(6)
                        ->pluck('name')
                        ->implode(', ');

                    $katHtml = '';
                    foreach ($topKategoriler as $k) {
                        $icon = $k->icon ?: '📁';
                        $katHtml .= "<div class='flex items-center justify-between text-xs py-1 border-b border-gray-100 dark:border-gray-800'><span>{$icon} {$k->name}</span><span class='font-bold text-gray-700 dark:text-gray-300'>{$k->listings_count} ilan</span></div>";
                    }

                    $bosHtml = $bos > 0
                        ? "<div class='p-2.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl text-xs text-amber-800 dark:text-amber-300'><strong>⚠️ Henüz İlanı Olmayan Kategoriler ({$bos}):</strong><div class='mt-1 text-2xs text-amber-700 dark:text-amber-400'>{$boslar}...</div></div>"
                        : "<div class='p-2.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs text-emerald-800 dark:text-emerald-300'><strong>✓ Mükemmel Dağılım:</strong> Tüm kategorilerde en az bir aktif ilan bulunmaktadır.</div>";

                    return new HtmlString(
                        "<div class='space-y-3.5 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2.5 text-center'>"
                        ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-xl'>"
                        ."<div class='text-primary-700 dark:text-primary-400 font-bold text-lg'>{$toplam}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Toplam ({$ana} Ana • {$alt} Alt)</div>"
                        .'</div>'
                        ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl'>"
                        ."<div class='text-emerald-700 dark:text-emerald-400 font-bold text-lg'>{$dolu}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>İlanlı Kategori</div>"
                        .'</div>'
                        ."<div class='p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl'>"
                        ."<div class='text-amber-700 dark:text-amber-400 font-bold text-lg'>{$bos}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>0 İlanlı Boş</div>"
                        .'</div>'
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700'>"
                        ."<div class='text-xs font-bold text-gray-900 dark:text-gray-100 mb-1.5'>🔥 En Popüler 5 Kategori</div>"
                        .$katHtml
                        .'</div>'
                        .$bosHtml
                        ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs text-stone-600 dark:text-stone-300 space-y-1'>"
                        ."<strong class='text-stone-900 dark:text-stone-100'>💡 AI Taksonomi Tavsiyesi:</strong>"
                        ."<p>Gurbetçi Türk topluluğunda en yüksek arama hacmine sahip 'Usta & Tamirat', 'Tercüme & Resmi İşlemler' ve 'Nakliye' kategorilerini ana menüde ilk sıralarda (düşük sort_order) tutmanız önerilir.</p>"
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
                ->label('Yeni Kategori Oluştur')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
