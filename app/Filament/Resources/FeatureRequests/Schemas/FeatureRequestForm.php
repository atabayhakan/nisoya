<?php

namespace App\Filament\Resources\FeatureRequests\Schemas;

use App\Enums\FeatureRequestStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FeatureRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('listing_id')
                    ->label('Öne Çıkarılacak İlan')
                    ->relationship('listing', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('user_id')
                    ->label('Talep Eden Üye')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('days')
                    ->label('Öne Çıkarma Süresi (Gün)')
                    ->suffix('Gün')
                    ->required()
                    ->numeric()
                    ->default(7)
                    ->minValue(1)
                    ->maxValue(365),

                Select::make('status')
                    ->label('Durum')
                    ->options(FeatureRequestStatus::class)
                    ->default('beklemede')
                    ->required(),

                DateTimePicker::make('processed_at')
                    ->label('İşlem Tarihi'),
            ]);
    }
}
