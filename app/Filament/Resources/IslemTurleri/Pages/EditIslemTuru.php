<?php

declare(strict_types=1);

namespace App\Filament\Resources\IslemTurleri\Pages;

use App\Filament\Resources\IslemTurleri\IslemTuruResource;
use App\Filament\Resources\TemsilcilikIslemleri\TemsilcilikIslemiResource;
use App\Models\IslemTuru;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditIslemTuru extends EditRecord
{
    protected static string $resource = IslemTuruResource::class;

    protected function getHeaderActions(): array
    {
        /** @var IslemTuru $record */
        $record = $this->record;

        return [
            Action::make('islemler')
                ->label('Temsilcilik İçerikleri')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('info')
                ->url(fn (): string => TemsilcilikIslemiResource::getUrl('index', [
                    'tableFilters' => ['islem_turu_id' => ['value' => $record->id]],
                ])),

            DeleteAction::make(),
        ];
    }
}
