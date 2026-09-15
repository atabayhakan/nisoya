<?php

namespace App\Filament\Resources\DiasporaAccounts\Pages;

use App\Filament\Concerns\GuardsAdminGeoContext;
use App\Filament\Resources\DiasporaAccounts\DiasporaAccountResource;
use App\Models\DiasporaAccount;
use App\Support\GlobalCommand\DiasporaDispatch;
use App\Support\GlobalCommand\GeoContext;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDiasporaAccounts extends ListRecords
{
    use GuardsAdminGeoContext;

    protected static string $resource = DiasporaAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tumunuSenkronizeEt')->label('Bu Görünümdeki Hesapları Tara')->icon('heroicon-o-arrow-path')
                ->action(function (DiasporaDispatch $dispatch): void {
                    $count = $dispatch->enqueue(auth()->user(), app(GeoContext::class)->apply(DiasporaAccount::query()));
                    Notification::make()->title('Tarama talepleri alındı')->body($count.' aktif, doğrulanmış hesap kuyruğa alındı.')->success()->send();
                }),
            CreateAction::make()->label('Yeni Hesap Takip Et')->icon('heroicon-o-plus'),
        ];
    }
}
