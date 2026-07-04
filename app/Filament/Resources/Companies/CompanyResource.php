<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Models\Company;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'İş İlanları';
    }

    public static function getNavigationLabel(): string
    {
        return 'Şirketler';
    }

    public static function getModelLabel(): string
    {
        return 'şirket';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Şirketler';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Şirket adı')->required(),
            TextInput::make('sector')->label('Sektör'),
            TextInput::make('website')->label('Web sitesi')->url(),
            Textarea::make('about')->label('Hakkında')->columnSpanFull(),
            Toggle::make('is_verified')->label('Doğrulanmış'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Şirket')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Sahip')->searchable(),
                TextColumn::make('sector')->label('Sektör')->toggleable(),
                TextColumn::make('job_listings_count')->label('İlan')->counts('jobListings'),
                IconColumn::make('is_verified')->label('Doğrulandı')->boolean(),
                TextColumn::make('created_at')->label('Kayıt')->dateTime('d.m.Y')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_verified')->label('Doğrulanmış'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
