<?php

declare(strict_types=1);

namespace App\Filament\Resources\TemsilcilikIslemleri\Pages;

use App\Filament\Resources\TemsilcilikIslemleri\TemsilcilikIslemiResource;
use App\Filament\Resources\Temsilcilikler\TemsilcilikResource;
use App\Models\TemsilcilikIslemi;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditTemsilcilikIslemi extends EditRecord
{
    protected static string $resource = TemsilcilikIslemiResource::class;

    protected function getHeaderActions(): array
    {
        /** @var TemsilcilikIslemi $record */
        $record = $this->record;

        return [
            Action::make('canliSayfa')
                ->label('Canlı Sayfa')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->visible(fn (): bool => $record->temsilcilik !== null && $record->islemTuru !== null)
                ->url(fn (): string => url('/'.strtolower($record->temsilcilik->country_code).'/'.$record->temsilcilik->slug.'/'.$record->islemTuru->slug))
                ->openUrlInNewTab(),

            Action::make('resmiKaynak')
                ->label('Resmî Kaynak')
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->color('gray')
                ->visible(fn (): bool => filled($record->resmi_kaynak_url))
                ->url(fn (): string => (string) $record->resmi_kaynak_url)
                ->openUrlInNewTab(),

            Action::make('temsilcilik')
                ->label('Temsilcilik Bilgisi')
                ->icon(Heroicon::OutlinedBuildingLibrary)
                ->color('info')
                ->url(fn (): string => TemsilcilikResource::getUrl('edit', ['record' => $record->temsilcilik_id])),

            DeleteAction::make(),
        ];
    }
}
