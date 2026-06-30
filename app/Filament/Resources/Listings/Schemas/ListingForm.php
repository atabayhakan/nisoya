<?php

namespace App\Filament\Resources\Listings\Schemas;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\PriceUnit;
use App\Models\Country;
use App\Models\Currency;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('İlan Bilgileri')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Üye')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('type')
                            ->label('Tür')
                            ->options(ListingType::class)
                            ->default('hizmet')
                            ->required(),
                        TextInput::make('title')
                            ->label('Başlık')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                            ->label('Kısa ad (URL)')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Açıklama')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Section::make('Fiyat & Konum')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')
                            ->label('Fiyat')
                            ->numeric()
                            ->placeholder('Boş bırakırsan "görüşülür"'),
                        Select::make('currency')
                            ->label('Para birimi')
                            ->options(fn () => Currency::query()->orderBy('sort_order')->pluck('name', 'code'))
                            ->default('EUR')
                            ->required(),
                        Select::make('price_unit')
                            ->label('Fiyat birimi')
                            ->options(PriceUnit::class)
                            ->default('gorusulur')
                            ->required(),
                        Select::make('country_code')
                            ->label('Ülke')
                            ->options(fn () => Country::query()->orderBy('sort_order')->get()
                                ->mapWithKeys(fn ($c) => [$c->code => trim(($c->emoji ?? '').' '.$c->name_tr)]))
                            ->searchable(),
                        TextInput::make('city')
                            ->label('Şehir')
                            ->maxLength(255),
                        Toggle::make('is_remote')
                            ->label('Uzaktan / online verilebilir'),
                    ]),

                Section::make('Durum & Öne Çıkarma')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Durum')
                            ->options(ListingStatus::class)
                            ->default('beklemede')
                            ->required(),
                        TextInput::make('stock')
                            ->label('Stok (ürün için)')
                            ->numeric(),
                        Toggle::make('is_featured')
                            ->label('Öne çıkan'),
                        DateTimePicker::make('featured_until')
                            ->label('Öne çıkma bitiş tarihi'),
                    ]),
            ]);
    }
}
