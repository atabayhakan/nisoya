<?php

namespace App\Filament\Resources\Stories\Schemas;

use App\Enums\StoryStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class StoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Yazan (herkese açık tarafta hiç gösterilmez)')
                    ->required(),
                Textarea::make('body')
                    ->label('Hikaye')
                    ->maxLength(600)
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(StoryStatus::class)
                    ->default('beklemede')
                    ->required(),
            ]);
    }
}
