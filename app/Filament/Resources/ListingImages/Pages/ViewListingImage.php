<?php

namespace App\Filament\Resources\ListingImages\Pages;

use App\Filament\Resources\ListingImages\ListingImageResource;
use App\Filament\Resources\Listings\Infolists\ListingImageInfolist;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewListingImage extends ViewRecord
{
    protected static string $resource = ListingImageResource::class;

    public function infolist(Schema $schema): Schema
    {
        return ListingImageInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_listing')
                ->label('İlana git')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => route('listings.show', $this->record->listing_id))
                ->openUrlInNewTab(),

            Action::make('view_exif_raw')
                ->label('Ham EXIF JSON')
                ->icon('heroicon-o-code-bracket')
                ->modalContent(fn () => view('filament.partials.exif-raw', [
                    'exif' => $this->record->exif_metadata ?? [],
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            DeleteAction::make(),
        ];
    }
}
