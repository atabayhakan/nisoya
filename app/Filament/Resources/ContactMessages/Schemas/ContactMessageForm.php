<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use App\Enums\ContactCategory;
use App\Enums\ContactMessageStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gönderen')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Ad Soyad')->disabled(),
                        TextInput::make('email')->label('E-posta')->disabled(),
                        TextInput::make('phone')->label('Telefon')->disabled(),
                        Select::make('category')
                            ->label('Konu')
                            ->options(ContactCategory::class)
                            ->disabled(),
                        Textarea::make('message')
                            ->label('Mesaj')
                            ->disabled()
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
                Section::make('Yönetim')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Durum')
                            ->options(ContactMessageStatus::class)
                            ->required(),
                        Textarea::make('admin_note')
                            ->label('İç not (yalnızca yönetim görür)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
