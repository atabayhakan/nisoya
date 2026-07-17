<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Enums\ContactCategory;
use App\Enums\ContactMessageStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ad Soyad')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('category')
                    ->label('Konu')
                    ->badge()
                    ->searchable(),
                TextColumn::make('message')
                    ->label('Mesaj')
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Konu')
                    ->options(ContactCategory::class),
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(ContactMessageStatus::class),
            ])
            ->defaultSort('created_at', 'desc')
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
