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
                    ->relationship('listing', 'title')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('days')
                    ->required()
                    ->numeric()
                    ->default(7),
                Select::make('status')
                    ->options(FeatureRequestStatus::class)
                    ->default('beklemede')
                    ->required(),
                DateTimePicker::make('processed_at'),
            ]);
    }
}
