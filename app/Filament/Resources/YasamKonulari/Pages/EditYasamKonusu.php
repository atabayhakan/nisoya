<?php

namespace App\Filament\Resources\YasamKonulari\Pages;

use App\Filament\Resources\YasamKategorileri\YasamKategorisiResource;
use App\Filament\Resources\YasamKonuIcerikleri\YasamKonuIcerigiResource;
use App\Filament\Resources\YasamKonulari\YasamKonusuResource;
use App\Models\YasamKonusu;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditYasamKonusu extends EditRecord
{
    protected static string $resource = YasamKonusuResource::class;

    protected function getHeaderActions(): array
    {
        /** @var YasamKonusu $record */
        $record = $this->record;

        return [
            Action::make('canliSayfa')
                ->label('Canlıda Gör')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->visible(fn (): bool => $record->kategori !== null)
                ->url(fn (): string => url('/de/yasam/'.$record->kategori->slug.'/'.$record->slug))
                ->openUrlInNewTab(),

            Action::make('icerikler')
                ->label('Ülke İçerikleri')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('info')
                ->url(fn (): string => YasamKonuIcerigiResource::getUrl('index', ['tableFilters[yasam_konusu_id][value]' => $record->id])),

            Action::make('kategori')
                ->label('Kategoriye Git')
                ->icon(Heroicon::OutlinedSquares2x2)
                ->color('gray')
                ->visible(fn (): bool => $record->kategori_id !== null)
                ->url(fn (): string => YasamKategorisiResource::getUrl('edit', ['record' => $record->kategori_id])),

            DeleteAction::make(),
        ];
    }
}
