<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserStatus;
use App\Filament\Actions\BulkToggleUserStatusAction;
use App\Filament\Actions\ToggleUserStatusAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
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
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('avatar_path')
                    ->searchable(),
                TextColumn::make('country_code')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('preferred_currency')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->searchable(),
                IconColumn::make('is_verified')
                    ->boolean(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('last_seen_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'uye' => 'Üye',
                        'moderator' => 'Moderatör',
                        'admin' => 'Yönetici',
                    ]),
                SelectFilter::make('status')
                    ->options(UserStatus::class),
                SelectFilter::make('is_verified')
                    ->label('Doğrulanmış mı')
                    ->options([
                        '1' => 'Evet',
                        '0' => 'Hayır',
                    ]),
            ])
            ->recordActions([
                ToggleUserStatusAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkToggleUserStatusAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
