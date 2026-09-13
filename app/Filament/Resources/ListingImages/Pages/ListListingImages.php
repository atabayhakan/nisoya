<?php

namespace App\Filament\Resources\ListingImages\Pages;

use App\Filament\Resources\ListingImages\ListingImageResource;
use App\Filament\Resources\ListingImages\Widgets\ListingImageStatsWidget;
use App\Filament\Resources\Listings\ListingResource;
use App\Models\ListingImage;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\HtmlString;

class ListListingImages extends ListRecords
{
    protected static string $resource = ListingImageResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ListingImageStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $toplam = ListingImage::query()->count();
        $kapak = ListingImage::query()->where('is_cover', true)->count();
        $gps = ListingImage::query()->whereNotNull('gps_lat')->whereNotNull('gps_lng')->count();
        $isaretli = ListingImage::query()->where('is_flagged', true)->count();
        $hassasExif = ListingImage::query()->where('has_sensitive_exif', true)->count();

        return [
            'hepsi' => Tab::make('Tüm Görseller')
                ->badge((string) $toplam),

            'kapak' => Tab::make('Kapak Görselleri')
                ->badge($kapak > 0 ? (string) $kapak : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('is_cover', true)),

            'gps' => Tab::make('GPS Konumlular')
                ->badge($gps > 0 ? (string) $gps : null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('gps_lat')->whereNotNull('gps_lng')),

            'isaretli' => Tab::make('AI Moderasyon Uyarısı')
                ->badge($isaretli > 0 ? (string) $isaretli : null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('is_flagged', true)),

            'hassas_exif' => Tab::make('Hassas EXIF')
                ->badge($hassasExif > 0 ? (string) $hassasExif : null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('has_sensitive_exif', true)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiMedyaRaporu')
                ->label('AI Medya & Gizlilik Raporu')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Medya Havuzu & KVKK Gizlilik Analizi')
                ->modalDescription(function (): HtmlString {
                    $toplam = ListingImage::query()->count();
                    $kapak = ListingImage::query()->where('is_cover', true)->count();
                    $gps = ListingImage::query()->whereNotNull('gps_lat')->whereNotNull('gps_lng')->count();
                    $isaretli = ListingImage::query()->where('is_flagged', true)->count();
                    $toplamBayt = (int) (ListingImage::query()->sum('size_bytes') ?: 0);

                    if ($toplamBayt >= 1073741824) {
                        $depolamaStr = round($toplamBayt / 1073741824, 2).' GB';
                    } elseif ($toplamBayt >= 1048576) {
                        $depolamaStr = round($toplamBayt / 1048576, 1).' MB';
                    } else {
                        $depolamaStr = round($toplamBayt / 1024, 1).' KB';
                    }

                    return new HtmlString(
                        "<div class='space-y-3.5 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2.5 text-center'>"
                        ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-xl'>"
                        ."<div class='text-primary-700 dark:text-primary-400 font-bold text-lg'>{$toplam}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Toplam ({$kapak} Kapak)</div>"
                        .'</div>'
                        ."<div class='p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-xl'>"
                        ."<div class='text-blue-700 dark:text-blue-400 font-bold text-lg'>{$gps}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>GPS Koordinatlı</div>"
                        .'</div>'
                        ."<div class='p-3 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-xl'>"
                        ."<div class='text-rose-700 dark:text-rose-400 font-bold text-lg'>{$isaretli}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>AI Moderasyon İşaretli</div>"
                        .'</div>'
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 text-xs space-y-1'>"
                        ."<div class='flex justify-between items-center'><span>Toplam Disk Depolama Kullanımı:</span><span class='font-bold text-gray-900 dark:text-gray-100'>{$depolamaStr}</span></div>"
                        ."<div class='flex justify-between items-center'><span>EXIF Metadata & Gizlilik Durumu:</span><span class='font-bold text-emerald-600'>Aktif Filtreleniyor</span></div>"
                        .'</div>'
                        ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs text-stone-600 dark:text-stone-300 space-y-1'>"
                        ."<strong class='text-stone-900 dark:text-stone-100'>🔒 KVKK & Görsel Güvenlik Standartları:</strong>"
                        ."<p>Görseller yüklenirken hassas cihaz bilgileri (kamera seri no, orijinal GPS) istemci güvenliği için optimize edilir. AI moderasyonu uygunsuz içerik tespit ettiğinde görsel işaretlenir ve ilan onay beklemeye alınır. 'Onayla' butonu ile güvenli içeriklerin işaretini kaldırabilirsiniz.</p>"
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            Action::make('reprocess_all')
                ->label('Toplu Yeniden İşle')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function (): void {
                    Artisan::call('images:reprocess');
                    Notification::make()
                        ->title('Toplu yeniden işleme tamamlandı')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Tüm görselleri yeniden işle')
                ->modalDescription('Mevcut tüm görsellere EXIF orientation düzeltmesi ve metadata temizliği uygular. Bu işlem uzun sürebilir.'),

            Action::make('reverse_geocode_all')
                ->label('Reverse Geocode (Toplu)')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->action(function (): void {
                    Artisan::call('images:reverse-geocode');
                    Notification::make()
                        ->title('Toplu reverse geocoding başladı (arkaplan)')
                        ->info()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Tüm görselleri reverse geocode et')
                ->modalDescription('GPS koordinatı bilinen görseller için Nominatim üzerinden şehir/ülke tespiti yapılır. Rate limit nedeniyle uzun sürebilir (dakikada max 60 işlem).'),

            Action::make('ilanlar')
                ->label('Tüm İlanlar')
                ->icon(Heroicon::OutlinedShoppingBag)
                ->color('gray')
                ->url(fn (): string => ListingResource::getUrl('index')),
        ];
    }
}
