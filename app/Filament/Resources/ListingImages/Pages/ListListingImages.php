<?php

namespace App\Filament\Resources\ListingImages\Pages;

use App\Filament\Resources\ListingImages\ListingImageResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

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
                ->label('Reverse geocode (toplu)')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->action(function () {
                    // Nominatim rate limit (1 req/s) — admin başına dakikada max 60 işlem
                    // (config/app.php'de tanımlı 'reverse-geocode' policy).
                    // Bu sayede 3600 görsel/saat'ten fazla istek atılması engellenir.
                    Artisan::call('images:reverse-geocode');
                    Notification::make()
                        ->title('Toplu reverse geocoding başladı (arkaplan)')
                        ->info()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Tüm görselleri reverse geocode et')
                ->modalDescription('GPS koordinatı bilinen görseller için Nominatim üzerinden şehir/ülke tespiti yapılır. Rate limit nedeniyle uzun sürebilir (yaklaşık 1.1 sn/görsel, dakikada max 60 işlem).'),
        ];
    }
}
