<?php

namespace App\Filament\Resources\YasamKonuIcerikleri\Pages;

use App\Filament\Resources\YasamKonuIcerikleri\YasamKonuIcerigiResource;
use App\Filament\Resources\YasamKonulari\YasamKonusuResource;
use App\Models\YasamKonuIcerigi;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditYasamKonuIcerigi extends EditRecord
{
    protected static string $resource = YasamKonuIcerigiResource::class;

    protected function getHeaderActions(): array
    {
        /** @var YasamKonuIcerigi $record */
        $record = $this->record;

        return [
            Action::make('canliSayfa')
                ->label('Canlıda Gör')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->visible(fn (): bool => $record->konu !== null && $record->konu->kategori !== null)
                ->url(fn (): string => url('/'.strtolower($record->country_code).'/yasam/'.$record->konu->kategori->slug.'/'.$record->konu->slug))
                ->openUrlInNewTab(),

            Action::make('resmiKaynak')
                ->label('Resmî Kaynak')
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->color('gray')
                ->visible(fn (): bool => filled($record->kaynak_url))
                ->url(fn (): string => (string) $record->kaynak_url)
                ->openUrlInNewTab(),

            Action::make('konuGit')
                ->label('Konuyu Düzenle')
                ->icon(Heroicon::OutlinedQuestionMarkCircle)
                ->color('info')
                ->visible(fn (): bool => $record->yasam_konusu_id !== null)
                ->url(fn (): string => YasamKonusuResource::getUrl('edit', ['record' => $record->yasam_konusu_id])),

            DeleteAction::make(),
        ];
    }
}
