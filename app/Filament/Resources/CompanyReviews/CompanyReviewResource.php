<?php

namespace App\Filament\Resources\CompanyReviews;

use App\Enums\ReviewStatus;
use App\Filament\Resources\CompanyReviews\Pages\CreateCompanyReview;
use App\Filament\Resources\CompanyReviews\Pages\EditCompanyReview;
use App\Filament\Resources\CompanyReviews\Pages\ListCompanyReviews;
use App\Models\CompanyReview;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CompanyReviewResource extends Resource
{
    protected static ?string $model = CompanyReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'İş & Kariyer Portalı';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return 'Şirket Değerlendirmeleri';
    }

    public static function getModelLabel(): string
    {
        return 'şirket değerlendirmesi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Şirket Değerlendirmeleri';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')
                ->label('Şirket')
                ->relationship('company', 'name')
                ->required()
                ->searchable(),
            Select::make('reviewer_id')
                ->label('Değerlendiren')
                ->relationship('reviewer', 'name')
                ->required()
                ->searchable(),
            TextInput::make('rating')
                ->label('Puan')
                ->numeric()
                ->minValue(1)
                ->maxValue(5)
                ->required(),
            Textarea::make('comment')
                ->label('Yorum')
                ->columnSpanFull(),
            Select::make('status')
                ->label('Durum')
                ->options(ReviewStatus::class)
                ->default('yayinda')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->label('Şirket')->searchable(),
                TextColumn::make('reviewer.name')->label('Değerlendiren')->searchable(),
                TextColumn::make('rating')->label('Puan')->numeric()->sortable(),
                TextColumn::make('status')->label('Durum')->badge()->searchable(),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Durum')->options(ReviewStatus::class),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyReviews::route('/'),
            'create' => CreateCompanyReview::route('/create'),
            'edit' => EditCompanyReview::route('/{record}/edit'),
        ];
    }
}
