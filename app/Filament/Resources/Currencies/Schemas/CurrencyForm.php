<?php

namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Para Birimi Kodu (3 Harf)')
                    ->required()
                    ->maxLength(3)
                    ->placeholder('EUR')
                    ->extraInputAttributes(['style' => 'text-transform: uppercase']),

                TextInput::make('name')
                    ->label('Para Birimi Adı')
                    ->required()
                    ->placeholder('Euro'),

                TextInput::make('symbol')
                    ->label('Sembol')
                    ->required()
                    ->maxLength(8)
                    ->placeholder('€'),

                Toggle::make('is_active')
                    ->label('Aktif mi?')
                    ->default(true)
                    ->required(),

                TextInput::make('sort_order')
                    ->label('Sıralama')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
