<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Enums\ReportStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('reporter_id')
                    ->relationship('reporter', 'name')
                    ->required(),
                TextInput::make('reportable_type')
                    ->required(),
                TextInput::make('reportable_id')
                    ->required()
                    ->numeric(),
                TextInput::make('reason')
                    ->required(),
                Textarea::make('note')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(ReportStatus::class)
                    ->default('acik')
                    ->required(),
            ]);
    }
}
