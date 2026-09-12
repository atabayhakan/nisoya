<?php

namespace App\Filament\Resources\FeatureRequests\Tables;

use App\Enums\FeatureRequestStatus;
use App\Models\FeatureRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeatureRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('listing.title')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('onayla')
                    ->label('Onayla')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Öne Çıkarma Talebini Onayla')
                    ->modalDescription(fn (FeatureRequest $record) => "Bu ilanı {$record->days} gün boyunca vitrinde öne çıkarmak istiyor musunuz?")
                    ->visible(fn (FeatureRequest $record) => $record->status === FeatureRequestStatus::Beklemede)
                    ->action(function (FeatureRequest $record): void {
                        $record->status = FeatureRequestStatus::Onaylandi;
                        $record->save();
                        Notification::make()->title('Talep onaylandı ve ilan öne çıkarıldı')->success()->send();
                    }),
                Action::make('reddet')
                    ->label('Reddet')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (FeatureRequest $record) => $record->status === FeatureRequestStatus::Beklemede)
                    ->action(function (FeatureRequest $record): void {
                        $record->status = FeatureRequestStatus::Reddedildi;
                        $record->save();
                        Notification::make()->title('Talep reddedildi')->warning()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
