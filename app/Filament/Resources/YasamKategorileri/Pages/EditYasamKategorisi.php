<?php

namespace App\Filament\Resources\YasamKategorileri\Pages;

use App\Filament\Resources\YasamKategorileri\YasamKategorisiResource;
use App\Filament\Resources\YasamKonulari\YasamKonusuResource;
use App\Models\YasamKategorisi;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditYasamKategorisi extends EditRecord
{
    protected static string $resource = YasamKategorisiResource::class;

    protected function getHeaderActions(): array
    {
        /** @var YasamKategorisi $record */
        $record = $this->record;

        return [
            Action::make('canliSayfa')
                ->label('Canlıda Gör')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => url('/de/yasam/'.$record->slug))
                ->openUrlInNewTab(),

            Action::make('konulariGor')
                ->label('Kategori Konuları')
                ->icon(Heroicon::OutlinedQuestionMarkCircle)
                ->color('info')
                ->url(fn (): string => YasamKonusuResource::getUrl('index', ['tableFilters[kategori_id][value]' => $record->id])),

            DeleteAction::make(),
        ];
    }
}
