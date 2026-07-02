<?php

namespace App\Filament\Resources\ListingImages\Pages;

use App\Filament\Resources\ListingImages\ListingImageResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListListingImages extends ListRecords
{
    protected static string $resource = ListingImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reprocess_all')
                ->label('Toplu yeniden işle')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    \Illuminate\Support\Facades\Artisan::call('images:reprocess');
                    $this->notify('Toplu yeniden işleme tamamlandı.');
                })
                ->requiresConfirmation()
                ->modalHeading('Tüm görselleri yeniden işle')
                ->modalDescription('Mevcut tüm görsellere EXIF orientation düzeltmesi ve metadata temizliği uygular. Bu işlem uzun sürebilir.'),

            Action::make('reverse_geocode_all')
                ->label('Reverse geocode (toplu)')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->action(function () {
                    \Illuminate\Support\Facades\Artisan::call('images:reverse-geocode');
                    $this->notify('Toplu reverse geocoding başladı (arkaplan).');
                })
                ->requiresConfirmation()
                ->modalHeading('Tüm görselleri reverse geocode et')
                ->modalDescription('GPS koordinatı bilinen görseller için Nominatim üzerinden şehir/ülke tespiti yapılır. Rate limit nedeniyle uzun sürebilir (yaklaşık 1.1 sn/görsel).'),
        ];
    }
}