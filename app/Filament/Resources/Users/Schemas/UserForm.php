<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                TextInput::make('username'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('avatar_path'),
                Textarea::make('bio')
                    ->columnSpanFull(),
                TextInput::make('country_code'),
                TextInput::make('city'),
                TextInput::make('preferred_currency')
                    ->required()
                    ->default('EUR'),
                Select::make('role')
                    ->options(UserRole::class)
                    ->default('uye')
                    ->required(),
                Toggle::make('is_verified')
                    ->required(),
                Select::make('status')
                    ->options(UserStatus::class)
                    ->default('aktif')
                    ->required(),
                DateTimePicker::make('last_seen_at'),
            ]);
    }
}
