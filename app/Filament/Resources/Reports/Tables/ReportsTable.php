<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Enums\ReportStatus;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reporter.name')
                    ->label('Şikayet Eden')
                    ->searchable(),
                TextColumn::make('reportable_type')
                    ->label('Hedef Tür')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'App\\Models\\Listing' => 'İlan',
                        'App\\Models\\User' => 'Üye',
                        'App\\Models\\Review' => 'Değerlendirme',
                        default => class_basename($state),
                    })
                    ->badge()
                    ->searchable(),
                TextColumn::make('reportable_title')
                    ->label('Hedef')
                    ->getStateUsing(function ($record) {
                        if (! $record->reportable) {
                            return '— (silinmiş)';
                        }

                        return match (true) {
                            $record->reportable instanceof Listing => $record->reportable->title,
                            $record->reportable instanceof User => $record->reportable->name,
                            $record->reportable instanceof Review => mb_substr((string) $record->reportable->comment, 0, 60),
                            default => '#'.$record->reportable_id,
                        };
                    })
                    ->url(fn ($record) => match (true) {
                        $record->reportable instanceof Listing => route('listings.show', [$record->reportable_id, $record->reportable?->slug]),
                        $record->reportable instanceof User => route('profiles.show', $record->reportable?->username),
                        default => null,
                    }, shouldOpenInNewTab: true)
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHasMorph('reportable', ['App\\Models\\Listing', 'App\\Models\\User', 'App\\Models\\Review'], function ($q, $type) use ($search) {
                            if ($type === Listing::class) {
                                $q->where('title', 'like', "%{$search}%");
                            } elseif ($type === User::class) {
                                $q->where('name', 'like', "%{$search}%");
                            } else {
                                $q->where('comment', 'like', "%{$search}%");
                            }
                        });
                    }),
                TextColumn::make('reason')
                    ->label('Sebep')
                    ->searchable(),
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
                SelectFilter::make('reportable_type')
                    ->label('Hedef Tür')
                    ->options([
                        'App\\Models\\Listing' => 'İlan',
                        'App\\Models\\User' => 'Üye',
                        'App\\Models\\Review' => 'Değerlendirme',
                    ]),
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(ReportStatus::class),
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
