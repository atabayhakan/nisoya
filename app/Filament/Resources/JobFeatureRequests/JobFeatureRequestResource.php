<?php

namespace App\Filament\Resources\JobFeatureRequests;

use App\Enums\FeatureRequestStatus;
use App\Filament\Resources\JobFeatureRequests\Pages\CreateJobFeatureRequest;
use App\Filament\Resources\JobFeatureRequests\Pages\EditJobFeatureRequest;
use App\Filament\Resources\JobFeatureRequests\Pages\ListJobFeatureRequests;
use App\Models\JobFeatureRequest;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class JobFeatureRequestResource extends Resource
{
    protected static ?string $model = JobFeatureRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'İş İlanları';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return 'Öne Çıkarma Talepleri';
    }

    public static function getModelLabel(): string
    {
        return 'öne çıkarma talebi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Öne Çıkarma Talepleri';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (JobFeatureRequest::query()->where('status', 'beklemede')->count() ?: '');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('job_listing_id')
                ->label('İş ilanı')
                ->relationship('jobListing', 'title')
                ->required()
                ->searchable(),
            Select::make('user_id')
                ->label('Talep eden')
                ->relationship('user', 'name')
                ->required()
                ->searchable(),
            TextInput::make('days')
                ->label('Gün')
                ->numeric()
                ->default(7)
                ->required(),
            Select::make('status')
                ->label('Durum')
                ->options(FeatureRequestStatus::class)
                ->default('beklemede')
                ->required(),
            DateTimePicker::make('processed_at')
                ->label('İşlenme tarihi'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jobListing.title')->label('İş ilanı')->searchable(),
                TextColumn::make('user.name')->label('Talep eden')->searchable(),
                TextColumn::make('days')->label('Gün')->numeric(),
                TextColumn::make('status')->label('Durum')->badge(),
                TextColumn::make('processed_at')->label('İşlendi')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobFeatureRequests::route('/'),
            'create' => CreateJobFeatureRequest::route('/create'),
            'edit' => EditJobFeatureRequest::route('/{record}/edit'),
        ];
    }
}
