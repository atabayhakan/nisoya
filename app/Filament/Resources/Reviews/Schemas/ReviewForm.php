<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Enums\ReviewStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('listing_id')
                    ->relationship('listing', 'title'),
                Select::make('reviewee_id')
                    ->relationship('reviewee', 'name')
                    ->required(),
                Select::make('reviewer_id')
                    ->relationship('reviewer', 'name')
                    ->required(),
                TextInput::make('rating')
                    ->required()
                    ->numeric(),
                Textarea::make('comment')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(ReviewStatus::class)
                    ->default('yayinda')
                    ->required(),
            ]);
    }
}
