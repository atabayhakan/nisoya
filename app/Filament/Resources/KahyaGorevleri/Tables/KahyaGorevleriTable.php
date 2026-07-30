<?php

namespace App\Filament\Resources\KahyaGorevleri\Tables;

use App\Models\KahyaGorevi;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class KahyaGorevleriTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('son_islem_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('baslik')
                    ->label('Görev')
                    ->searchable()
                    ->wrap()
                    ->description(fn (KahyaGorevi $record): string => Str::limit($record->hedef, 90)),
                TextColumn::make('durum')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        KahyaGorevi::DURUM_ACIK => 'Açık',
                        KahyaGorevi::DURUM_TAMAMLANDI => 'Tamamlandı',
                        KahyaGorevi::DURUM_IPTAL => 'İptal',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        KahyaGorevi::DURUM_ACIK => 'info',
                        KahyaGorevi::DURUM_TAMAMLANDI => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('ilerleme')
                    ->label('İlerleme')
                    ->state(function (KahyaGorevi $record): string {
                        $i = $record->ilerleme();

                        return $i['toplam'] > 0 ? "{$i['yapildi']}/{$i['toplam']} adım" : 'plansız';
                    }),
                TextColumn::make('hamleler_count')
                    ->label('Hamle')
                    ->counts('hamleler')
                    ->tooltip('Bu göreve bağlı hamle kartı sayısı.'),
                TextColumn::make('son_islem_at')
                    ->label('Son hareket')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options([
                        KahyaGorevi::DURUM_ACIK => 'Açık',
                        KahyaGorevi::DURUM_TAMAMLANDI => 'Tamamlandı',
                        KahyaGorevi::DURUM_IPTAL => 'İptal',
                    ])
                    ->default(KahyaGorevi::DURUM_ACIK),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
