<?php

declare(strict_types=1);

namespace App\Filament\Resources\Temsilcilikler\Pages;

use App\Filament\Resources\TemsilcilikIslemleri\TemsilcilikIslemiResource;
use App\Filament\Resources\Temsilcilikler\TemsilcilikResource;
use App\Models\Temsilcilik;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditTemsilcilik extends EditRecord
{
    protected static string $resource = TemsilcilikResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Temsilcilik $record */
        $record = $this->record;

        return [
            Action::make('canliSayfa')
                ->label('Canlı Sayfa')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => url('/'.strtolower($record->country_code).'/'.$record->slug))
                ->openUrlInNewTab(),

            Action::make('islemler')
                ->label('İşlem İçerikleri')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('info')
                ->url(fn (): string => TemsilcilikIslemiResource::getUrl('index', [
                    'tableFilters' => ['temsilcilik_id' => ['value' => $record->id]],
                ])),

            Action::make('harita')
                ->label('Harita')
                ->icon(Heroicon::OutlinedMapPin)
                ->color('gray')
                ->visible(fn (): bool => $record->latitude !== null && $record->longitude !== null)
                ->url(fn (): ?string => $record->haritaBaglantilari()->first()['url'] ?? null)
                ->openUrlInNewTab(),

            DeleteAction::make(),
        ];
    }
}
