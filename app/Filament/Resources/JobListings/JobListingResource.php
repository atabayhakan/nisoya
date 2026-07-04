<?php

namespace App\Filament\Resources\JobListings;

use App\Enums\JobStatus;
use App\Filament\Resources\JobListings\Pages\EditJobListing;
use App\Filament\Resources\JobListings\Pages\ListJobListings;
use App\Models\JobListing;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JobListingResource extends Resource
{
    protected static ?string $model = JobListing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'İş İlanları';
    }

    public static function getNavigationLabel(): string
    {
        return 'İş İlanları';
    }

    public static function getModelLabel(): string
    {
        return 'iş ilanı';
    }

    public static function getPluralModelLabel(): string
    {
        return 'İş İlanları';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')->label('Durum')->options(JobStatus::class)->required(),
            Toggle::make('is_featured')->label('Öne çıkan'),
            DateTimePicker::make('featured_until')->label('Öne çıkarma bitişi'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Başlık')->searchable()->limit(50),
                TextColumn::make('company.name')->label('Şirket')->searchable(),
                TextColumn::make('category.name')->label('Kategori')->toggleable(),
                TextColumn::make('status')->label('Durum')->badge(),
                TextColumn::make('applications_count')->label('Başvuru')->counts('applications'),
                IconColumn::make('is_featured')->label('Öne çıkan')->boolean(),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Durum')->options(JobStatus::class),
                SelectFilter::make('is_featured')->label('Öne çıkan')->options([1 => 'Evet', 0 => 'Hayır']),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobListings::route('/'),
            'edit' => EditJobListing::route('/{record}/edit'),
        ];
    }
}
