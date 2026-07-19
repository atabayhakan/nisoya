<?php

namespace App\Filament\Resources\Deals\Tables;

use App\Enums\DealStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DealsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('buyer.name')
                    ->label('Alıcı')
                    ->searchable(),
                TextColumn::make('seller.name')
                    ->label('Satıcı')
                    ->searchable(),
                TextColumn::make('listing.title')
                    ->label('İlan')
                    ->placeholder('—')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label('Tutar')
                    ->formatStateUsing(fn ($state, $record): string => $state !== null ? $state.' '.$record->currency : 'Görüşülür'),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge(),
                TextColumn::make('dispute_note')
                    ->label('Sorun notu')
                    ->placeholder('—')
                    ->wrap()
                    ->limit(80)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(DealStatus::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
