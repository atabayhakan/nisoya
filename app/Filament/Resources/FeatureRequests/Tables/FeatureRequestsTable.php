<?php

namespace App\Filament\Resources\FeatureRequests\Tables;

use App\Enums\FeatureRequestStatus;
use App\Filament\Resources\FeatureRequests\FeatureRequestResource;
use App\Filament\Resources\Listings\ListingResource;
use App\Models\FeatureRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class FeatureRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('listing.title')
                    ->label('İlan')
                    ->weight('bold')
                    ->icon('heroicon-m-shopping-bag')
                    ->description(function (FeatureRequest $record): string {
                        $cat = $record->listing && $record->listing->category ? $record->listing->category->name : null;
                        $until = $record->listing && $record->listing->featured_until ? ('Vitrin Bitiş: '.$record->listing->featured_until->format('d.m.Y')) : null;
                        if ($cat && $until) {
                            return "{$cat} • {$until}";
                        }
                        if ($cat) {
                            return $cat;
                        }
                        if ($until) {
                            return $until;
                        }

                        return 'İlan Detayı';
                    })
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Talep Eden Üye')
                    ->weight('bold')
                    ->icon('heroicon-m-user')
                    ->description(fn (FeatureRequest $record): ?string => $record->user ? $record->user->email : null)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('days')
                    ->label('Süre')
                    ->formatStateUsing(fn ($state): string => "{$state} Gün")
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-calendar-days')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->icon(fn (FeatureRequestStatus $state): string => match ($state) {
                        FeatureRequestStatus::Beklemede => 'heroicon-m-clock',
                        FeatureRequestStatus::Onaylandi => 'heroicon-m-check-circle',
                        FeatureRequestStatus::Reddedildi => 'heroicon-m-x-circle',
                    })
                    ->sortable(),

                TextColumn::make('processed_at')
                    ->label('İşlem Tarihi')
                    ->formatStateUsing(fn ($state) => $state ? $state->format('d.m.Y H:i') : 'Bekliyor')
                    ->badge(fn ($state): bool => $state === null)
                    ->color(fn ($state): string => $state === null ? 'warning' : 'gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Talep Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (FeatureRequest $record): string => $record->created_at ? $record->created_at->diffForHumans() : '')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Son Güncelleme')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(FeatureRequestStatus::class),

                SelectFilter::make('days')
                    ->label('Süre (Gün)')
                    ->options([
                        7 => '7 Gün (1 Hafta)',
                        14 => '14 Gün (2 Hafta)',
                        30 => '30 Gün (1 Ay)',
                        60 => '60 Gün (2 Ay)',
                    ]),
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
                        Notification::make()->title('Talep onaylandı ve ilan vitrine alındı')->success()->send();
                    }),

                Action::make('reddet')
                    ->label('Reddet')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Talebi Reddet')
                    ->modalDescription('Bu öne çıkarma talebini reddetmek istediğinize emin misiniz?')
                    ->visible(fn (FeatureRequest $record) => $record->status === FeatureRequestStatus::Beklemede)
                    ->action(function (FeatureRequest $record): void {
                        $record->status = FeatureRequestStatus::Reddedildi;
                        $record->save();
                        Notification::make()->title('Talep reddedildi')->warning()->send();
                    }),

                Action::make('sitedeGor')
                    ->label('İlanı Gör')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->visible(fn (FeatureRequest $record): bool => $record->listing !== null)
                    ->url(fn (FeatureRequest $record): string => route('listings.show', [$record->listing->id, $record->listing->slug]), shouldOpenInNewTab: true),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('topluOnayla')
                        ->label('Seçilenleri Onayla')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record instanceof FeatureRequest && $record->status === FeatureRequestStatus::Beklemede) {
                                    $record->status = FeatureRequestStatus::Onaylandi;
                                    $record->save();
                                    $count++;
                                }
                            }
                            Notification::make()->title("{$count} talep onaylandı ve vitrine alındı.")->success()->send();
                        }),

                    BulkAction::make('topluReddet')
                        ->label('Seçilenleri Reddet')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record instanceof FeatureRequest && $record->status === FeatureRequestStatus::Beklemede) {
                                    $record->status = FeatureRequestStatus::Reddedildi;
                                    $record->save();
                                    $count++;
                                }
                            }
                            Notification::make()->title("{$count} talep reddedildi.")->info()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Henüz öne çıkarma talebi bulunmuyor')
            ->emptyStateDescription('İlan sahipleri ilanlarını ana sayfa ve arama vitrininde öne çıkarmak istediklerinde talepleri bu listeye düşer. Bekleyen talepleri onaylayarak ilanı hemen vitrine alabilirsiniz.')
            ->emptyStateIcon(Heroicon::OutlinedRocketLaunch)
            ->emptyStateActions([
                Action::make('yeniTalep')
                    ->label('Yeni Talep Oluştur')
                    ->icon(Heroicon::OutlinedPlus)
                    ->color('primary')
                    ->url(fn (): string => FeatureRequestResource::getUrl('create')),

                Action::make('ilanlar')
                    ->label('Tüm İlanlar')
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->color('gray')
                    ->url(fn (): string => ListingResource::getUrl('index')),
            ]);
    }
}
